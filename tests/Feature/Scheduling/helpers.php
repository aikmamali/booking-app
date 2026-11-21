<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Service;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Enums\Weekday;
use App\Modules\Scheduling\Models\ClassSession;
use App\Modules\Scheduling\Models\Schedule;

/**
 * A Tuesday 18:30 schedule (Athens time) for a 60-minute service.
 *
 * @param  array<string, mixed>  $overrides
 */
function tuesdayClass(array $overrides = []): Schedule
{
    return Schedule::factory()->create([
        'service_id' => Service::factory()->create(['duration_minutes' => 60])->id,
        'instructor_id' => User::factory()->instructor()->create()->id,
        'weekday' => Weekday::Tuesday,
        'start_time' => '18:30',
        'capacity' => 5,
        'valid_from' => now()->toDateString(),
        ...$overrides,
    ]);
}

/**
 * @return list<string> session starts in UTC, ordered
 */
function sessionStarts(?Schedule $schedule = null): array
{
    return ClassSession::query()
        ->when($schedule instanceof Schedule, fn ($q) => $q->where('schedule_id', $schedule?->id))
        ->orderBy('starts_at')
        ->get()
        ->map(fn ($s): string => $s->starts_at->format('Y-m-d H:i'))
        ->all();
}
