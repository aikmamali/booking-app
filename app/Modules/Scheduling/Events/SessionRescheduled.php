<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Time, instructor or room of a session changed. Communication notifies booked customers.
 */
final readonly class SessionRescheduled implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $sessionId) {}
}
