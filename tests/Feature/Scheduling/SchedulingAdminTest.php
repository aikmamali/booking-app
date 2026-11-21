<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Service;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Filament\Resources\ClassSessionResource\Pages\ListClassSessions;
use App\Modules\Scheduling\Filament\Resources\ClosureResource\Pages\ManageClosures;
use App\Modules\Scheduling\Filament\Resources\ScheduleResource;
use App\Modules\Scheduling\Filament\Resources\ScheduleResource\Pages\ManageSchedules;
use App\Modules\Scheduling\Models\ClassSession;
use App\Modules\Scheduling\Models\Schedule;
use Filament\Actions\CreateAction;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo('2027-03-20 08:00:00');
    $this->actingAs(User::factory()->admin()->create());
});

it('shows the scheduling screens to administrators', function (string $url): void {
    $this->get($url)->assertOk();
})->with(['/admin/schedules', '/admin/closures', '/admin/class-sessions', '/admin/service-categories', '/admin/rooms']);

it('creates a weekly class from the admin and fills the calendar', function (): void {
    $service = Service::factory()->create(['default_capacity' => 6]);
    $instructor = User::factory()->instructor()->create();

    Livewire::test(ManageSchedules::class)
        ->callAction(CreateAction::class, [
            'service_id' => $service->id, 'instructor_id' => $instructor->id, 'weekday' => 2,
            'start_time' => '18:30', 'capacity' => 6, 'valid_from' => '2027-03-20',
        ])
        ->assertHasNoActionErrors();

    expect(Schedule::query()->count())->toBe(1)
        ->and(ClassSession::query()->count())->toBe(8);
});

it('offers only instructors for a schedule', function (): void {
    $instructor = User::factory()->instructor()->create(['first_name' => 'Ελένη', 'last_name' => 'Κ']);
    User::factory()->customer()->create(['first_name' => 'Πελάτης']);

    expect(ScheduleResource::instructorOptions())
        ->toBe([$instructor->id => 'Ελένη Κ']);
});

it('shows a rejection as a message instead of failing', function (): void {
    Livewire::test(ManageSchedules::class)
        ->callAction(CreateAction::class, [
            'service_id' => Service::factory()->create()->id,
            'instructor_id' => User::factory()->customer()->create()->id,
            'weekday' => 2, 'start_time' => '18:30', 'capacity' => 6, 'valid_from' => '2027-03-20',
        ])
        ->assertNotified(__('scheduling.errors.not_instructor'));

    expect(Schedule::query()->count())->toBe(0);
});

it('changes and cancels a session from the calendar', function (): void {
    $session = ClassSession::factory()->create(['starts_at' => '2027-03-23 16:30:00', 'ends_at' => '2027-03-23 17:30:00']);

    Livewire::test(ListClassSessions::class)
        ->assertCanSeeTableRecords([$session])
        ->mountTableAction('change', $session)
        ->assertTableActionDataSet(['date' => '2027-03-23', 'start_time' => '18:30', 'lock_version' => 0])
        ->setTableActionData(['capacity' => 9, 'start_time' => '19:00'])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    expect($session->fresh()?->capacity)->toBe(9)
        ->and($session->fresh()?->starts_at->format('H:i'))->toBe('17:00');

    Livewire::test(ListClassSessions::class)
        ->callTableAction('cancel', $session, ['reason' => 'Βλάβη'])
        ->assertHasNoTableActionErrors();

    expect($session->fresh()?->isCancelled())->toBeTrue();
});

it('cancels the sessions of a new closure from the admin', function (): void {
    $session = ClassSession::factory()->create(['starts_at' => '2027-03-25 16:30:00', 'ends_at' => '2027-03-25 17:30:00']);

    Livewire::test(ManageClosures::class)
        ->callAction(CreateAction::class, ['starts_on' => '2027-03-25', 'ends_on' => '2027-03-25', 'reason' => '25η Μαρτίου'])
        ->assertHasNoActionErrors();

    expect($session->fresh()?->cancel_reason)->toBe('25η Μαρτίου');
});

it('fills the calendar on demand', function (): void {
    Schedule::factory()->create(['weekday' => 2, 'start_time' => '18:30']);

    Livewire::test(ListClassSessions::class)->callAction('generate')
        ->assertNotified(__('scheduling.notices.generated', ['count' => 8]));
});

it('keeps customers and instructors out of the schedule', function (string $role, string $url): void {
    $this->actingAs(User::factory()->{$role}()->create());

    $this->get($url)->assertForbidden();
})->with(['customer', 'instructor'])->with(['/admin/schedules', '/admin/class-sessions']);
