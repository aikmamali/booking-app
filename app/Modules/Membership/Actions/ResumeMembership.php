<?php

declare(strict_types=1);

namespace App\Modules\Membership\Actions;

use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Exceptions\LedgerRejected;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Support\MembershipAudit;
use App\Shared\BusinessTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * MB-06: ends a freeze. Resuming before `frozen_until` gives back the unused paused days,
 * so the end date only moves by the days actually paused.
 */
final readonly class ResumeMembership
{
    public function __construct(private BusinessTime $time) {}

    public function handle(Membership $membership, ?int $actorId): Membership
    {
        return DB::transaction(function () use ($membership, $actorId): Membership {
            $membership = Membership::query()->whereKey($membership->id)->lockForUpdate()->firstOrFail();

            if ($membership->status !== MembershipStatus::Frozen || $membership->frozen_until === null) {
                throw LedgerRejected::because('membership.errors.not_frozen');
            }

            $today = CarbonImmutable::parse($this->time->today());
            $unused = $today->lessThan($membership->frozen_until) ? (int) $today->diffInDays($membership->frozen_until) : 0;
            $oldEnd = $membership->ends_on;

            $membership->forceFill([
                'status' => MembershipStatus::Active,
                'frozen_until' => null,
                'ends_on' => $oldEnd->subDays($unused),
            ])->save();

            MembershipAudit::log($membership, 'membership_resumed', $actorId, [
                'unused_days' => $unused,
                'old_ends_on' => $oldEnd->toDateString(),
                'new_ends_on' => $membership->ends_on->toDateString(),
            ]);

            return $membership;
        });
    }
}
