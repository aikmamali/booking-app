<?php

declare(strict_types=1);

namespace App\Shared;

use Carbon\CarbonImmutable;

/**
 * Converts between wall-clock time in the business time zone and stored UTC instants.
 * A schedule says "Tuesday 18:30"; that is 16:30 UTC in winter and 15:30 UTC in summer.
 * The zone comes from BookingPolicySettings (bound in AppServiceProvider).
 */
final readonly class BusinessTime
{
    public function __construct(
        private string $timezone,
        private Clock $clock,
    ) {}

    public function timezone(): string
    {
        return $this->timezone;
    }

    /**
     * @param  string  $date  Y-m-d in the business time zone
     * @param  string  $time  H:i or H:i:s in the business time zone
     */
    public function toUtc(string $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::parse($date.' '.$time, $this->timezone)->utc();
    }

    public function localDate(CarbonImmutable $instant): CarbonImmutable
    {
        return $instant->setTimezone($this->timezone)->startOfDay();
    }

    public function toLocal(CarbonImmutable $instant): CarbonImmutable
    {
        return $instant->setTimezone($this->timezone);
    }

    /**
     * Today's date in the business time zone, as Y-m-d.
     */
    public function today(): string
    {
        return $this->localDate($this->clock->now())->format('Y-m-d');
    }
}
