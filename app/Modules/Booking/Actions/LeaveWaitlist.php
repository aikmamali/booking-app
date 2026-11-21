<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\WaitlistStatus;
use App\Modules\Booking\Events\SeatFreed;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\WaitlistEntry;
use Illuminate\Support\Facades\DB;

/**
 * WL-03: leave the queue, without consequences. A declined offer frees its held seat for
 * the next customer.
 */
final class LeaveWaitlist
{
    public function handle(WaitlistEntry $entry): void
    {
        DB::transaction(function () use ($entry): void {
            /** @var WaitlistEntry $locked */
            $locked = WaitlistEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo(WaitlistStatus::Left)) {
                throw BookingRejected::because('booking.waitlist.not_waiting');
            }

            $wasOffered = $locked->status === WaitlistStatus::Offered;
            $locked->update(['status' => WaitlistStatus::Left]);

            if ($wasOffered) {
                event(new SeatFreed($locked->class_session_id));
            }
        });
    }
}
