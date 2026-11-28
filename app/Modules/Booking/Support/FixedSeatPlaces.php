<?php

declare(strict_types=1);

namespace App\Modules\Booking\Support;

use App\Modules\Booking\Enums\FixedSeatStatus;
use App\Modules\Booking\Models\FixedSeat;
use App\Modules\Booking\Models\Season;
use App\Modules\Scheduling\Contracts\WeeklyClasses;

/**
 * How many standing places a weekly class still has in a season. Requested and active
 * seats both hold a place, so approvals never exceed the class capacity.
 */
final readonly class FixedSeatPlaces
{
    public function __construct(private WeeklyClasses $classes) {}

    /**
     * Serialises concurrent requests and approvals within a season (the caller holds a
     * transaction). Locking our own row keeps us off the Scheduling tables.
     */
    public function lockSeason(int $seasonId): void
    {
        Season::query()->whereKey($seasonId)->lockForUpdate()->value('id');
    }

    public function hasRoom(int $scheduleId, int $seasonId, ?int $ignoreSeatId = null): bool
    {
        $capacity = $this->classes->find($scheduleId)->capacity ?? 0;

        return $this->held($scheduleId, $seasonId, $ignoreSeatId) < $capacity;
    }

    public function holdsOne(int $userId, int $scheduleId, int $seasonId): bool
    {
        return FixedSeat::query()->where(['user_id' => $userId, 'schedule_id' => $scheduleId, 'season_id' => $seasonId])
            ->whereIn('status', [FixedSeatStatus::Requested, FixedSeatStatus::Active])
            ->exists();
    }

    private function held(int $scheduleId, int $seasonId, ?int $ignoreSeatId): int
    {
        return FixedSeat::query()->where(['schedule_id' => $scheduleId, 'season_id' => $seasonId])
            ->whereIn('status', [FixedSeatStatus::Requested, FixedSeatStatus::Active])
            ->when($ignoreSeatId !== null, fn ($q) => $q->whereKeyNot($ignoreSeatId))
            ->count();
    }
}
