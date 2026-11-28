<?php

declare(strict_types=1);

namespace App\Modules\Membership\Actions;

use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Exceptions\LedgerRejected;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Support\MembershipAudit;
use Illuminate\Support\Facades\DB;

/**
 * MB-06: moves the end date of an active or frozen membership later, with a reason.
 * An expired membership is not revived: its credits were already written off.
 */
final readonly class ExtendMembership
{
    public function handle(Membership $membership, int $days, string $reason, ?int $actorId): Membership
    {
        if ($days < 1) {
            throw LedgerRejected::because('membership.errors.extend_days');
        }

        if (trim($reason) === '') {
            throw LedgerRejected::because('membership.errors.reason_required');
        }

        return DB::transaction(function () use ($membership, $days, $reason, $actorId): Membership {
            $membership = Membership::query()->whereKey($membership->id)->lockForUpdate()->firstOrFail();

            if (! in_array($membership->status, [MembershipStatus::Active, MembershipStatus::Frozen], true)) {
                throw LedgerRejected::because('membership.errors.not_active');
            }

            $oldEnd = $membership->ends_on;
            $membership->forceFill(['ends_on' => $oldEnd->addDays($days)])->save();

            MembershipAudit::log($membership, 'membership_extended', $actorId, [
                'days' => $days,
                'old_ends_on' => $oldEnd->toDateString(),
                'new_ends_on' => $membership->ends_on->toDateString(),
                'reason' => trim($reason),
            ]);

            return $membership;
        });
    }
}
