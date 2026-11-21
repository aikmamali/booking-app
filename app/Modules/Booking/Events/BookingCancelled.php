<?php

declare(strict_types=1);

namespace App\Modules\Booking\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A booking was cancelled by the customer, by staff or because the session was cancelled.
 */
final readonly class BookingCancelled implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $bookingId, public bool $refunded, public bool $bySession) {}
}
