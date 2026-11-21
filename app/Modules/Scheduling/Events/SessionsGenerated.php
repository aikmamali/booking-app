<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * New sessions were added to the calendar.
 */
final readonly class SessionsGenerated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $count) {}
}
