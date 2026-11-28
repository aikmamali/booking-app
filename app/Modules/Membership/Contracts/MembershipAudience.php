<?php

declare(strict_types=1);

namespace App\Modules\Membership\Contracts;

use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use Carbon\CarbonImmutable;

/**
 * Public read contract for CM-03 audiences: customers whose active membership matches
 * every given filter (one membership must satisfy all of them).
 */
final class MembershipAudience
{
    /**
     * @return list<int>
     */
    public function customerIds(string $today, ?int $expiringWithinDays = null, ?int $serviceId = null): array
    {
        $ids = Membership::query()
            ->where('status', MembershipStatus::Active)
            ->whereDate('starts_on', '<=', $today)
            ->whereDate('ends_on', '>=', $today)
            ->when($expiringWithinDays !== null, fn ($q) => $q->whereDate(
                'ends_on', '<=', CarbonImmutable::parse($today)->addDays((int) $expiringWithinDays)->toDateString(),
            ))
            ->when($serviceId !== null, fn ($q) => $q->whereJsonContains('service_ids', $serviceId))
            ->distinct()
            ->pluck('user_id');

        return array_values(array_map(intval(...), $ids->all()));
    }
}
