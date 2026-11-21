<?php

declare(strict_types=1);

namespace App\Modules\Booking\Support;

use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Settings\BookingPolicySettings;
use Carbon\CarbonImmutable;

/**
 * BK-04: what happens if this booking is cancelled now. The customer screen shows it
 * before confirmation; CancelBooking applies the same answer.
 */
final readonly class CancellationTerms
{
    private function __construct(
        public bool $started,
        public bool $withinCutoff,
        public bool $lateCancelAllowed,
        public CarbonImmutable $deadline,
    ) {}

    public static function for(Booking $booking, CarbonImmutable $now, BookingPolicySettings $settings): self
    {
        $session = $booking->session;
        $policy = EffectivePolicy::for($session->service, $settings);
        $deadline = $session->starts_at->subMinutes($policy->cancellationCutoffMinutes);

        return new self(
            started: $session->starts_at->lte($now),
            withinCutoff: $now->lte($deadline),
            lateCancelAllowed: $policy->lateCancelAllowed,
            deadline: $deadline,
        );
    }

    /** The customer may cancel or reschedule themselves. */
    public function customerMayCancel(): bool
    {
        return ! $this->started && ($this->withinCutoff || $this->lateCancelAllowed);
    }

    public function customerMayReschedule(): bool
    {
        return ! $this->started && $this->withinCutoff;
    }

    public function refundsCredit(): bool
    {
        return $this->withinCutoff;
    }
}
