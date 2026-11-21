<?php

declare(strict_types=1);

namespace App\Modules\Booking\Support;

use App\Modules\Booking\Contracts\RequiredForms;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Rules\BookingContext;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Membership\Contracts\MembershipSelector;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\BusinessTime;
use App\Shared\Clock;
use Illuminate\Database\Eloquent\Builder;

/**
 * Gathers the facts the booking rules need, so the rules themselves stay pure.
 */
final readonly class BookingContextFactory
{
    public function __construct(
        private Clock $clock,
        private BusinessTime $time,
        private BookingPolicySettings $settings,
        private MembershipSelector $memberships,
        private RequiredForms $forms,
    ) {}

    /**
     * @param  ?int  $ignoreBookingId  the booking being moved by a reschedule
     */
    public function make(int $userId, ClassSession $session, ?int $ignoreBookingId = null): BookingContext
    {
        $now = $this->clock->now();
        $localDate = $this->time->localDate($session->starts_at)->format('Y-m-d');
        $mine = fn () => Booking::query()->where('user_id', $userId)->whereKeyNot($ignoreBookingId ?? 0);

        return new BookingContext(
            userId: $userId,
            session: $session,
            now: $now,
            policy: EffectivePolicy::for($session->service, $this->settings),
            membership: $this->memberships->choose($userId, $session->service_id, $session->service->credit_cost, $localDate),
            takenSeats: SeatCounter::taken($session->id, $ignoreBookingId),
            alreadyBooked: $mine()->active()->where('class_session_id', $session->id)->exists(),
            bookingsThatDay: $this->bookingsOn($mine(), $localDate),
            activeBookings: $mine()->active()->whereHas('session', fn ($q) => $q->where('starts_at', '>', $now))->count(),
            formsCompleted: $this->forms->completedBy($userId),
        );
    }

    /**
     * @param  Builder<Booking>  $query
     */
    private function bookingsOn($query, string $localDate): int
    {
        $from = $this->time->toUtc($localDate, '00:00');

        return $query
            ->whereIn('status', ['confirmed', 'pending_payment', 'pending_credits', 'attended', 'no_show'])
            ->whereHas('session', fn ($q) => $q->where('starts_at', '>=', $from)->where('starts_at', '<', $from->addDay()))
            ->count();
    }
}
