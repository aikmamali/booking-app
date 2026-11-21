<?php

declare(strict_types=1);

namespace App\Modules\Booking\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The reminder window of a booking was reached (BK-09).
 */
final readonly class BookingReminderDue implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $bookingId) {}
}
