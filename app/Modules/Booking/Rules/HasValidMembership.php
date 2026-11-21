<?php

declare(strict_types=1);

namespace App\Modules\Booking\Rules;

/**
 * A membership that covers the service, is active on the session date and has enough
 * credits (chosen FEFO by the Membership module).
 */
final class HasValidMembership implements BookingRule
{
    public function check(BookingContext $context): RuleResult
    {
        return $context->membership->isFound()
            ? RuleResult::pass()
            : RuleResult::fail($context->membership->rejection ?? 'booking.no_membership');
    }
}
