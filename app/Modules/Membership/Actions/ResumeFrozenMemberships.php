<?php

declare(strict_types=1);

namespace App\Modules\Membership\Actions;

use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Shared\BusinessTime;

/**
 * MB-06: daily job step. Freezes whose `frozen_until` has come resume by themselves.
 * Idempotent: a resumed membership is no longer frozen.
 */
final readonly class ResumeFrozenMemberships
{
    public function __construct(
        private ResumeMembership $resume,
        private BusinessTime $time,
    ) {}

    public function handle(): int
    {
        $memberships = Membership::query()
            ->where('status', MembershipStatus::Frozen)
            ->whereDate('frozen_until', '<=', $this->time->today())
            ->get();

        foreach ($memberships as $membership) {
            $this->resume->handle($membership, null);
        }

        return $memberships->count();
    }
}
