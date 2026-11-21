<?php

declare(strict_types=1);

namespace App\Modules\Booking\Rules;

final readonly class RuleResult
{
    private function __construct(
        public bool $passed,
        public ?string $reason,
        public bool $overridable,
    ) {}

    public static function pass(): self
    {
        return new self(true, null, false);
    }

    /**
     * @param  string  $reason  translation key, e.g. booking.full
     * @param  bool  $overridable  staff may bypass it with a reason (BK-07)
     */
    public static function fail(string $reason, bool $overridable = false): self
    {
        return new self(false, $reason, $overridable);
    }
}
