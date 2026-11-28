<?php

declare(strict_types=1);

namespace App\Modules\Booking\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * FS-03: a fixed-seat booking is held as pending_credits until the customer buys a package.
 */
final readonly class FixedSeatAwaitingCredits implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $bookingId) {}
}
