<?php

declare(strict_types=1);

use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Actions\CancelSession;
use App\Modules\Scheduling\Actions\ChangeSession;
use App\Modules\Scheduling\Contracts\SessionOccupancy;
use App\Modules\Scheduling\Data\SessionChangeData;
use App\Modules\Scheduling\Events\SessionCancelled;
use App\Modules\Scheduling\Events\SessionRescheduled;
use App\Modules\Scheduling\Exceptions\SessionChangeRejected;
use App\Modules\Scheduling\Models\ClassSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->travelTo('2027-03-20 08:00:00');
    // Tuesday 23 March 2027, 18:30 Athens.
    $this->session = ClassSession::factory()->create([
        'starts_at' => '2027-03-23 16:30:00', 'ends_at' => '2027-03-23 17:30:00', 'capacity' => 5,
    ])->fresh();
});

function change(ClassSession $session, array $overrides = []): ClassSession
{
    return app(ChangeSession::class)->handle($session, new SessionChangeData(...[
        'instructor_id' => $session->instructor_id,
        'room_id' => $session->room_id,
        'capacity' => $session->capacity,
        'date' => '2027-03-23',
        'start_time' => '18:30',
        'lock_version' => $session->lock_version,
        ...array_map(fn (mixed $v): mixed => $v instanceof Closure ? $v() : $v, $overrides),
    ]));
}

it('moves a session, keeping its duration, and announces it', function (): void {
    Event::fake([SessionRescheduled::class]);

    $changed = change($this->session, ['date' => '2027-03-24', 'start_time' => '19:00', 'capacity' => 6]);

    expect($changed->starts_at->format('Y-m-d H:i'))->toBe('2027-03-24 17:00')
        ->and($changed->ends_at->format('Y-m-d H:i'))->toBe('2027-03-24 18:00')
        ->and($changed->capacity)->toBe(6)
        ->and($changed->is_customized)->toBeTrue()
        ->and($changed->lock_version)->toBe(1);
    Event::assertDispatched(SessionRescheduled::class, fn (SessionRescheduled $e): bool => $e->sessionId === $this->session->id);
});

it('changes the instructor', function (): void {
    Event::fake([SessionRescheduled::class]);
    $other = User::factory()->instructor()->create();

    expect(change($this->session, ['instructor_id' => $other->id])->instructor_id)->toBe($other->id);
    Event::assertDispatched(SessionRescheduled::class);
});

it('does not announce a capacity-only change', function (): void {
    Event::fake([SessionRescheduled::class]);

    change($this->session, ['capacity' => 8]);

    Event::assertNotDispatched(SessionRescheduled::class);
});

it('rejects a change based on an outdated copy', function (): void {
    change($this->session, ['capacity' => 6]);

    change($this->session->fresh() ?? $this->session, ['capacity' => 7, 'lock_version' => 0]);
})->throws(SessionChangeRejected::class, 'scheduling.errors.stale');

it('rejects changes that break the rules', function (Closure $arrange, array $change, string $error): void {
    $arrange($this);

    expect(fn (): ClassSession => change($this->session->fresh() ?? $this->session, $change))
        ->toThrow(SessionChangeRejected::class, $error);
})->with([
    'not an instructor' => [fn (): null => null, ['instructor_id' => fn () => User::factory()->customer()->create()->id], 'scheduling.errors.not_instructor'],
    'moved into the past' => [fn (): null => null, ['date' => '2027-03-19'], 'scheduling.errors.in_past'],
    'already started' => [fn ($t) => $t->travelTo('2027-03-23 16:31:00'), ['capacity' => 6], 'scheduling.errors.in_past'],
    'cancelled' => [fn ($t) => app(CancelSession::class)->handle($t->session, 'x'), ['capacity' => 6, 'lock_version' => 1], 'scheduling.errors.already_cancelled'],
]);

it('never shrinks a session below the seats taken', function (): void {
    app()->instance(SessionOccupancy::class, new class implements SessionOccupancy
    {
        public function takenSeats(int $sessionId): int
        {
            return 4;
        }
    });

    expect(fn (): ClassSession => change($this->session, ['capacity' => 3]))
        ->toThrow(SessionChangeRejected::class, 'scheduling.errors.capacity_below_taken');
    expect(change($this->session, ['capacity' => 4])->capacity)->toBe(4);
});

it('cancels a session with a reason, after commit', function (): void {
    Event::fake([SessionCancelled::class]);

    DB::beginTransaction();
    app(CancelSession::class)->handle($this->session, 'Ασθένεια εκπαιδευτή');
    Event::assertNotDispatched(SessionCancelled::class);
    DB::commit();

    $session = $this->session->fresh();
    expect($session?->isCancelled())->toBeTrue()
        ->and($session?->cancel_reason)->toBe('Ασθένεια εκπαιδευτή')
        ->and($session?->cancelled_at?->toDateTimeString())->toBe('2027-03-20 08:00:00');
    Event::assertDispatched(SessionCancelled::class, fn (SessionCancelled $e): bool => $e->reason === 'Ασθένεια εκπαιδευτή');
});

it('refuses to cancel twice', function (): void {
    app(CancelSession::class)->handle($this->session, 'x');
    app(CancelSession::class)->handle($this->session, 'x');
})->throws(SessionChangeRejected::class, 'scheduling.errors.already_cancelled');
