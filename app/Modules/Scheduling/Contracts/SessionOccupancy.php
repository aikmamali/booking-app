<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Contracts;

/**
 * How many seats of a session are taken. Implemented by the Booking module; until then the
 * Scheduling module binds NoOccupancy. Lets Scheduling refuse to shrink a session below
 * its bookings without reading Booking tables.
 */
interface SessionOccupancy
{
    public function takenSeats(int $sessionId): int;
}
