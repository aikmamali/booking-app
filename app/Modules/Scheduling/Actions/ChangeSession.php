<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Actions;

use App\Modules\Scheduling\Contracts\SessionOccupancy;
use App\Modules\Scheduling\Data\SessionChangeData;
use App\Modules\Scheduling\Events\SessionRescheduled;
use App\Modules\Scheduling\Exceptions\SessionChangeRejected;
use App\Modules\Scheduling\Models\ClassSession;
use App\Modules\Scheduling\Support\BusinessTime;
use App\Shared\Clock;
use Illuminate\Support\Facades\DB;

/**
 * SC-03: changes instructor, room, capacity or time of one session. Uses optimistic locking
 * (lock_version) so two staff members cannot overwrite each other, and never shrinks a
 * session below the seats already taken.
 */
final readonly class ChangeSession
{
    public function __construct(
        private Clock $clock,
        private BusinessTime $time,
        private SessionOccupancy $occupancy,
        private EnsureInstructor $ensureInstructor,
    ) {}

    public function handle(ClassSession $session, SessionChangeData $data): ClassSession
    {
        $this->ensureInstructor->handle($data->instructor_id);

        return DB::transaction(function () use ($session, $data): ClassSession {
            /** @var ClassSession $locked */
            $locked = ClassSession::query()->with('service')->whereKey($session->id)->lockForUpdate()->firstOrFail();

            $this->guard($locked, $data);

            $startsAt = $this->time->toUtc($data->date, $data->start_time);
            $locked->forceFill([
                'instructor_id' => $data->instructor_id,
                'room_id' => $data->room_id,
                'capacity' => $data->capacity,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->addMinutes($locked->service->duration_minutes),
                'is_customized' => true,
                'lock_version' => $locked->lock_version + 1,
            ]);

            if ($locked->isDirty(['starts_at', 'instructor_id', 'room_id'])) {
                event(new SessionRescheduled($locked->id));
            }

            $locked->save();

            return $locked;
        });
    }

    private function guard(ClassSession $session, SessionChangeData $data): void
    {
        if ($session->lock_version !== $data->lock_version) {
            throw SessionChangeRejected::because('scheduling.errors.stale');
        }

        if ($session->isCancelled()) {
            throw SessionChangeRejected::because('scheduling.errors.already_cancelled');
        }

        if ($session->hasStartedAt($this->clock->now())) {
            throw SessionChangeRejected::because('scheduling.errors.in_past');
        }

        if ($this->time->toUtc($data->date, $data->start_time)->lte($this->clock->now())) {
            throw SessionChangeRejected::because('scheduling.errors.in_past');
        }

        $taken = $this->occupancy->takenSeats($session->id);

        if ($data->capacity < $taken) {
            throw SessionChangeRejected::because('scheduling.errors.capacity_below_taken', ['taken' => $taken]);
        }
    }
}
