<?php

declare(strict_types=1);

namespace App\Modules\Booking\Listeners;

use App\Modules\Booking\Actions\BookActiveFixedSeats;

final readonly class BookFixedSeatsForNewSessions
{
    public function __construct(private BookActiveFixedSeats $book) {}

    public function handle(): void
    {
        $this->book->handle();
    }
}
