<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Actions;

use App\Modules\Scheduling\Contracts\SessionOccupancy;
use App\Modules\Scheduling\Data\ScheduleData;
use App\Modules\Scheduling\Exceptions\SessionChangeRejected;
use App\Modules\Scheduling\Models\ClassSession;
use App\Modules\Scheduling\Models\Schedule;
use App\Modules\Scheduling\Support\BusinessTime;
use App\Shared\Clock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * SC-02: creates or edits a weekly schedule and keeps its future sessions in step.
 *
 * - Day and time are fixed once created: to move a class, end this schedule (valid until)
 *   and create a new one. Moving booked sessions silently would surprise customers.
 * - Instructor, room and capacity changes apply to future sessions not edited by hand.
 * - Shortening the validity cancels the future sessions after the new end date.
 */
final readonly class SaveSchedule
{
    public function __construct(
        private Clock $clock,
        private BusinessTime $time,
        private SessionOccupancy $occupancy,
        private EnsureInstructor $ensureInstructor,
        private CancelSession $cancelSession,
        private GenerateSessions $generateSessions,
    ) {}

    public function handle(?Schedule $schedule, ScheduleData $data): Schedule
    {
        $this->ensureInstructor->handle($data->instructor_id);
        $this->guardDates($data);

        $schedule = DB::transaction(function () use ($schedule, $data): Schedule {
            if (! $schedule instanceof Schedule) {
                return Schedule::query()->create($data->toArray());
            }

            $this->guardFixedSlot($schedule, $data);
            $schedule->update($data->toArray());
            $this->propagate($schedule);
            $this->cancelAfterValidity($schedule);

            return $schedule;
        });

        $this->generateSessions->handle($schedule);

        return $schedule;
    }

    private function guardDates(ScheduleData $data): void
    {
        if ($data->valid_until !== null && $data->valid_until < $data->valid_from) {
            throw SessionChangeRejected::because('scheduling.errors.valid_until_before_from');
        }
    }

    private function guardFixedSlot(Schedule $schedule, ScheduleData $data): void
    {
        if ($schedule->weekday !== $data->weekday || substr($schedule->start_time, 0, 5) !== substr($data->start_time, 0, 5)) {
            throw SessionChangeRejected::because('scheduling.errors.slot_is_fixed');
        }
    }

    private function propagate(Schedule $schedule): void
    {
        foreach ($this->futureSessions($schedule)->where('is_customized', false)->get() as $session) {
            $capacity = max($schedule->capacity, $this->occupancy->takenSeats($session->id));

            $session->forceFill([
                'instructor_id' => $schedule->instructor_id,
                'room_id' => $schedule->room_id,
                'capacity' => $capacity,
            ])->save();
        }
    }

    private function cancelAfterValidity(Schedule $schedule): void
    {
        if ($schedule->valid_until === null) {
            return;
        }

        $end = $this->time->toUtc($schedule->valid_until->addDay()->format('Y-m-d'), '00:00');

        foreach ($this->futureSessions($schedule)->where('starts_at', '>=', $end)->get() as $session) {
            $this->cancelSession->handle($session, __('scheduling.reasons.schedule_ended', [], (string) config('app.locale')));
        }
    }

    /**
     * @return Builder<ClassSession>
     */
    private function futureSessions(Schedule $schedule): Builder
    {
        return ClassSession::query()
            ->scheduled()
            ->where('schedule_id', $schedule->id)
            ->where('starts_at', '>', $this->clock->now());
    }
}
