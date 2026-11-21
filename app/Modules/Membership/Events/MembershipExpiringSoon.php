<?php

declare(strict_types=1);

namespace App\Modules\Membership\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The membership ends within membership_expiry_warning_days. Communication notifies the customer (MB-05).
 */
final readonly class MembershipExpiringSoon implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $membershipId) {}
}
