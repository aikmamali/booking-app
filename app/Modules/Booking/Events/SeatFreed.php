<?php

declare(strict_types=1);

namespace App\Modules\Booking\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A seat became free. The waitlist (step 5) promotes the next customer.
 */
final readonly class SeatFreed implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $sessionId) {}
}
