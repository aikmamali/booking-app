<?php

declare(strict_types=1);

namespace App\Modules\Booking\Contracts;

final readonly class RosterEntry
{
    public function __construct(
        public int $bookingId,
        public int $userId,
        public string $name,
        public string $status,
    ) {}
}
