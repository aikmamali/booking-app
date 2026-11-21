<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\BookingCancelled;
use App\Modules\Booking\Models\Booking;
use App\Modules\Membership\Contracts\CreditLedger;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * SC-03 / spec 7.1: when the business cancels a session, every active booking is
 * cancelled and always refunded. Also used when a customer account is deleted (ID-04).
 */
final readonly class CancelBookingsOfSession
{
    public function __construct(
        private CreditLedger $ledger,
        private Clock $clock,
    ) {}

    public function handle(int $sessionId, string $reason): int
    {
        return $this->cancelAll(Booking::query()->active()->where('class_session_id', $sessionId)->pluck('id')->all(), $reason, bySession: true);
    }

    /**
     * @param  array<int, int|string>  $bookingIds
     */
    public function cancelAll(array $bookingIds, string $reason, bool $bySession): int
    {
        return DB::transaction(function () use ($bookingIds, $reason, $bySession): int {
            $count = 0;

            foreach ($bookingIds as $id) {
                $booking = Booking::query()->whereKey($id)->lockForUpdate()->first();

                if ($booking === null || ! $booking->status->isActive()) {
                    continue;
                }

                $this->refundAndCancel($booking, $reason);
                event(new BookingCancelled($booking->id, $booking->membership_id !== null, $bySession));
                $count++;
            }

            return $count;
        });
    }

    private function refundAndCancel(Booking $booking, string $reason): void
    {
        if ($booking->status === BookingStatus::Confirmed && $booking->membership_id !== null) {
            $this->ledger->refund($booking->membership_id, $booking->credits_charged, $booking->id, reason: $reason);
        }

        $booking->forceFill([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => $this->clock->now(),
            'cancel_reason' => $reason,
        ])->save();
    }
}
