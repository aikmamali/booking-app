<?php

declare(strict_types=1);

namespace App\Modules\Membership\Events;

use App\Modules\Billing\Enums\PaymentMethod;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A package was sold. Billing records the payment (BL-01, step 7).
 */
final readonly class MembershipSold implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public int $membershipId,
        public int $userId,
        public int $priceCents,
        public PaymentMethod $method,
        public ?int $actorId = null,
    ) {}
}
