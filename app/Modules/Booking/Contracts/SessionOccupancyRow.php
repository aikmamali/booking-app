<?php

declare(strict_types=1);

namespace App\Modules\Booking\Contracts;

use Carbon\CarbonImmutable;

final readonly class SessionOccupancyRow
{
    public function __construct(
        public CarbonImmutable $startsAt,
        public string $serviceName,
        public string $instructorName,
        public int $booked,
        public int $capacity,
        public int $waiting,
        public bool $cancelled,
    ) {}

    public function percent(): int
    {
        return $this->capacity === 0 ? 0 : (int) round($this->booked * 100 / $this->capacity);
    }
}
