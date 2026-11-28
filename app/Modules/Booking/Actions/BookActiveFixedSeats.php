<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\FixedSeatStatus;
use App\Modules\Booking\Models\FixedSeat;

/**
 * FS-03: runs after GenerateSessions, so new classes get their fixed-seat bookings before
 * they open to everyone else (spec 7.4).
 */
final readonly class BookActiveFixedSeats
{
    public function __construct(private BookFixedSeat $book) {}

    public function handle(): int
    {
        $made = 0;

        foreach (FixedSeat::query()->with('season')->where('status', FixedSeatStatus::Active)->get() as $seat) {
            $made += $this->book->handle($seat);
        }

        return $made;
    }
}
