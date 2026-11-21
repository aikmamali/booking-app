<?php

declare(strict_types=1);

use App\Modules\Booking\Actions\CloseFinishedSessions;
use App\Modules\Booking\Actions\MarkAttendance;
use App\Modules\Booking\Actions\SendBookingReminders;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Communication\Notifications\BookingCancelledNotification;
use App\Modules\Communication\Notifications\BookingReminderNotification;
use App\Modules\Identity\Actions\DeleteAccount;
use App\Modules\Scheduling\Actions\CancelSession;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    $this->travelTo('2027-03-20 08:00:00');
    $this->session = bookableSession(); // 23 March 16:30–17:30 UTC
    $this->customer = customerFor($this->session);
    $this->booking = book($this->customer, $this->session);
});

function setting(string $key, mixed $value): void
{
    $settings = app(BookingPolicySettings::class);
    $settings->{$key} = $value;
    $settings->save();
}

describe('attendance', function (): void {
    it('is recorded once the session has started', function (BookingStatus $outcome): void {
        $this->travelTo('2027-03-23 16:35:00');

        expect(app(MarkAttendance::class)->handle($this->booking, $outcome)->status)->toBe($outcome)
            ->and(creditsOf($this->customer))->toBe('9.00');
    })->with([BookingStatus::Attended, BookingStatus::NoShow]);

    it('is refused before the start or twice', function (): void {
        expect(fn () => app(MarkAttendance::class)->handle($this->booking, BookingStatus::Attended))->toThrow(BookingRejected::class, 'booking.not_started');

        $this->travelTo('2027-03-23 16:35:00');
        app(MarkAttendance::class)->handle($this->booking, BookingStatus::Attended);
        expect(fn () => app(MarkAttendance::class)->handle($this->booking, BookingStatus::NoShow))->toThrow(BookingRejected::class, 'booking.not_markable');
    });

    it('refunds a no-show only when the setting says so', function (): void {
        setting('no_show_refunds_credit', true);
        $this->travelTo('2027-03-23 16:35:00');

        app(MarkAttendance::class)->handle($this->booking, BookingStatus::NoShow);

        expect(creditsOf($this->customer))->toBe('10.00');
    });
});

describe('closing finished sessions (MarkNoShows)', function (): void {
    it('marks bookings attended when there is no check-in at the door', function (): void {
        $this->travelTo('2027-03-23 17:29:00');
        expect(app(CloseFinishedSessions::class)->handle())->toBe(0);

        $this->travelTo('2027-03-23 17:30:00');
        expect(app(CloseFinishedSessions::class)->handle())->toBe(1)
            ->and(app(CloseFinishedSessions::class)->handle())->toBe(0)
            ->and($this->booking->fresh()?->status)->toBe(BookingStatus::Attended);
    });

    it('marks no-shows when check-in is required', function (): void {
        setting('checkin_required', true);
        $this->travelTo('2027-03-23 17:45:00');

        app(CloseFinishedSessions::class)->handle();

        expect($this->booking->fresh()?->status)->toBe(BookingStatus::NoShow)
            ->and(creditsOf($this->customer))->toBe('9.00');
    });
});

it('cancels and refunds every booking when the business cancels the session', function (): void {
    Notification::fake();
    $other = customerFor($this->session);
    book($other, $this->session);

    app(CancelSession::class)->handle($this->session, 'Ασθένεια εκπαιδευτή');

    expect($this->booking->fresh()?->status)->toBe(BookingStatus::Cancelled)
        ->and($this->booking->fresh()?->cancel_reason)->toBe('Ασθένεια εκπαιδευτή')
        ->and(creditsOf($this->customer))->toBe('10.00')
        ->and(creditsOf($other))->toBe('10.00');
    Notification::assertSentTo([$this->customer, $other], BookingCancelledNotification::class);
});

it('cancels future bookings, with refund, when the customer deletes their account', function (): void {
    $past = bookableSession(['starts_at' => '2027-03-20 07:00:00', 'ends_at' => '2027-03-20 07:30:00'], $this->session->service);
    $old = Booking::factory()->create(['user_id' => $this->customer->id, 'class_session_id' => $past->id]);

    app(DeleteAccount::class)->handle($this->customer);

    expect($this->booking->fresh()?->status)->toBe(BookingStatus::Cancelled)
        ->and($this->booking->fresh()?->cancel_reason)->toBe('account_deleted')
        ->and($old->fresh()?->status)->toBe(BookingStatus::Confirmed);
});

describe('reminders', function (): void {
    beforeEach(fn () => Notification::fake());

    it('sends one reminder 24 hours and one 2 hours before', function (): void {
        $send = fn (string $at): int => tap(app(SendBookingReminders::class), fn () => $this->travelTo($at))->handle();

        expect($send('2027-03-22 16:29:00'))->toBe(0)
            ->and($send('2027-03-22 16:30:00'))->toBe(1)
            ->and($send('2027-03-22 20:00:00'))->toBe(0)
            ->and($send('2027-03-23 14:30:00'))->toBe(1)
            ->and($send('2027-03-23 15:00:00'))->toBe(0);
        Notification::assertSentToTimes($this->customer, BookingReminderNotification::class, 2);
    });

    it('skips the reminders whose moment had passed when the booking was made', function (): void {
        $this->booking->forceFill(['remind' => false])->save(); // isolate the late booking
        $customer = customerFor($this->session);
        $this->travelTo('2027-03-23 13:00:00'); // 3.5 hours before
        $late = book($customer, $this->session);

        expect(app(SendBookingReminders::class)->handle())->toBe(0);
        $this->travelTo('2027-03-23 14:31:00');
        expect(app(SendBookingReminders::class)->handle())->toBe(1);
        Notification::assertSentTo($late->user, BookingReminderNotification::class);
    });

    it('respects the opt-out and cancelled bookings', function (): void {
        $this->booking->forceFill(['remind' => false])->save();
        $this->travelTo('2027-03-23 14:31:00');

        expect(app(SendBookingReminders::class)->handle())->toBe(0);
        Notification::assertNothingSent();
    });
});

it('runs the booking jobs at the times of spec section 8', function (string $name, string $cron): void {
    $event = collect(app(Schedule::class)->events())->first(fn ($e): bool => $e->description === $name);

    expect($event?->expression)->toBe($cron)->and($event?->withoutOverlapping)->toBeTrue();
})->with([
    ['send-booking-reminders', '*/5 * * * *'],
    ['mark-no-shows', '*/15 * * * *'],
]);
