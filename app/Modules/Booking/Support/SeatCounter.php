<?php

declare(strict_types=1);

namespace App\Modules\Booking\Support;

use App\Modules\Booking\Models\Booking;
use App\Modules\Scheduling\Contracts\SessionOccupancy;

/**
 * Seats held by active bookings. Also serves Scheduling through SessionOccupancy, so a
 * session can never be shrunk below its bookings.
 */
final class SeatCounter implements SessionOccupancy
{
    public static function taken(int $sessionId, ?int $ignoreBookingId = null): int
    {
        return Booking::query()
            ->active()
            ->where('class_session_id', $sessionId)
            ->when($ignoreBookingId !== null, fn ($q) => $q->whereKeyNot($ignoreBookingId))
            ->count();
    }

    public function takenSeats(int $sessionId): int
    {
        return self::taken($sessionId);
    }
}
