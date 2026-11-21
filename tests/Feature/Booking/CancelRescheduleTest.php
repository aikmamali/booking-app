<?php

declare(strict_types=1);

use App\Modules\Booking\Actions\CancelBooking;
use App\Modules\Booking\Actions\RescheduleBooking;
use App\Modules\Booking\Data\CancellationRequest;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\BookingCancelled;
use App\Modules\Booking\Events\BookingRescheduled;
use App\Modules\Booking\Events\SeatFreed;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Communication\Notifications\BookingCancelledNotification;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Models\ClassSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->travelTo('2027-03-20 08:00:00');
    $this->session = bookableSession(); // 23 March 16:30 UTC
    $this->customer = customerFor($this->session);
    $this->booking = book($this->customer, $this->session);
});

function cancelAs(Booking $booking, array $request = []): Booking
{
    return app(CancelBooking::class)->handle($booking, new CancellationRequest(...$request));
}

it('refunds up to and including the cut-off (180 minutes)', function (int $minutesBefore, bool $refunded): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-23 16:30:00')->subMinutes($minutesBefore));
    Notification::fake();

    $cancelled = cancelAs($this->booking, ['actor_id' => $this->customer->id, 'reason' => 'Δουλειά']);

    expect($cancelled->status)->toBe(BookingStatus::Cancelled)
        ->and($cancelled->late_cancel)->toBeFalse()
        ->and($cancelled->cancel_reason)->toBe('Δουλειά')
        ->and(creditsOf($this->customer))->toBe('10.00');
    Notification::assertSentTo($this->customer, BookingCancelledNotification::class);
})->with([
    '181 minutes before' => [181, true],
    '180 minutes before' => [180, true],
]);

it('stops customers cancelling after the cut-off (179 minutes)', function (): void {
    $this->travelTo('2027-03-23 13:31:00');

    expect(fn (): Booking => cancelAs($this->booking, ['actor_id' => $this->customer->id]))->toThrow(BookingRejected::class, 'booking.cancel_too_late');
    expect($this->booking->fresh()?->status)->toBe(BookingStatus::Confirmed)
        ->and(creditsOf($this->customer))->toBe('9.00');
});

it('allows a late cancellation without refund when the service permits it', function (): void {
    $settings = app(BookingPolicySettings::class);
    $settings->late_cancel_allowed = true;
    $settings->save();
    $this->travelTo('2027-03-23 15:00:00');

    $cancelled = cancelAs($this->booking, ['actor_id' => $this->customer->id]);

    expect($cancelled->late_cancel)->toBeTrue()->and(creditsOf($this->customer))->toBe('9.00');
});

it('lets staff cancel late, refunding only with an override reason', function (): void {
    $admin = User::factory()->admin()->create();
    $other = book(customerFor($this->session), $this->session);
    $this->travelTo('2027-03-23 15:00:00');

    cancelAs($this->booking, ['actor_id' => $admin->id, 'by_staff' => true]);
    cancelAs($other, ['actor_id' => $admin->id, 'by_staff' => true, 'refund_override_reason' => 'Ασθένεια']);

    expect($this->booking->fresh()?->late_cancel)->toBeTrue()
        ->and(creditsOf($this->customer))->toBe('9.00')
        ->and(creditsOf($other->user))->toBe('10.00')
        ->and(Activity::query()->forSubject($other)->where('event', 'refund_override')->exists())->toBeTrue();
});

it('does not treat a customer as staff', function (): void {
    cancelAs($this->booking, ['actor_id' => $this->customer->id, 'by_staff' => true]);
})->throws(BookingRejected::class, 'booking.override_not_allowed');

it('refuses to cancel twice or after the start', function (): void {
    cancelAs($this->booking, ['actor_id' => $this->customer->id]);

    expect(fn (): Booking => cancelAs($this->booking, ['actor_id' => $this->customer->id]))->toThrow(BookingRejected::class, 'booking.not_cancellable');

    $second = book($this->customer, $this->session);
    $this->travelTo('2027-03-23 16:31:00');
    expect(fn (): Booking => cancelAs($second, ['actor_id' => User::factory()->admin()->create()->id, 'by_staff' => true]))
        ->toThrow(BookingRejected::class, 'booking.already_started');
});

it('frees the seat after commit', function (): void {
    Event::fake([SeatFreed::class, BookingCancelled::class]);

    cancelAs($this->booking, ['actor_id' => $this->customer->id]);

    Event::assertDispatched(SeatFreed::class, fn (SeatFreed $e): bool => $e->sessionId === $this->session->id);
    Event::assertDispatched(BookingCancelled::class, fn (BookingCancelled $e): bool => $e->refunded && ! $e->bySession);
});

describe('reschedule', function (): void {
    beforeEach(fn (): ClassSession => $this->target = bookableSession(['starts_at' => '2027-03-25 16:30:00', 'ends_at' => '2027-03-25 17:30:00'], $this->session->service));

    it('moves the booking in one step without charging twice', function (): void {
        Event::fake([BookingRescheduled::class, SeatFreed::class, BookingCancelled::class]);

        $moved = app(RescheduleBooking::class)->handle($this->booking, $this->target->id, $this->customer->id);

        $old = $this->booking->fresh();
        expect($moved->class_session_id)->toBe($this->target->id)
            ->and($moved->status)->toBe(BookingStatus::Confirmed)
            ->and($old?->status)->toBe(BookingStatus::Cancelled)
            ->and($old?->cancel_reason)->toBe('rescheduled')
            ->and($old?->rescheduled_to_id)->toBe($moved->id)
            ->and($old?->late_cancel)->toBeFalse()
            ->and(creditsOf($this->customer))->toBe('9.00');
        Event::assertDispatched(BookingRescheduled::class);
        Event::assertNotDispatched(BookingCancelled::class);
    });

    it('keeps the cut-off of a refunded cancellation', function (): void {
        $this->travelTo('2027-03-23 13:31:00');

        app(RescheduleBooking::class)->handle($this->booking, $this->target->id, $this->customer->id);
    })->throws(BookingRejected::class, 'booking.reschedule_too_late');

    it('changes nothing when the new session is full', function (): void {
        $this->target->update(['capacity' => 0]);

        expect(fn () => app(RescheduleBooking::class)->handle($this->booking, $this->target->id, $this->customer->id))
            ->toThrow(BookingRejected::class, 'booking.full');
        expect($this->booking->fresh()?->status)->toBe(BookingStatus::Confirmed)
            ->and(creditsOf($this->customer))->toBe('9.00')
            ->and(Booking::query()->count())->toBe(1);
    });

    it('moves within the same day despite the daily limit', function (): void {
        $sameDay = bookableSession(['starts_at' => '2027-03-23 06:00:00', 'ends_at' => '2027-03-23 07:00:00'], $this->session->service);

        expect(app(RescheduleBooking::class)->handle($this->booking, $sameDay->id, $this->customer->id)->class_session_id)->toBe($sameDay->id);
    });

    it('refuses the same session', function (): void {
        app(RescheduleBooking::class)->handle($this->booking, $this->session->id, $this->customer->id);
    })->throws(BookingRejected::class, 'booking.duplicate');
});
