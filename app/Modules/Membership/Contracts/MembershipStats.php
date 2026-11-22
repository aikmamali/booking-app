<?php

declare(strict_types=1);

namespace App\Modules\Membership\Contracts;

use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;

/**
 * Public read contract of Membership for the Reporting dashboard.
 */
final class MembershipStats
{
    /**
     * Active memberships valid on the given local date.
     */
    public function activeOn(string $date): int
    {
        return Membership::query()->where('status', MembershipStatus::Active)
            ->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->count();
    }

    /**
     * Active memberships whose last day falls between two local dates (inclusive).
     */
    public function endingBetween(string $from, string $to): int
    {
        return Membership::query()->where('status', MembershipStatus::Active)
            ->whereDate('ends_on', '>=', $from)->whereDate('ends_on', '<=', $to)->count();
    }
}
