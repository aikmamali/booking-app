<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A session was cancelled (SC-03, SC-04). Booking refunds credits; Communication notifies customers.
 */
final readonly class SessionCancelled implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $sessionId, public string $reason) {}
}
