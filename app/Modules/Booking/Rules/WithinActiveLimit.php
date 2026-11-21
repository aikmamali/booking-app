<?php

declare(strict_types=1);

namespace App\Modules\Booking\Rules;

/**
 * max_active_bookings: future bookings a customer may hold at once (null = no limit).
 */
final class WithinActiveLimit implements BookingRule
{
    public function check(BookingContext $context): RuleResult
    {
        $max = $context->policy->maxActiveBookings;

        return $max === null || $context->activeBookings < $max
            ? RuleResult::pass()
            : RuleResult::fail('booking.active_limit', overridable: true);
    }
}
