<?php

declare(strict_types=1);

namespace App\Modules\Membership\Contracts;

use App\Modules\Membership\Models\Membership;

/**
 * Public read contract of the Membership module.
 */
final readonly class MembershipSummaries
{
    public function __construct(private CreditLedger $ledger) {}

    public function find(int $membershipId, string $locale): ?MembershipSummary
    {
        $membership = Membership::query()->find($membershipId);

        return $membership === null ? null : new MembershipSummary(
            userId: $membership->user_id,
            packageName: (string) $membership->getTranslation('package_name', $locale),
            endsOn: $membership->ends_on,
            balance: $this->ledger->balance($membership->id),
        );
    }

    public function ownerOf(int $membershipId): ?int
    {
        $owner = Membership::query()->whereKey($membershipId)->value('user_id');

        return $owner === null ? null : (int) $owner;
    }
}
