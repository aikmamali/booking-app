<?php

declare(strict_types=1);

namespace App\Modules\Booking\Support;

use App\Modules\Booking\Enums\WaitlistStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\WaitlistEntry;
use App\Modules\Scheduling\Contracts\SessionOccupancy;
use App\Shared\Clock;

/**
 * Seats held by active bookings plus seats reserved by open waitlist offers. Also serves
 * Scheduling through SessionOccupancy, so a session can never shrink below them.
 */
final class SeatCounter implements SessionOccupancy
{
    /**
     * @param  ?int  $ignoreBookingId  the booking being moved by a reschedule
     * @param  ?int  $forUserId  a customer accepting their own offer does not compete with it
     */
    public static function taken(int $sessionId, ?int $ignoreBookingId = null, ?int $forUserId = null): int
    {
        $bookings = Booking::query()
            ->active()
            ->where('class_session_id', $sessionId)
            ->when($ignoreBookingId !== null, fn ($q) => $q->whereKeyNot($ignoreBookingId))
            ->count();

        return $bookings + self::heldByOffers($sessionId, $forUserId);
    }

    public static function heldByOffers(int $sessionId, ?int $exceptUserId = null): int
    {
        return WaitlistEntry::query()
            ->where('class_session_id', $sessionId)
            ->where('status', WaitlistStatus::Offered)
            ->where('offered_until', '>', app(Clock::class)->now())
            ->when($exceptUserId !== null, fn ($q) => $q->where('user_id', '!=', $exceptUserId))
            ->count();
    }

    public function takenSeats(int $sessionId): int
    {
        return self::taken($sessionId);
    }
}
