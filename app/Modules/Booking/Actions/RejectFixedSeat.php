<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\FixedSeatStatus;
use App\Modules\Booking\Events\FixedSeatRejected;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\FixedSeat;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * FS-02: staff turn a request down, with a reason the customer sees.
 */
final readonly class RejectFixedSeat
{
    public function __construct(private Clock $clock) {}

    public function handle(FixedSeat $seat, string $reason, int $actorId): FixedSeat
    {
        if (trim($reason) === '') {
            throw BookingRejected::because('booking.fixed_seats.reason_required');
        }

        return DB::transaction(function () use ($seat, $reason, $actorId): FixedSeat {
            $locked = FixedSeat::query()->whereKey($seat->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== FixedSeatStatus::Requested) {
                throw BookingRejected::because('booking.fixed_seats.not_requested');
            }

            $locked->forceFill([
                'status' => FixedSeatStatus::Revoked,
                'revoked_reason' => trim($reason),
                'decided_by' => $actorId,
                'decided_at' => $this->clock->now(),
            ])->save();
            event(new FixedSeatRejected($locked->id));

            return $locked;
        });
    }
}
