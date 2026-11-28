<?php

declare(strict_types=1);

namespace App\Modules\Booking\Contracts;

final readonly class FixedSeatSummary
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $serviceName,
        public string $weekday,
        public string $time,
        public string $season,
        public ?string $reason,
    ) {}
}
