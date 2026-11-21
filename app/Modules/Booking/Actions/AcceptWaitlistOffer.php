<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Data\BookingRequest;
use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Enums\WaitlistStatus;
use App\Modules\Booking\Events\WaitlistPromoted;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\WaitlistEntry;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * WL-02 (offer): the customer takes the seat held for them, before offered_until.
 */
final readonly class AcceptWaitlistOffer
{
    public function __construct(
        private BookSession $bookSession,
        private Clock $clock,
    ) {}

    public function handle(WaitlistEntry $entry): Booking
    {
        return DB::transaction(function () use ($entry): Booking {
            $session = $this->bookSession->lockSession($entry->class_session_id);

            /** @var WaitlistEntry $locked */
            $locked = WaitlistEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== WaitlistStatus::Offered || $locked->offered_until?->lte($this->clock->now()) !== false) {
                throw BookingRejected::because('booking.waitlist.offer_expired');
            }

            $booking = $this->bookSession->bookLocked(
                new BookingRequest(user_id: $locked->user_id, session_id: $session->id, source: BookingSource::Waitlist),
                $session,
            );

            $locked->forceFill(['status' => WaitlistStatus::Promoted, 'booking_id' => $booking->id])->save();
            event(new WaitlistPromoted($locked->id, $booking->id));

            return $booking;
        });
    }
}
