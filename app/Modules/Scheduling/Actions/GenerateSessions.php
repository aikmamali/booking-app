<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Actions;

use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Scheduling\Events\SessionsGenerated;
use App\Modules\Scheduling\Models\ClassSession;
use App\Modules\Scheduling\Models\Closure;
use App\Modules\Scheduling\Models\Schedule;
use App\Shared\BusinessTime;
use App\Shared\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * SC-02: fills the calendar up to N weeks ahead from the weekly schedules. Idempotent: an
 * existing (schedule, start) pair is never inserted twice, so a session cancelled or edited
 * by staff stays as it is. Closed days (SC-04) are skipped.
 */
final readonly class GenerateSessions
{
    public function __construct(
        private Clock $clock,
        private BusinessTime $time,
        private BookingPolicySettings $settings,
    ) {}

    public function handle(?Schedule $only = null): int
    {
        $now = $this->clock->now();
        $today = $this->time->localDate($now);
        $horizon = $today->addWeeks($this->settings->sessions_generation_weeks);
        $closures = $this->closuresBetween($today, $horizon);

        $rows = [];

        foreach ($this->schedules($today, $horizon, $only) as $schedule) {
            array_push($rows, ...$this->rowsFor($schedule, $today, $horizon, $closures, $now));
        }

        $inserted = DB::transaction(fn (): int => array_sum(array_map(
            fn (array $chunk): int => ClassSession::query()->insertOrIgnore($chunk),
            array_chunk($rows, 500),
        )));

        if ($inserted > 0) {
            event(new SessionsGenerated($inserted));
        }

        return $inserted;
    }

    /**
     * @param  Collection<int, Closure>  $closures
     * @return list<array<string, mixed>>
     */
    private function rowsFor(Schedule $schedule, CarbonImmutable $today, CarbonImmutable $horizon, Collection $closures, CarbonImmutable $now): array
    {
        $rows = [];
        $date = $this->firstOccurrence($schedule, $today);

        for (; $date->lte($horizon); $date = $date->addWeek()) {
            if (! $schedule->isValidOn($date) || $closures->contains(fn (Closure $c): bool => $c->covers($date))) {
                continue;
            }

            $startsAt = $this->time->toUtc($date->format('Y-m-d'), $schedule->start_time);

            if ($startsAt->gt($now)) {
                $rows[] = $this->row($schedule, $startsAt, $now);
            }
        }

        return $rows;
    }

    private function firstOccurrence(Schedule $schedule, CarbonImmutable $today): CarbonImmutable
    {
        $from = $schedule->valid_from->gt($today) ? $schedule->valid_from->startOfDay() : $today;
        $offset = ($schedule->weekday->value - $from->dayOfWeekIso + 7) % 7;

        return $from->addDays($offset);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Schedule $schedule, CarbonImmutable $startsAt, CarbonImmutable $now): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'schedule_id' => $schedule->id,
            'service_id' => $schedule->service_id,
            'instructor_id' => $schedule->instructor_id,
            'room_id' => $schedule->room_id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addMinutes($schedule->service->duration_minutes),
            'capacity' => $schedule->capacity,
            'status' => 'scheduled',
            'is_customized' => false,
            'lock_version' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @return Collection<int, Schedule>
     */
    private function schedules(CarbonImmutable $today, CarbonImmutable $horizon, ?Schedule $only): Collection
    {
        return Schedule::query()
            ->with('service')
            ->when($only instanceof Schedule, fn ($q) => $q->whereKey($only?->id))
            ->whereDate('valid_from', '<=', $horizon)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $today))
            ->whereHas('service', fn ($q) => $q->where('is_active', true))
            ->get();
    }

    /**
     * @return Collection<int, Closure>
     */
    private function closuresBetween(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Closure::query()
            ->whereDate('ends_on', '>=', $from)
            ->whereDate('starts_on', '<=', $to)
            ->get();
    }
}
