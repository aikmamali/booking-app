<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Data\CancellationRequest;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\BookingCancelled;
use App\Modules\Booking\Events\SeatFreed;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Booking\Support\CancellationTerms;
use App\Modules\Identity\Models\User;
use App\Modules\Membership\Contracts\CreditLedger;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * BK-04: up to cancellation_cutoff_minutes before the start the credit is refunded. After
 * that, customers can cancel only if late_cancel_allowed (losing the credit); staff can
 * always cancel and may refund with an override reason (BK-07, audited).
 */
final readonly class CancelBooking
{
    public function __construct(
        private CreditLedger $ledger,
        private Clock $clock,
        private BookingPolicySettings $settings,
    ) {}

    public function handle(Booking $booking, CancellationRequest $request): Booking
    {
        return DB::transaction(function () use ($booking, $request): Booking {
            $locked = $this->lock($booking);
            $terms = CancellationTerms::for($locked, $this->clock->now(), $this->settings);
            $refund = $this->decideRefund($terms, $request);

            $this->cancel($locked, $request, $refund, late: ! $terms->withinCutoff);
            $this->audit($locked, $request, $refund);

            event(new BookingCancelled($locked->id, $refund, bySession: false));
            event(new SeatFreed($locked->class_session_id));

            return $locked;
        });
    }

    private function lock(Booking $booking): Booking
    {
        /** @var Booking $locked */
        $locked = Booking::query()->with('session.service')->whereKey($booking->id)->lockForUpdate()->firstOrFail();

        if (! $locked->status->canTransitionTo(BookingStatus::Cancelled)) {
            throw BookingRejected::because('booking.not_cancellable');
        }

        return $locked;
    }

    private function decideRefund(CancellationTerms $terms, CancellationRequest $request): bool
    {
        if ($terms->started) {
            throw BookingRejected::because('booking.already_started');
        }

        if ($request->by_staff) {
            $this->guardStaff($request);

            return $terms->withinCutoff || filled($request->refund_override_reason);
        }

        if (! $terms->customerMayCancel()) {
            throw BookingRejected::because('booking.cancel_too_late');
        }

        return $terms->refundsCredit();
    }

    private function cancel(Booking $booking, CancellationRequest $request, bool $refund, bool $late): void
    {
        if ($refund && $booking->membership_id !== null && $booking->status === BookingStatus::Confirmed) {
            $this->ledger->refund($booking->membership_id, $booking->credits_charged, $booking->id, $request->actor_id, $request->reason);
        }

        $booking->forceFill([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => $this->clock->now(),
            'cancel_reason' => $request->reason === null ? null : trim($request->reason),
            'cancelled_by' => $request->actor_id,
            'late_cancel' => $late && ! $refund,
        ])->save();
    }

    private function guardStaff(CancellationRequest $request): void
    {
        $actor = $request->actor_id === null ? null : User::query()->find($request->actor_id);

        if ($actor === null || ! $actor->can('book_for_customer')) {
            throw BookingRejected::because('booking.override_not_allowed');
        }
    }

    private function audit(Booking $booking, CancellationRequest $request, bool $refund): void
    {
        if (! filled($request->refund_override_reason)) {
            return;
        }

        activity('booking')->performedOn($booking)->event('refund_override')
            ->causedBy(User::query()->find($request->actor_id))
            ->withProperties(['override_reason' => $request->refund_override_reason, 'refunded' => $refund])
            ->log('cancelled_with_override');
    }
}
