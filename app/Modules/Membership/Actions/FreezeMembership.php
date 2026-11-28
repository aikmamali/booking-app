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
 * MB-06: pauses an active membership from today until a date (e.g. an injury). The end
 * date moves later by the paused days, and a frozen membership cannot pay for bookings
 * (FEFO selects active ones only). It resumes by itself on `frozen_until`.
 */
final readonly class FreezeMembership
{
    public function __construct(private BusinessTime $time) {}

    public function handle(Membership $membership, string $until, string $reason, ?int $actorId): Membership
    {
        $today = CarbonImmutable::parse($this->time->today());
        $resumesOn = CarbonImmutable::parse($until);

        if ($resumesOn->lessThanOrEqualTo($today)) {
            throw LedgerRejected::because('membership.errors.freeze_until_past');
        }

        if (trim($reason) === '') {
            throw LedgerRejected::because('membership.errors.reason_required');
        }

        return DB::transaction(function () use ($membership, $today, $resumesOn, $reason, $actorId): Membership {
            $membership = Membership::query()->whereKey($membership->id)->lockForUpdate()->firstOrFail();

            if (! $membership->status->canTransitionTo(MembershipStatus::Frozen) || $membership->status !== MembershipStatus::Active) {
                throw LedgerRejected::because('membership.errors.not_active');
            }

            $days = (int) $today->diffInDays($resumesOn);
            $oldEnd = $membership->ends_on;

            $membership->forceFill([
                'status' => MembershipStatus::Frozen,
                'frozen_until' => $resumesOn,
                'ends_on' => $oldEnd->addDays($days),
            ])->save();

            MembershipAudit::log($membership, 'membership_frozen', $actorId, [
                'until' => $resumesOn->toDateString(),
                'days' => $days,
                'old_ends_on' => $oldEnd->toDateString(),
                'new_ends_on' => $membership->ends_on->toDateString(),
                'reason' => trim($reason),
            ]);

            return $membership;
        });
    }
}
