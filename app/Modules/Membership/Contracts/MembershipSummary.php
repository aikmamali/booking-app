<?php

declare(strict_types=1);

namespace App\Modules\Membership\Contracts;

use App\Shared\Credits;
use Carbon\CarbonImmutable;

/**
 * Read-only view of a membership for other modules (Communication's emails).
 */
final readonly class MembershipSummary
{
    public function __construct(
        public int $userId,
        public string $packageName,
        public CarbonImmutable $endsOn,
        public Credits $balance,
    ) {}
}
