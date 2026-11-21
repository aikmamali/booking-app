<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Service;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Actions\SaveSchedule;
use App\Modules\Scheduling\Contracts\SessionOccupancy;
use App\Modules\Scheduling\Data\ScheduleData;
use App\Modules\Scheduling\Enums\Weekday;
use App\Modules\Scheduling\Events\SessionCancelled;
use App\Modules\Scheduling\Exceptions\SessionChangeRejected;
use App\Modules\Scheduling\Models\ClassSession;
use App\Modules\Scheduling\Models\Schedule;
use Illuminate\Support\Facades\Event;

beforeEach(fn () => $this->travelTo('2027-03-20 08:00:00'));

function scheduleData(Schedule $schedule, array $overrides = []): ScheduleData
{
    return new ScheduleData(...[
        'service_id' => $schedule->service_id,
        'instructor_id' => $schedule->instructor_id,
        'room_id' => $schedule->room_id,
        'weekday' => $schedule->weekday,
        'start_time' => substr($schedule->start_time, 0, 5),
        'capacity' => $schedule->capacity,
        'valid_from' => $schedule->valid_from->toDateString(),
        'valid_until' => $schedule->valid_until?->toDateString(),
        ...$overrides,
    ]);
}

it('creates a schedule and fills its sessions straight away', function (): void {
    $schedule = app(SaveSchedule::class)->handle(null, new ScheduleData(
        service_id: Service::factory()->create()->id,
        instructor_id: User::factory()->instructor()->create()->id,
        room_id: null,
        weekday: Weekday::Thursday,
        start_time: '07:15',
        capacity: 6,
        valid_from: '2027-03-20',
        valid_until: null,
    ));

    expect(ClassSession::query()->where('schedule_id', $schedule->id)->count())->toBe(8)
        ->and(sessionStarts($schedule)[0])->toBe('2027-03-25 05:15');
});

it('only accepts instructors', function (): void {
    $schedule = tuesdayClass();

    app(SaveSchedule::class)->handle(null, scheduleData($schedule, [
        'instructor_id' => User::factory()->customer()->create()->id,
    ]));
})->throws(SessionChangeRejected::class, 'scheduling.errors.not_instructor');

it('rejects an end date before the start date', function (): void {
    app(SaveSchedule::class)->handle(null, scheduleData(tuesdayClass(), ['valid_until' => '2027-03-01']));
})->throws(SessionChangeRejected::class, 'scheduling.errors.valid_until_before_from');

it('does not let the day or time of an existing schedule change', function (array $change): void {
    $schedule = tuesdayClass();

    app(SaveSchedule::class)->handle($schedule, scheduleData($schedule, $change));
})->with([
    'day' => [['weekday' => Weekday::Wednesday]],
    'time' => [['start_time' => '19:00']],
])->throws(SessionChangeRejected::class, 'scheduling.errors.slot_is_fixed');

it('passes instructor, room and capacity changes on to future sessions not edited by hand', function (): void {
    $schedule = tuesdayClass();
    app(SaveSchedule::class)->handle($schedule, scheduleData($schedule));
    $custom = ClassSession::query()->orderBy('starts_at')->firstOrFail();
    $custom->forceFill(['is_customized' => true, 'capacity' => 3])->save();
    $newInstructor = User::factory()->instructor()->create();
    $originalInstructor = $schedule->instructor_id;

    app(SaveSchedule::class)->handle($schedule, scheduleData($schedule, [
        'instructor_id' => $newInstructor->id, 'capacity' => 8,
    ]));

    $others = ClassSession::query()->whereKeyNot($custom->id)->get();
    expect($others->pluck('instructor_id')->unique()->all())->toBe([$newInstructor->id])
        ->and($others->pluck('capacity')->unique()->all())->toBe([8])
        ->and($custom->fresh()?->capacity)->toBe(3)
        ->and($custom->fresh()?->instructor_id)->toBe($originalInstructor);
});

it('never shrinks a session below the seats already taken', function (): void {
    app()->instance(SessionOccupancy::class, new class implements SessionOccupancy
    {
        public function takenSeats(int $sessionId): int
        {
            return 4;
        }
    });
    $schedule = tuesdayClass();
    app(SaveSchedule::class)->handle($schedule, scheduleData($schedule));

    app(SaveSchedule::class)->handle($schedule, scheduleData($schedule, ['capacity' => 2]));

    expect(ClassSession::query()->pluck('capacity')->unique()->all())->toBe([4]);
});

it('cancels the sessions after a shortened end date', function (): void {
    Event::fake([SessionCancelled::class]);
    $schedule = tuesdayClass();
    app(SaveSchedule::class)->handle($schedule, scheduleData($schedule));

    app(SaveSchedule::class)->handle($schedule, scheduleData($schedule, ['valid_until' => '2027-04-06']));

    $byStatus = ClassSession::query()->get()->groupBy(fn (ClassSession $s): string => $s->status->value);
    expect($byStatus['scheduled'])->toHaveCount(3)
        ->and($byStatus['cancelled'])->toHaveCount(5)
        ->and($byStatus['cancelled']->first()?->cancel_reason)->toBe('Λήξη εβδομαδιαίου προγράμματος');
    Event::assertDispatchedTimes(SessionCancelled::class, 5);
});
