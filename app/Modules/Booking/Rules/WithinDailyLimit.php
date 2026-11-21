<?php

declare(strict_types=1);

namespace App\Modules\Booking\Rules;

/**
 * max_bookings_per_day, counted on the local date of the session.
 */
final class WithinDailyLimit implements BookingRule
{
    public function check(BookingContext $context): RuleResult
    {
        return $context->bookingsThatDay < $context->policy->maxBookingsPerDay
            ? RuleResult::pass()
            : RuleResult::fail('booking.daily_limit', overridable: true);
    }
}
