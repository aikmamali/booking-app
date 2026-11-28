<?php

declare(strict_types=1);

namespace App\Modules\Membership\Support;

use App\Modules\Identity\Models\User;
use App\Modules\Membership\Models\Membership;

/**
 * Audit trail for membership changes that are not ledger movements (spec: activity_log).
 */
final class MembershipAudit
{
    /**
     * @param  array<string, scalar>  $properties
     */
    public static function log(Membership $membership, string $event, ?int $actorId, array $properties): void
    {
        $actor = $actorId === null ? null : User::query()->find($actorId);

        activity()->performedOn($membership)->causedBy($actor)->withProperties($properties)->log($event);
    }
}
