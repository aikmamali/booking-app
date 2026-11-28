<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\FixedSeatStatus;
use App\Modules\Booking\Events\FixedSeatRevoked;
use App\Modules\Booking\Events\SeatFreed;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\FixedSeat;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * FS-04: takes a seat away (by staff, or automatically after too many cancellations in a
 * row). Its future bookings are cancelled with a credit refund and the seats freed.
 */
final readonly class RevokeFixedSeat
{
    public const string CANCEL_REASON = 'fixed_seat_revoked';

    public function __construct(
        private CancelBookingsOfSession $cancel,
        private Clock $clock,
    ) {}

    public function handle(FixedSeat $seat, string $reason, ?int $actorId): FixedSeat
    {
        if (trim($reason) === '') {
            throw BookingRejected::because('booking.fixed_seats.reason_required');
        }

        return DB::transaction(function () use ($seat, $reason, $actorId): FixedSeat {
            $locked = FixedSeat::query()->whereKey($seat->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== FixedSeatStatus::Active) {
                throw BookingRejected::because('booking.fixed_seats.not_active');
            }

            $locked->forceFill([
                'status' => FixedSeatStatus::Revoked,
                'revoked_reason' => trim($reason),
                'decided_by' => $actorId,
                'decided_at' => $this->clock->now(),
            ])->save();

            $this->cancelFutureBookings($locked);
            event(new FixedSeatRevoked($locked->id));

            return $locked;
        });
    }

    private function cancelFutureBookings(FixedSeat $seat): void
    {
        $future = Booking::query()->active()->where('fixed_seat_id', $seat->id)
            ->whereHas('session', fn ($q) => $q->where('starts_at', '>', $this->clock->now()))
            ->get(['id', 'class_session_id']);

        $this->cancel->cancelAll($future->pluck('id')->all(), self::CANCEL_REASON, bySession: false);

        foreach ($future->pluck('class_session_id')->unique() as $sessionId) {
            event(new SeatFreed((int) $sessionId));
        }
    }
}
