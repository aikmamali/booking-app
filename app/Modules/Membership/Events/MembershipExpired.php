<?php

declare(strict_types=1);

namespace App\Modules\Membership\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A membership reached its end date; unused credits were written off (MB-05).
 */
final readonly class MembershipExpired implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $membershipId, public string $forfeitedCredits) {}
}
