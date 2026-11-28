<?php

declare(strict_types=1);

use App\Modules\Booking\Actions\ApproveFixedSeat;
use App\Modules\Booking\Actions\CancelBooking;
use App\Modules\Booking\Actions\EndFinishedSeasons;
use App\Modules\Booking\Actions\ExpirePendingBookings;
use App\Modules\Booking\Actions\MarkAttendance;
use App\Modules\Booking\Actions\RejectFixedSeat;
use App\Modules\Booking\Actions\RequestFixedSeat;
use App\Modules\Booking\Actions\RevokeFixedSeat;
use App\Modules\Booking\Actions\SaveSeason;
use App\Modules\Booking\Data\CancellationRequest;
use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Enums\FixedSeatStatus;
use App\Modules\Booking\Events\FixedSeatAwaitingCredits;
use App\Modules\Booking\Events\FixedSeatRevoked;
use App\Modules\Booking\Events\FixedSeatWarning;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Filament\Resources\FixedSeatResource\Pages\ListFixedSeats;
use App\Modules\Booking\Livewire\MyFixedSeats;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\FixedSeat;
use App\Modules\Booking\Models\Season;
use App\Modules\Communication\Enums\NotificationType;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Actions\GenerateSessions;
use App\Modules\Scheduling\Models\Schedule;
use App\Shared\Credits;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

// Saturday 20/3/2027, 10:00 Athens. Season March–April; Tuesday 18:30 class.
// Generated Tuesdays inside the season: 23/3, 30/3, 6/4, 13/4, 20/4, 27/4 (six).
beforeEach(function (): void {
    $this->travelTo('2027-03-20 08:00:00');
    $this->admin = User::factory()->admin()->create();
    $this->season = app(SaveSeason::class)->handle('Άνοιξη 2027', '2027-03-01', '2027-04-30');
    $this->schedule = tuesdayClass(['valid_from' => '2027-03-01', 'capacity' => 2]);
    $this->schedule->service->forceFill(['credit_cost' => Credits::of(1)])->save();
    app(GenerateSessions::class)->handle();
});

function seatCustomer(Schedule $schedule, string $credits = '10'): User
{
    $customer = User::factory()->customer()->create();

    if ($credits !== '0') {
        soldMembership($customer, ['credits' => Credits::of($credits)], '2027-03-20', $schedule->service);
    }

    return $customer;
}

function activeSeat(User $customer, Schedule $schedule, Season $season, User $admin): FixedSeat
{
    $seat = app(RequestFixedSeat::class)->handle($customer->id, $schedule->id, $season->id);

    return app(ApproveFixedSeat::class)->handle($seat, $admin->id);
}

function seatBookings(FixedSeat $seat, ?BookingStatus $status = null): int
{
    return Booking::query()->where('fixed_seat_id', $seat->id)->when($status, fn ($q) => $q->where('status', $status))->count();
}

describe('seasons', function (): void {
    it('refuses overlapping or inverted seasons', function (): void {
        expect(fn () => app(SaveSeason::class)->handle('Β', '2027-04-15', '2027-06-30'))->toThrow(BookingRejected::class, 'booking.seasons.overlap')
            ->and(fn () => app(SaveSeason::class)->handle('Γ', '2027-09-30', '2027-09-01'))->toThrow(BookingRejected::class, 'booking.seasons.invalid');
    });
});

describe('requests', function (): void {
    it('lets a customer request a seat once, within capacity', function (): void {
        $customer = seatCustomer($this->schedule);
        $request = app(RequestFixedSeat::class);

        $seat = $request->handle($customer->id, $this->schedule->id, $this->season->id);

        expect($seat->status)->toBe(FixedSeatStatus::Requested)
            ->and(fn () => $request->handle($customer->id, $this->schedule->id, $this->season->id))->toThrow(BookingRejected::class, 'booking.fixed_seats.duplicate');

        $request->handle(seatCustomer($this->schedule)->id, $this->schedule->id, $this->season->id);
        expect(fn () => $request->handle(seatCustomer($this->schedule)->id, $this->schedule->id, $this->season->id))
            ->toThrow(BookingRejected::class, 'booking.fixed_seats.full');
    });

    it('refuses finished seasons and classes outside the season', function (): void {
        $old = app(SaveSeason::class)->handle('Χειμώνας', '2026-10-01', '2027-02-28');
        $later = tuesdayClass(['valid_from' => '2027-06-01']);
        $customer = seatCustomer($this->schedule);

        expect(fn () => app(RequestFixedSeat::class)->handle($customer->id, $this->schedule->id, $old->id))->toThrow(BookingRejected::class, 'booking.fixed_seats.season_over')
            ->and(fn () => app(RequestFixedSeat::class)->handle($customer->id, $later->id, $this->season->id))->toThrow(BookingRejected::class, 'booking.fixed_seats.not_in_season');
    });

    it('requires a reason to reject', function (): void {
        $seat = app(RequestFixedSeat::class)->handle(seatCustomer($this->schedule)->id, $this->schedule->id, $this->season->id);

        expect(fn () => app(RejectFixedSeat::class)->handle($seat, ' ', $this->admin->id))->toThrow(BookingRejected::class);

        $seat = app(RejectFixedSeat::class)->handle($seat, 'Γεμάτο', $this->admin->id);
        expect($seat->status)->toBe(FixedSeatStatus::Revoked)->and($seat->revoked_reason)->toBe('Γεμάτο');
    });
});

describe('bookings', function (): void {
    it('books every class of the season on approval, before the booking window opens', function (): void {
        $customer = seatCustomer($this->schedule);

        $seat = activeSeat($customer, $this->schedule, $this->season, $this->admin);

        // 27/4 is 38 days ahead, far beyond the 14-day booking window.
        expect(seatBookings($seat, BookingStatus::Confirmed))->toBe(6)
            ->and(Booking::query()->where('fixed_seat_id', $seat->id)->pluck('source')->unique()->all())->toBe([BookingSource::FixedSeat])
            ->and(creditsOf($customer))->toBe('4.00');
    });

    it('books classes generated later, and never twice', function (): void {
        $season = app(SaveSeason::class)->handle('Καλοκαίρι', '2027-05-01', '2027-07-31');
        $customer = seatCustomer($this->schedule, '30');
        $seat = activeSeat($customer, $this->schedule, $season, $this->admin);
        $before = seatBookings($seat);

        $this->travelTo('2027-03-27 08:00:00');
        app(GenerateSessions::class)->handle();

        expect(seatBookings($seat))->toBe($before + 1);
        app(GenerateSessions::class)->handle();
        expect(seatBookings($seat))->toBe($before + 1);
    });

    it('holds places without credits and confirms them after a purchase', function (): void {
        Event::fake([FixedSeatAwaitingCredits::class]);
        $customer = seatCustomer($this->schedule, '2');

        $seat = activeSeat($customer, $this->schedule, $this->season, $this->admin);

        expect(seatBookings($seat, BookingStatus::Confirmed))->toBe(2)
            ->and(seatBookings($seat, BookingStatus::PendingCredits))->toBe(4);
        $held = Booking::query()->where('fixed_seat_id', $seat->id)->where('status', BookingStatus::PendingCredits)->first();
        expect($held?->hold_until?->toDateTimeString())->toBe('2027-03-21 08:00:00');
        Event::assertDispatchedTimes(FixedSeatAwaitingCredits::class, 4);

        soldMembership($customer, ['credits' => Credits::of(3)], '2027-03-20', $this->schedule->service);

        expect(seatBookings($seat, BookingStatus::Confirmed))->toBe(5)
            ->and(seatBookings($seat, BookingStatus::PendingCredits))->toBe(1);
    });

    it('expires held places after the hold and frees the seat', function (): void {
        $seat = activeSeat(seatCustomer($this->schedule, '0'), $this->schedule, $this->season, $this->admin);

        $this->travelTo('2027-03-21 07:59:00');
        expect(app(ExpirePendingBookings::class)->handle())->toBe(0);

        $this->travelTo('2027-03-21 08:00:00');
        expect(app(ExpirePendingBookings::class)->handle())->toBe(6)
            ->and(seatBookings($seat, BookingStatus::Expired))->toBe(6)
            ->and(app(ExpirePendingBookings::class)->handle())->toBe(0);
    });
});

describe('cancellations in a row (FS-04)', function (): void {
    beforeEach(function (): void {
        $this->customer = seatCustomer($this->schedule);
        $this->seat = activeSeat($this->customer, $this->schedule, $this->season, $this->admin);
        $this->bookings = Booking::query()->where('fixed_seat_id', $this->seat->id)->with('session')->get()
            ->sortBy(fn (Booking $b) => $b->session->starts_at)->values();
    });

    function cancelOwn(Booking $booking): void
    {
        app(CancelBooking::class)->handle($booking, new CancellationRequest(actor_id: $booking->user_id));
    }

    it('warns one cancellation before the limit and revokes at the limit', function (): void {
        Event::fake([FixedSeatWarning::class]);

        cancelOwn($this->bookings[0]);
        cancelOwn($this->bookings[1]);
        Event::assertNotDispatched(FixedSeatWarning::class);
        cancelOwn($this->bookings[2]);
        Event::assertDispatched(FixedSeatWarning::class);
        expect($this->seat->refresh()->consecutive_cancellations)->toBe(3);

        cancelOwn($this->bookings[3]);

        $this->seat->refresh();
        expect($this->seat->status)->toBe(FixedSeatStatus::Revoked)
            ->and($this->seat->revoked_reason)->toBe(__('booking.fixed_seats.auto_revoked', ['count' => 4]))
            ->and(seatBookings($this->seat, BookingStatus::Confirmed))->toBe(0)
            ->and(creditsOf($this->customer))->toBe('10.00');
    });

    it('starts counting again after an attended class', function (): void {
        cancelOwn($this->bookings[0]);
        cancelOwn($this->bookings[1]);
        $this->travelTo('2027-04-06 17:00:00'); // after the 6/4 class
        app(MarkAttendance::class)->handle($this->bookings[2], BookingStatus::Attended, $this->admin->id);
        expect($this->seat->refresh()->consecutive_cancellations)->toBe(0);

        cancelOwn($this->bookings[3]);
        cancelOwn($this->bookings[4]);
        cancelOwn($this->bookings[5]);

        expect($this->seat->refresh()->status)->toBe(FixedSeatStatus::Active)
            ->and($this->seat->consecutive_cancellations)->toBe(3);
    });

    it('lets staff revoke with a reason, refunding the future classes', function (): void {
        Event::fake([FixedSeatRevoked::class]);

        app(RevokeFixedSeat::class)->handle($this->seat, 'Μετακόμισε', $this->admin->id);

        expect(seatBookings($this->seat, BookingStatus::Cancelled))->toBe(6)
            ->and(creditsOf($this->customer))->toBe('10.00');
        Event::assertDispatched(FixedSeatRevoked::class);
    });
});

it('ends seats when their season is over', function (): void {
    $seat = activeSeat(seatCustomer($this->schedule), $this->schedule, $this->season, $this->admin);

    $this->travelTo('2027-04-30 20:00:00'); // 23:00 Athens on the last day
    expect(app(EndFinishedSeasons::class)->handle())->toBe(0);

    $this->travelTo('2027-04-30 21:30:00'); // 00:30 Athens the next day
    expect(app(EndFinishedSeasons::class)->handle())->toBe(1)
        ->and($seat->refresh()->status)->toBe(FixedSeatStatus::Ended);
});

describe('screens', function (): void {
    it('lets the customer request and withdraw a seat', function (): void {
        $customer = seatCustomer($this->schedule);
        $this->actingAs($customer);

        Livewire::test(MyFixedSeats::class)
            ->assertSet('seasonId', $this->season->id)
            ->set('scheduleId', $this->schedule->id)
            ->call('request')
            ->assertHasNoErrors();

        $seat = FixedSeat::query()->sole();
        Livewire::test(MyFixedSeats::class)
            ->assertSee(__('booking.fixed_seats.status.requested'))
            ->call('leave', $seat->public_id);

        expect($seat->refresh()->status)->toBe(FixedSeatStatus::Ended);
        $this->get(route('app.fixed-seats'))->assertOk();
    });

    it('lets staff approve a request from the list', function (): void {
        $seat = app(RequestFixedSeat::class)->handle(seatCustomer($this->schedule)->id, $this->schedule->id, $this->season->id);
        $this->actingAs($this->admin);

        Livewire::test(ListFixedSeats::class)
            ->assertCanSeeTableRecords([$seat])
            ->callTableAction('approve', $seat)
            ->assertHasNoTableActionErrors();

        expect($seat->refresh()->status)->toBe(FixedSeatStatus::Active)
            ->and(seatBookings($seat))->toBe(6);
    });

    it('lets staff assign a seat directly', function (): void {
        $customer = seatCustomer($this->schedule);
        $this->actingAs($this->admin);

        Livewire::test(ListFixedSeats::class)
            ->callAction('assign', ['user_id' => $customer->id, 'season_id' => $this->season->id, 'schedule_id' => $this->schedule->id])
            ->assertHasNoActionErrors();

        expect(FixedSeat::query()->sole()->status)->toBe(FixedSeatStatus::Active);
    });
});

describe('notifications', function (): void {
    it('tells the customer about approval, held places and revocation', function (): void {
        Notification::fake();
        $customer = seatCustomer($this->schedule, '5');

        $seat = activeSeat($customer, $this->schedule, $this->season, $this->admin);
        app(RevokeFixedSeat::class)->handle($seat, 'Μετακόμισε', $this->admin->id);

        assertNotified($customer, NotificationType::FixedSeatApproved,
            check: fn ($n): bool => $n->replace['weekday'] === __('scheduling.weekdays.2') && $n->replace['time'] === '18:30');
        assertNotified($customer, NotificationType::FixedSeatAwaitingCredits);
        assertNotified($customer, NotificationType::FixedSeatRevoked,
            check: fn ($n): bool => $n->replace['reason'] === 'Μετακόμισε');
    });
});
