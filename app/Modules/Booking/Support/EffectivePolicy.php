<?php

declare(strict_types=1);

namespace App\Modules\Booking\Support;

use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Catalog\Data\PolicyOverrides;
use App\Modules\Catalog\Models\Service;

/**
 * The booking policy for one service: the service's overrides where set, the global
 * BookingPolicySettings otherwise (spec section 4, "per service" column).
 */
final readonly class EffectivePolicy
{
    public function __construct(
        public int $cancellationCutoffMinutes,
        public bool $lateCancelAllowed,
        public int $opensDaysAhead,
        public int $closesMinutesBefore,
        public int $maxBookingsPerDay,
        public ?int $maxActiveBookings,
    ) {}

    public static function for(Service $service, BookingPolicySettings $settings): self
    {
        /** @var PolicyOverrides|null $o */
        $o = $service->policy_overrides;

        return new self(
            cancellationCutoffMinutes: $o->cancellation_cutoff_minutes ?? $settings->cancellation_cutoff_minutes,
            lateCancelAllowed: $o->late_cancel_allowed ?? $settings->late_cancel_allowed,
            opensDaysAhead: $o->booking_opens_days_ahead ?? $settings->booking_opens_days_ahead,
            closesMinutesBefore: $o->booking_closes_minutes_before ?? $settings->booking_closes_minutes_before,
            maxBookingsPerDay: $settings->max_bookings_per_day,
            maxActiveBookings: $settings->max_active_bookings,
        );
    }
}
