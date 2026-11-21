<?php

declare(strict_types=1);

namespace App\Modules\Booking\Rules;

/**
 * Not cancelled, not started, and inside the booking window. Staff may book outside the
 * window, never into a cancelled or started session.
 */
final class SessionIsBookable implements BookingRule
{
    public function check(BookingContext $context): RuleResult
    {
        $session = $context->session;

        if ($session->isCancelled() || $session->starts_at->lte($context->now)) {
            return RuleResult::fail('booking.session_closed');
        }

        $opens = $session->starts_at->subDays($context->policy->opensDaysAhead);
        $closes = $session->starts_at->subMinutes($context->policy->closesMinutesBefore);

        if ($context->now->lt($opens) || $context->now->gte($closes)) {
            return RuleResult::fail('booking.session_closed', overridable: true);
        }

        return RuleResult::pass();
    }
}
