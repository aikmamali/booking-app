<?php

declare(strict_types=1);

namespace App\Modules\Booking\Contracts;

use App\Modules\Booking\Models\FixedSeat;
use App\Modules\Scheduling\Contracts\WeeklyClass;
use App\Modules\Scheduling\Contracts\WeeklyClasses;

/**
 * Public read contract for fixed seats (notifications, FS-02 to FS-04).
 */
final readonly class FixedSeatSummaries
{
    public function __construct(private WeeklyClasses $classes) {}

    public function find(int $seatId): ?FixedSeatSummary
    {
        $seat = FixedSeat::query()->with('season')->find($seatId);
        $class = $seat === null ? null : $this->classes->find($seat->schedule_id);

        if ($seat === null || ! $class instanceof WeeklyClass) {
            return null;
        }

        return new FixedSeatSummary(
            id: $seat->id,
            userId: $seat->user_id,
            serviceName: $class->serviceName,
            weekday: $class->weekday,
            time: substr($class->startTime, 0, 5),
            season: $seat->season->name,
            reason: $seat->revoked_reason,
        );
    }
}
