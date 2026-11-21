<?php

declare(strict_types=1);

namespace App\Modules\Booking\Rules;

/**
 * GDPR and questionnaires that must be signed before booking (CM-06, phase 3).
 */
final class RequiredFormsCompleted implements BookingRule
{
    public function check(BookingContext $context): RuleResult
    {
        return $context->formsCompleted ? RuleResult::pass() : RuleResult::fail('booking.forms_missing', overridable: true);
    }
}
