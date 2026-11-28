<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\FixedSeat;
use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Membership\Contracts\CreditLedger;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * BK-08: attended or no-show, once the session has started. A no-show keeps the credit
 * unless no_show_refunds_credit is on.
 */
final readonly class MarkAttendance
{
    public function __construct(
        private CreditLedger $ledger,
        private Clock $clock,
        private BookingPolicySettings $settings,
    ) {}

    public function handle(Booking $booking, BookingStatus $outcome, ?int $actorId = null): Booking
    {
        if (! in_array($outcome, [BookingStatus::Attended, BookingStatus::NoShow], true)) {
            throw new InvalidArgumentException('Attendance is either attended or no_show.');
        }

        return DB::transaction(function () use ($booking, $outcome, $actorId): Booking {
            /** @var Booking $locked */
            $locked = Booking::query()->with('session')->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo($outcome)) {
                throw BookingRejected::because('booking.not_markable');
            }

            if ($locked->session->starts_at->gt($this->clock->now())) {
                throw BookingRejected::because('booking.not_started');
            }

            $this->apply($locked, $outcome, $actorId);

            return $locked;
        });
    }

    /**
     * Shared with CloseFinishedSessions; the caller holds the row lock.
     */
    public function apply(Booking $booking, BookingStatus $outcome, ?int $actorId = null): void
    {
        $refund = $outcome === BookingStatus::NoShow && $this->settings->no_show_refunds_credit && $booking->membership_id !== null;

        if ($refund) {
            $this->ledger->refund((int) $booking->membership_id, $booking->credits_charged, $booking->id, $actorId, 'no_show');
        }

        $booking->forceFill([
            'status' => $outcome,
            'checked_in_at' => $outcome === BookingStatus::Attended ? $this->clock->now() : null,
        ])->save();

        // FS-04: attending resets the fixed seat's cancellations in a row.
        if ($outcome === BookingStatus::Attended && $booking->fixed_seat_id !== null) {
            FixedSeat::query()->whereKey($booking->fixed_seat_id)->update(['consecutive_cancellations' => 0]);
        }
    }
}
