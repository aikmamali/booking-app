<?php

declare(strict_types=1);

namespace App\Modules\Booking\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A waiting customer got the seat (auto-book or accepted offer, WL-02).
 */
final readonly class WaitlistPromoted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $entryId, public int $bookingId) {}
}
