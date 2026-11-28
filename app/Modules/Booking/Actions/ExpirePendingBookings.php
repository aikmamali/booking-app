<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\SeatFreed;
use App\Modules\Booking\Models\Booking;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * Spec 8: held bookings (pending_credits, later pending_payment) whose hold ran out become
 * expired and free their seat. Idempotent.
 */
final readonly class ExpirePendingBookings
{
    public function __construct(private Clock $clock) {}

    public function handle(): int
    {
        $ids = Booking::query()
            ->whereIn('status', [BookingStatus::PendingCredits, BookingStatus::PendingPayment])
            ->where('hold_until', '<=', $this->clock->now())
            ->pluck('id');

        $expired = 0;

        foreach ($ids as $id) {
            $expired += DB::transaction(fn (): int => $this->expire((int) $id));
        }

        return $expired;
    }

    private function expire(int $bookingId): int
    {
        $booking = Booking::query()->whereKey($bookingId)->lockForUpdate()->first();

        if ($booking === null || ! $booking->status->canTransitionTo(BookingStatus::Expired)
            || $booking->hold_until === null || $booking->hold_until->gt($this->clock->now())) {
            return 0;
        }

        $booking->forceFill(['status' => BookingStatus::Expired])->save();
        event(new SeatFreed($booking->class_session_id));

        return 1;
    }
}
