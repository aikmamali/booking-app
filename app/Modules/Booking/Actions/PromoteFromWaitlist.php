<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Data\BookingRequest;
use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Enums\WaitlistStatus;
use App\Modules\Booking\Enums\WaitlistStrategy;
use App\Modules\Booking\Events\WaitlistOffered;
use App\Modules\Booking\Events\WaitlistPromoted;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\WaitlistEntry;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Booking\Support\SeatCounter;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * WL-02 (spec 7.3): fills free seats from the front of the queue, never inside
 * waitlist_cutoff_minutes. auto_book books and charges the customer (an entry that cannot
 * be booked, e.g. no credits, expires and the next one is tried); offer holds the seat
 * for waitlist_offer_ttl_minutes.
 */
final readonly class PromoteFromWaitlist
{
    public function __construct(
        private BookSession $bookSession,
        private BookingPolicySettings $settings,
        private Clock $clock,
    ) {}

    public function handle(int $sessionId): int
    {
        return DB::transaction(function () use ($sessionId): int {
            $session = $this->bookSession->lockSession($sessionId);

            if (! $this->promotionOpen($session)) {
                return 0;
            }

            $filled = 0;

            while (SeatCounter::taken($session->id) < $session->capacity && ($entry = $this->next($session)) instanceof WaitlistEntry) {
                $filled += $this->settings->waitlist_strategy === WaitlistStrategy::Offer
                    ? $this->offer($entry, $session)
                    : $this->autoBook($entry, $session);
            }

            return $filled;
        });
    }

    private function promotionOpen(ClassSession $session): bool
    {
        return ! $session->isCancelled()
            && $this->clock->now()->lt($session->starts_at->subMinutes($this->settings->waitlist_cutoff_minutes));
    }

    private function next(ClassSession $session): ?WaitlistEntry
    {
        return WaitlistEntry::query()
            ->where('class_session_id', $session->id)
            ->where('status', WaitlistStatus::Waiting)
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }

    private function autoBook(WaitlistEntry $entry, ClassSession $session): int
    {
        try {
            $booking = DB::transaction(fn (): Booking => $this->bookSession->bookLocked(
                new BookingRequest(user_id: $entry->user_id, session_id: $session->id, source: BookingSource::Waitlist),
                $session,
            ));
        } catch (BookingRejected) {
            $entry->update(['status' => WaitlistStatus::Expired]);

            return 0;
        }

        $entry->forceFill(['status' => WaitlistStatus::Promoted, 'booking_id' => $booking->id])->save();
        event(new WaitlistPromoted($entry->id, $booking->id));

        return 1;
    }

    private function offer(WaitlistEntry $entry, ClassSession $session): int
    {
        $entry->forceFill(['status' => WaitlistStatus::Offered, 'offered_until' => $this->offerDeadline($session)])->save();
        event(new WaitlistOffered($entry->id));

        return 1;
    }

    /**
     * The TTL, but never past the waitlist cut-off.
     */
    private function offerDeadline(ClassSession $session): CarbonImmutable
    {
        $ttl = $this->clock->now()->addMinutes($this->settings->waitlist_offer_ttl_minutes);
        $cutoff = $session->starts_at->subMinutes($this->settings->waitlist_cutoff_minutes);

        return $ttl->lt($cutoff) ? $ttl : $cutoff;
    }
}
