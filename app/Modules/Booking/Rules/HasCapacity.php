<?php

declare(strict_types=1);

namespace App\Modules\Booking\Rules;

final class HasCapacity implements BookingRule
{
    public function check(BookingContext $context): RuleResult
    {
        return $context->takenSeats < $context->session->capacity
            ? RuleResult::pass()
            : RuleResult::fail('booking.full');
    }
}
