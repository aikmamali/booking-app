<?php

declare(strict_types=1);

namespace App\Modules\Booking\Rules;

use App\Modules\Booking\Exceptions\BookingRejected;

/**
 * Runs the booking rules in order (Chain of Responsibility) and stops at the first failure.
 */
final readonly class BookingRuleChain
{
    /**
     * @param  list<BookingRule>  $rules
     */
    public function __construct(private array $rules) {}

    /**
     * @param  bool  $override  staff override (BK-07): overridable failures are skipped
     */
    public function assertPasses(BookingContext $context, bool $override = false): void
    {
        $failure = $this->firstFailure($context, $override);

        if ($failure instanceof RuleResult) {
            throw BookingRejected::because((string) $failure->reason);
        }
    }

    public function firstFailure(BookingContext $context, bool $override = false): ?RuleResult
    {
        foreach ($this->rules as $rule) {
            $result = $rule->check($context);

            if (! $result->passed && (! $override || ! $result->overridable)) {
                return $result;
            }
        }

        return null;
    }
}
