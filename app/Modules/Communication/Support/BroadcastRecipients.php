<?php

declare(strict_types=1);

namespace App\Modules\Communication\Support;

use App\Modules\Communication\Data\BroadcastAudience;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Contracts\MembershipAudience;
use App\Shared\BusinessTime;

/**
 * CM-03: who a bulk message reaches. Only active customers who gave marketing consent
 * (GDPR); membership filters come from the Membership contract.
 */
final readonly class BroadcastRecipients
{
    public function __construct(
        private MembershipAudience $memberships,
        private BusinessTime $time,
    ) {}

    /**
     * @return list<int>
     */
    public function ids(BroadcastAudience $audience): array
    {
        $members = $audience->filtersMemberships()
            ? $this->memberships->customerIds($this->time->today(), $audience->expiring_within_days, $audience->service_id)
            : null;

        $ids = User::role(Role::Customer->value)
            ->whereNull('anonymized_at')
            ->whereNotNull('marketing_consent_at')
            ->when($members !== null, fn ($q) => $q->whereKey($members))
            ->orderBy('id')
            ->pluck('id');

        return array_values(array_map(intval(...), $ids->all()));
    }
}
