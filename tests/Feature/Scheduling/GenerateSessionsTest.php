<?php

declare(strict_types=1);

use App\Modules\Booking\Settings\BookingPolicySettings;
use App\Modules\Scheduling\Actions\CancelSession;
use App\Modules\Scheduling\Actions\GenerateSessions;
use App\Modules\Scheduling\Events\SessionsGenerated;
use App\Modules\Scheduling\Jobs\GenerateSessionsJob;
use App\Modules\Scheduling\Models\ClassSession;
use App\Modules\Scheduling\Models\Closure;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;

function generate(): int
{
    return app(GenerateSessions::class)->handle();
}

beforeEach(function (): void {
    // Saturday 20 March 2027, 10:00 Athens. Summer time starts Sunday 28 March.
    $this->travelTo('2027-03-20 08:00:00');
});

it('fills the calendar for the configured number of weeks', function (): void {
    $settings = app(BookingPolicySettings::class);
    $settings->sessions_generation_weeks = 3;
    $settings->save();
    tuesdayClass();

    expect(generate())->toBe(3)
        ->and(sessionStarts())->toBe(['2027-03-23 16:30', '2027-03-30 15:30', '2027-04-06 15:30']);
});

it('keeps the wall-clock time across the daylight saving change', function (): void {
    tuesdayClass();
    generate();

    $local = ClassSession::query()->orderBy('starts_at')->get()
        ->map(fn (ClassSession $s): string => $s->starts_at->setTimezone('Europe/Athens')->format('D H:i'))
        ->unique()->values()->all();

    expect($local)->toBe(['Tue 18:30']);
});

it('copies service, instructor, capacity and duration onto each session', function (): void {
    $schedule = tuesdayClass(['capacity' => 7]);
    generate();

    $session = ClassSession::query()->firstOrFail();
    expect($session->service_id)->toBe($schedule->service_id)
        ->and($session->instructor_id)->toBe($schedule->instructor_id)
        ->and($session->capacity)->toBe(7)
        ->and($session->ends_at->diffInMinutes($session->starts_at, absolute: true))->toEqual(60)
        ->and($session->public_id)->toHaveLength(26)
        ->and($session->is_customized)->toBeFalse();
});

it('is idempotent', function (): void {
    tuesdayClass();

    $first = generate();

    expect(generate())->toBe(0)
        ->and(ClassSession::query()->count())->toBe($first)
        ->and($first)->toBe(8);
});

it('announces new sessions after commit, and stays quiet when nothing is new', function (): void {
    Event::fake([SessionsGenerated::class]);
    tuesdayClass();

    generate();
    generate();

    Event::assertDispatchedTimes(SessionsGenerated::class, 1);
});

it('skips closed days', function (): void {
    tuesdayClass();
    Closure::query()->create(['starts_on' => '2027-03-29', 'ends_on' => '2027-04-02', 'reason' => 'Πάσχα']);

    generate();

    expect(sessionStarts())->not->toContain('2027-03-30 15:30')->toHaveCount(7);
});

it('respects the validity of the schedule', function (): void {
    tuesdayClass(['valid_from' => '2027-04-01', 'valid_until' => '2027-04-20']);

    generate();

    expect(sessionStarts())->toBe(['2027-04-06 15:30', '2027-04-13 15:30', '2027-04-20 15:30']);
});

it('does not create sessions that already started today', function (): void {
    $this->travelTo('2027-03-23 17:00:00'); // Tuesday 19:00 Athens, after the 18:30 class

    tuesdayClass();
    generate();

    expect(sessionStarts()[0])->toBe('2027-03-30 15:30');
});

it('does not generate for inactive services', function (): void {
    $schedule = tuesdayClass();
    $schedule->service->update(['is_active' => false]);

    expect(generate())->toBe(0);
});

it('never revives a cancelled session', function (): void {
    tuesdayClass();
    generate();
    $first = ClassSession::query()->orderBy('starts_at')->firstOrFail();
    app(CancelSession::class)->handle($first, 'Ασθένεια');

    generate();

    expect(ClassSession::query()->where('starts_at', $first->starts_at)->count())->toBe(1)
        ->and($first->fresh()?->isCancelled())->toBeTrue();
});

it('runs every night at 02:00 Athens time without overlapping', function (): void {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($e): bool => $e->description === 'generate-sessions');

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 2 * * *')
        ->and($event->timezone)->toBe('Europe/Athens')
        ->and($event->withoutOverlapping)->toBeTrue();

    (new GenerateSessionsJob)->handle(app(GenerateSessions::class));
    expect(ClassSession::query()->count())->toBe(0);
});
