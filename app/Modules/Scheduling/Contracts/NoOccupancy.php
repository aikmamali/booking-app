<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Contracts;

/**
 * Placeholder until the Booking module provides real occupancy (MVP step 4).
 */
final class NoOccupancy implements SessionOccupancy
{
    public function takenSeats(int $sessionId): int
    {
        return 0;
    }
}
