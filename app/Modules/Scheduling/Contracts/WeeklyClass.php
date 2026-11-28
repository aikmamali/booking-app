<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Contracts;

use Carbon\CarbonImmutable;

/**
 * Read-only view of a weekly schedule for other modules (fixed seats, FS-02).
 */
final readonly class WeeklyClass
{
    public function __construct(
        public int $id,
        public int $serviceId,
        public string $serviceName,
        public string $weekday,
        public string $startTime,
        public string $instructorName,
        public int $capacity,
        public CarbonImmutable $validFrom,
        public ?CarbonImmutable $validUntil,
    ) {}

    public function label(): string
    {
        return $this->serviceName.' · '.$this->weekday.' '.substr($this->startTime, 0, 5).' · '.$this->instructorName;
    }

    public function runsDuring(string $from, string $to): bool
    {
        return $this->validFrom->toDateString() <= $to
            && (! $this->validUntil instanceof CarbonImmutable || $this->validUntil->toDateString() >= $from);
    }
}
