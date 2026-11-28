<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Enums\FixedSeatStatus;
use App\Modules\Booking\Events\FixedSeatWarning;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\FixedSeat;
use App\Modules\Booking\Settings\BookingPolicySettings;
use Illuminate\Support\Facades\DB;

/**
 * FS-04: after a fixed-seat booking is cancelled, counts the cancellations in a row (from
 * the latest class back to the last one attended). One below the limit the customer is
 * warned; at the limit the seat is revoked. Cancellations by the studio (class cancelled,
 * seat revoked) and reschedules do not count.
 */
final readonly class RecordFixedSeatCancellation
{
    private const array NOT_COUNTED = ['rescheduled', RevokeFixedSeat::CANCEL_REASON];

    public function __construct(
        private BookingPolicySettings $settings,
        private RevokeFixedSeat $revoke,
    ) {}

    public function handle(int $bookingId, bool $bySession): void
    {
        $booking = Booking::query()->with('session')->find($bookingId);

        if ($bySession || $booking?->fixed_seat_id === null || in_array($booking->cancel_reason, self::NOT_COUNTED, true)) {
            return;
        }

        $seat = FixedSeat::query()->find($booking->fixed_seat_id);

        if ($seat?->status !== FixedSeatStatus::Active) {
            return;
        }

        $streak = $this->streak($seat, $booking);
        $limit = $this->settings->fixed_seat_max_consecutive_cancellations;

        DB::transaction(fn () => $seat->forceFill(['consecutive_cancellations' => $streak])->save());

        if ($streak >= $limit) {
            $this->revoke->handle($seat, __('booking.fixed_seats.auto_revoked', ['count' => $limit]), null);
        } elseif ($streak === $limit - 1) {
            event(new FixedSeatWarning($seat->id));
        }
    }

    private function streak(FixedSeat $seat, Booking $latest): int
    {
        $history = Booking::query()->with('session')->where('fixed_seat_id', $seat->id)
            ->whereIn('status', [BookingStatus::Cancelled, BookingStatus::Attended, BookingStatus::NoShow])
            ->get()
            ->filter(fn (Booking $b): bool => $b->session->starts_at->lte($latest->session->starts_at))
            ->sortByDesc(fn (Booking $b): int => $b->session->starts_at->getTimestamp());

        $streak = 0;

        foreach ($history as $booking) {
            if ($booking->status !== BookingStatus::Cancelled) {
                break;
            }

            $streak += in_array($booking->cancel_reason, self::NOT_COUNTED, true) ? 0 : 1;
        }

        return $streak;
    }
}
