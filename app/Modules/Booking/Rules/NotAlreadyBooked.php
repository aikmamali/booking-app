<?php

declare(strict_types=1);

namespace App\Modules\Booking\Rules;

final class NotAlreadyBooked implements BookingRule
{
    public function check(BookingContext $context): RuleResult
    {
        return $context->alreadyBooked ? RuleResult::fail('booking.duplicate') : RuleResult::pass();
    }
}
