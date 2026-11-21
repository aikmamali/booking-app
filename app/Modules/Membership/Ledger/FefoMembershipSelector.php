<?php

declare(strict_types=1);

namespace App\Modules\Membership\Ledger;

use App\Modules\Membership\Contracts\CreditLedger;
use App\Modules\Membership\Contracts\MembershipChoice;
use App\Modules\Membership\Contracts\MembershipSelector;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Shared\Credits;

/**
 * First-expiring-first-out: of the memberships that can pay, charge the one that ends
 * soonest, so customers do not lose credits to expiry while holding a newer pack.
 */
final readonly class FefoMembershipSelector implements MembershipSelector
{
    public function __construct(private CreditLedger $ledger) {}

    public function choose(int $userId, int $serviceId, Credits $cost, string $sessionDate): MembershipChoice
    {
        $candidates = Membership::query()
            ->where('user_id', $userId)
            ->where('status', MembershipStatus::Active)
            ->whereDate('starts_on', '<=', $sessionDate)
            ->whereDate('ends_on', '>=', $sessionDate)
            ->whereJsonContains('service_ids', $serviceId)
            ->orderBy('ends_on')
            ->orderBy('id')
            ->pluck('id');

        foreach ($candidates as $membershipId) {
            if ($this->ledger->balance($membershipId)->greaterThanOrEqual($cost)) {
                return MembershipChoice::found($membershipId);
            }
        }

        return $candidates->isEmpty() ? MembershipChoice::noMembership() : MembershipChoice::insufficientCredits();
    }
}
