<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\FixedSeatStatus;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\FixedSeat;
use Illuminate\Support\Facades\DB;

/**
 * FS-02: the customer withdraws a request or gives up a seat. Bookings already made stay;
 * the customer cancels them one by one if needed.
 */
final readonly class LeaveFixedSeat
{
    public function handle(FixedSeat $seat, int $userId): FixedSeat
    {
        return DB::transaction(function () use ($seat, $userId): FixedSeat {
            $locked = FixedSeat::query()->whereKey($seat->id)->lockForUpdate()->firstOrFail();

            if ($locked->user_id !== $userId || ! $locked->status->canTransitionTo(FixedSeatStatus::Ended)) {
                throw BookingRejected::because('booking.fixed_seats.not_active');
            }

            $locked->forceFill(['status' => FixedSeatStatus::Ended])->save();

            return $locked;
        });
    }
}
