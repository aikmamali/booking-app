<?php

declare(strict_types=1);

namespace App\Modules\Booking\Rules;

/**
 * One booking check (spec section 4). A new rule is a new class plus one line in
 * BookingServiceProvider; existing rules do not change.
 */
interface BookingRule
{
    public function check(BookingContext $context): RuleResult;
}
