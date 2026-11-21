<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Data\BookingRequest;
use App\Modules\Booking\Enums\BookingSource;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\BookingRescheduled;
use App\Modules\Booking\Events\SeatFreed;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Booking\Support\CancellationTerms;
use App\Modules\Membership\Contracts\CreditLedger;
use App\Modules\Scheduling\Models\ClassSession;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * BK-05: move a booking to another session in one transaction. Same cut-off as a refunded
 * cancellation; the credit moves with the booking (refund then debit, net zero for the
 * same service). It is not a cancellation for the fixed-seat streak rule.
 */
final readonly class RescheduleBooking
{
    public function __construct(
        private BookSession $bookSession,
        private CreditLedger $ledger,
        private Clock $clock,
        private BookingPolicySettings $settings,
    ) {}

    public function handle(Booking $booking, int $newSessionId, ?int $actorId = null): Booking
    {
        return DB::transaction(function () use ($booking, $newSessionId, $actorId): Booking {
            $new = $this->lockSessions($booking->class_session_id, $newSessionId);
            $old = $this->lockBooking($booking);

            $this->release($old, $actorId);

            $moved = $this->bookSession->bookLocked(new BookingRequest(
                user_id: $old->user_id,
                session_id: $new->id,
                source: BookingSource::Customer,
                remind: $old->remind,
                actor_id: $actorId,
            ), $new, ignoreBookingId: $old->id);

            $old->forceFill(['rescheduled_to_id' => $moved->id])->save();

            event(new BookingRescheduled($old->id, $moved->id));
            event(new SeatFreed($old->class_session_id));

            return $moved;
        });
    }

    /**
     * Locks both sessions in id order so two opposite reschedules cannot deadlock.
     */
    private function lockSessions(int $oldId, int $newId): ClassSession
    {
        if ($oldId === $newId) {
            throw BookingRejected::because('booking.duplicate');
        }

        $locked = ClassSession::query()->with('service')->whereKey([$oldId, $newId])->orderBy('id')->lockForUpdate()->get();

        return $locked->firstWhere('id', $newId) ?? throw BookingRejected::because('booking.session_closed');
    }

    private function lockBooking(Booking $booking): Booking
    {
        /** @var Booking $locked */
        $locked = Booking::query()->with('session.service')->whereKey($booking->id)->lockForUpdate()->firstOrFail();
        $terms = CancellationTerms::for($locked, $this->clock->now(), $this->settings);

        if ($locked->status !== BookingStatus::Confirmed || ! $terms->customerMayReschedule()) {
            throw BookingRejected::because('booking.reschedule_too_late');
        }

        return $locked;
    }

    private function release(Booking $old, ?int $actorId): void
    {
        if ($old->membership_id !== null) {
            $this->ledger->refund($old->membership_id, $old->credits_charged, $old->id, $actorId, 'rescheduled');
        }

        $old->forceFill([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => $this->clock->now(),
            'cancel_reason' => 'rescheduled',
            'cancelled_by' => $actorId,
        ])->save();
    }
}
