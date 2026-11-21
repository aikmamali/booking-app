<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Support;

use App\Modules\Booking\Settings\BookingPolicySettings;
use Carbon\CarbonImmutable;

/**
 * Converts between wall-clock time in the business time zone and stored UTC instants.
 * Schedules say "Tuesday 18:30"; that is 16:30 UTC in winter and 15:30 UTC in summer.
 */
final readonly class BusinessTime
{
    public function __construct(private BookingPolicySettings $settings) {}

    public function timezone(): string
    {
        return $this->settings->timezone;
    }

    /**
     * @param  string  $date  Y-m-d in the business time zone
     * @param  string  $time  H:i or H:i:s in the business time zone
     */
    public function toUtc(string $date, string $time): CarbonImmutable
    {
        return CarbonImmutable::parse($date.' '.$time, $this->timezone())->utc();
    }

    public function localDate(CarbonImmutable $instant): CarbonImmutable
    {
        return $instant->setTimezone($this->timezone())->startOfDay();
    }

    public function toLocal(CarbonImmutable $instant): CarbonImmutable
    {
        return $instant->setTimezone($this->timezone());
    }
}
