<?php

declare(strict_types=1);

namespace App\Modules\Booking\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A booking moved to another session in one transaction (BK-05).
 */
final readonly class BookingRescheduled implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $fromBookingId, public int $toBookingId) {}
}
