<?php

declare(strict_types=1);

namespace App\Modules\Booking\Listeners;

use App\Modules\Booking\Events\SeatFreed;
use App\Modules\Booking\Jobs\PromoteFromWaitlistJob;

/**
 * Spec 7.3 step 3: a freed seat queues the promotion of the waitlist.
 */
final class PromoteWhenSeatFreed
{
    public function handle(SeatFreed $event): void
    {
        PromoteFromWaitlistJob::dispatch($event->sessionId);
    }
}
