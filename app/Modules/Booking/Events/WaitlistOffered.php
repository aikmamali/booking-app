<?php

declare(strict_types=1);

namespace App\Modules\Booking\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A seat is held for a waiting customer until offered_until (offer strategy, WL-02).
 */
final readonly class WaitlistOffered implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $entryId) {}
}
