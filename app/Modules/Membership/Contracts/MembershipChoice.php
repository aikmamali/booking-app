<?php

declare(strict_types=1);

namespace App\Modules\Membership\Contracts;

/**
 * Outcome of choosing which membership pays for a session. When none qualifies, the reason
 * tells the booking rule which message to show (booking.no_membership or
 * booking.insufficient_credits).
 */
final readonly class MembershipChoice
{
    private function __construct(
        public ?int $membershipId,
        public ?string $rejection,
    ) {}

    public static function found(int $membershipId): self
    {
        return new self($membershipId, null);
    }

    public static function noMembership(): self
    {
        return new self(null, 'booking.no_membership');
    }

    public static function insufficientCredits(): self
    {
        return new self(null, 'booking.insufficient_credits');
    }

    public function isFound(): bool
    {
        return $this->membershipId !== null;
    }
}
