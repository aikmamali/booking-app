<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Contracts;

use App\Modules\Scheduling\Models\Schedule;

/**
 * Public read contract of the weekly schedules.
 */
final class WeeklyClasses
{
    public function find(int $scheduleId): ?WeeklyClass
    {
        $schedule = Schedule::query()->with(['service', 'instructor'])->find($scheduleId);

        return $schedule === null ? null : $this->summarise($schedule);
    }

    /**
     * @param  list<int>  $scheduleIds
     * @return array<int, WeeklyClass> keyed by schedule id
     */
    public function findMany(array $scheduleIds): array
    {
        $found = [];

        foreach (Schedule::query()->with(['service', 'instructor'])->whereKey($scheduleIds)->get() as $schedule) {
            $found[$schedule->id] = $this->summarise($schedule);
        }

        return $found;
    }

    /**
     * Schedules of active services that run at some point between two local dates.
     *
     * @return list<WeeklyClass>
     */
    public function runningBetween(string $from, string $to): array
    {
        return array_values(Schedule::query()->with(['service', 'instructor'])
            ->whereDate('valid_from', '<=', $to)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $from))
            ->whereHas('service', fn ($q) => $q->where('is_active', true))
            ->orderBy('weekday')->orderBy('start_time')
            ->get()
            ->map(fn (Schedule $s): WeeklyClass => $this->summarise($s))
            ->all());
    }

    private function summarise(Schedule $schedule): WeeklyClass
    {
        return new WeeklyClass(
            id: $schedule->id,
            serviceId: $schedule->service_id,
            serviceName: $schedule->service->name,
            weekday: $schedule->weekday->label(),
            startTime: $schedule->start_time,
            instructorName: $schedule->instructor->fullName(),
            capacity: $schedule->capacity,
            validFrom: $schedule->valid_from,
            validUntil: $schedule->valid_until,
        );
    }
}
