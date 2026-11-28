<?php

declare(strict_types=1);

namespace App\Modules\Booking\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class FixedSeatApproved implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $fixedSeatId) {}
}
