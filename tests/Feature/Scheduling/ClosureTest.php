<?php

declare(strict_types=1);

use App\Modules\Scheduling\Actions\DeleteClosure;
use App\Modules\Scheduling\Actions\GenerateSessions;
use App\Modules\Scheduling\Actions\SaveClosure;
use App\Modules\Scheduling\Enums\SessionStatus;
use App\Modules\Scheduling\Events\SessionCancelled;
use App\Modules\Scheduling\Exceptions\SessionChangeRejected;
use App\Modules\Scheduling\Models\ClassSession;
use App\Modules\Scheduling\Models\Closure;
use Illuminate\Support\Facades\Event;

beforeEach(fn () => $this->travelTo('2027-03-20 08:00:00'));

it('cancels the sessions on closed days, by local date', function (): void {
    Event::fake([SessionCancelled::class]);
    // Monday 22 March 23:30 and Wednesday 24 March 00:30, Athens time.
    $before = ClassSession::factory()->create(['starts_at' => '2027-03-22 21:30:00', 'ends_at' => '2027-03-22 22:30:00']);
    $inside = ClassSession::factory()->create(['starts_at' => '2027-03-23 16:30:00', 'ends_at' => '2027-03-23 17:30:00']);
    $after = ClassSession::factory()->create(['starts_at' => '2027-03-23 22:30:00', 'ends_at' => '2027-03-23 23:30:00']);

    app(SaveClosure::class)->handle(null, '2027-03-23', '2027-03-23', 'Εθνική εορτή');

    expect($before->fresh()?->status)->toBe(SessionStatus::Scheduled)
        ->and($inside->fresh()?->status)->toBe(SessionStatus::Cancelled)
        ->and($inside->fresh()?->cancel_reason)->toBe('Εθνική εορτή')
        ->and($after->fresh()?->status)->toBe(SessionStatus::Scheduled);
    Event::assertDispatchedTimes(SessionCancelled::class, 1);
});

it('rejects a closure that ends before it starts', function (): void {
    app(SaveClosure::class)->handle(null, '2027-03-25', '2027-03-24', 'x');
})->throws(SessionChangeRejected::class, 'scheduling.errors.closure_ends_before_start');

it('fills the freed days when a closure is removed, without reviving cancelled sessions', function (): void {
    tuesdayClass();
    app(GenerateSessions::class)->handle();
    $closure = app(SaveClosure::class)->handle(null, '2027-03-23', '2027-03-31', 'Ανακαίνιση');
    Closure::query()->create(['starts_on' => '2027-04-13', 'ends_on' => '2027-04-13', 'reason' => 'x']);
    ClassSession::query()->where('starts_at', '2027-04-13 15:30:00')->delete(); // as if never generated

    app(DeleteClosure::class)->handle($closure);

    expect(Closure::query()->count())->toBe(1)
        ->and(ClassSession::query()->where('status', 'cancelled')->count())->toBe(2)
        ->and(ClassSession::query()->where('starts_at', '2027-04-13 15:30:00')->exists())->toBeFalse();
});
