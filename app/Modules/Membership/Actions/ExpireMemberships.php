<?php

declare(strict_types=1);

namespace App\Modules\Membership\Actions;

use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Enums\TransactionType;
use App\Modules\Membership\Events\MembershipExpired;
use App\Modules\Membership\Ledger\DatabaseCreditLedger;
use App\Modules\Membership\Models\Membership;
use App\Shared\BusinessTime;
use Illuminate\Support\Facades\DB;

/**
 * MB-05: memberships past their end date become `expired`, and unused credits are written
 * off with an `expire` entry. Idempotent: an expired membership is never touched again.
 */
final readonly class ExpireMemberships
{
    public function __construct(
        private DatabaseCreditLedger $ledger,
        private BusinessTime $time,
    ) {}

    public function handle(): int
    {
        $ids = Membership::query()
            ->whereIn('status', [MembershipStatus::Active, MembershipStatus::Frozen])
            ->whereDate('ends_on', '<', $this->time->today())
            ->pluck('id');

        foreach ($ids as $id) {
            DB::transaction(fn () => $this->expire($id));
        }

        return $ids->count();
    }

    private function expire(int $membershipId): void
    {
        /** @var Membership $membership */
        $membership = Membership::query()->whereKey($membershipId)->lockForUpdate()->firstOrFail();

        if (! $membership->status->canTransitionTo(MembershipStatus::Expired)) {
            return;
        }

        $balance = $this->ledger->balance($membershipId);

        if ($balance->isPositive()) {
            $this->ledger->record($membershipId, TransactionType::Expire, $balance->negate());
        }

        $membership->update(['status' => MembershipStatus::Expired]);

        event(new MembershipExpired($membershipId, $balance->toDecimal()));
    }
}
