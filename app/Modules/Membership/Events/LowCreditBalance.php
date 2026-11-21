<?php

declare(strict_types=1);

namespace App\Modules\Membership\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The balance fell to low_balance_threshold or below. Communication notifies the customer (MB-05).
 */
final readonly class LowCreditBalance implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $membershipId) {}
}
