<?php

declare(strict_types=1);

namespace App\Modules\Booking\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A seat was booked (BK-02). Communication sends the confirmation with an .ics file.
 */
final readonly class BookingConfirmed implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $bookingId) {}
}
