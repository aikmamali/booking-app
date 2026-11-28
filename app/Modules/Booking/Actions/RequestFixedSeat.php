<?php

declare(strict_types=1);

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\FixedSeatStatus;
use App\Modules\Booking\Exceptions\BookingRejected;
use App\Modules\Booking\Models\FixedSeat;
use App\Modules\Booking\Models\Season;
use App\Modules\Booking\Support\FixedSeatPlaces;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Contracts\WeeklyClass;
use App\Modules\Scheduling\Contracts\WeeklyClasses;
use App\Shared\BusinessTime;
use Illuminate\Support\Facades\DB;

/**
 * FS-02: a customer asks for a standing place in a weekly class for a season. Staff
 * approve or reject it (ApproveFixedSeat, RejectFixedSeat).
 */
final readonly class RequestFixedSeat
{
    public function __construct(
        private WeeklyClasses $classes,
        private FixedSeatPlaces $places,
        private BusinessTime $time,
    ) {}

    public function handle(int $userId, int $scheduleId, int $seasonId): FixedSeat
    {
        $season = Season::query()->find($seasonId);
        $class = $this->classes->find($scheduleId);
        $this->guard($userId, $season, $class);

        return DB::transaction(function () use ($userId, $scheduleId, $seasonId): FixedSeat {
            $this->places->lockSeason($seasonId);

            if ($this->places->holdsOne($userId, $scheduleId, $seasonId)) {
                throw BookingRejected::because('booking.fixed_seats.duplicate');
            }

            if (! $this->places->hasRoom($scheduleId, $seasonId)) {
                throw BookingRejected::because('booking.fixed_seats.full');
            }

            return FixedSeat::query()->create([
                'user_id' => $userId,
                'schedule_id' => $scheduleId,
                'season_id' => $seasonId,
                'status' => FixedSeatStatus::Requested,
            ]);
        });
    }

    private function guard(int $userId, ?Season $season, ?WeeklyClass $class): void
    {
        $user = User::query()->find($userId);

        if ($user === null || $user->isAnonymized() || ! $user->hasRole(Role::Customer->value)) {
            throw BookingRejected::because('booking.not_a_customer');
        }

        if (! $season instanceof Season || $season->ends_on->toDateString() < $this->time->today()) {
            throw BookingRejected::because('booking.fixed_seats.season_over');
        }

        if (! $class instanceof WeeklyClass || ! $class->runsDuring($season->starts_on->toDateString(), $season->ends_on->toDateString())) {
            throw BookingRejected::because('booking.fixed_seats.not_in_season');
        }
    }
}
