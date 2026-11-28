<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\FixedSeatStatus;
use App\Modules\Booking\Events\FixedSeatApproved;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\FixedSeat;
use App\Modules\Booking\Support\FixedSeatPlaces;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * FS-02: staff approve a request. The seat becomes active and its bookings are made at
 * once for every class already in the calendar (FS-03).
 */
final readonly class ApproveFixedSeat
{
    public function __construct(
        private FixedSeatPlaces $places,
        private BookFixedSeat $book,
        private Clock $clock,
    ) {}

    public function handle(FixedSeat $seat, int $actorId): FixedSeat
    {
        $seat = DB::transaction(function () use ($seat, $actorId): FixedSeat {
            $this->places->lockSeason($seat->season_id);
            $locked = FixedSeat::query()->whereKey($seat->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== FixedSeatStatus::Requested) {
                throw BookingRejected::because('booking.fixed_seats.not_requested');
            }

            if (! $this->places->hasRoom($locked->schedule_id, $locked->season_id, ignoreSeatId: $locked->id)) {
                throw BookingRejected::because('booking.fixed_seats.full');
            }

            $locked->forceFill(['status' => FixedSeatStatus::Active, 'decided_by' => $actorId, 'decided_at' => $this->clock->now()])->save();
            event(new FixedSeatApproved($locked->id));

            return $locked;
        });

        $this->book->handle($seat);

        return $seat;
    }
}
