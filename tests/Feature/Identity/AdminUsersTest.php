<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Filament\Resources\UserResource;
use App\Modules\Identity\Filament\Resources\UserResource\Pages\CreateUser;
use App\Modules\Identity\Filament\Resources\UserResource\Pages\EditUser;
use App\Modules\Identity\Filament\Resources\UserResource\Pages\ListUsers;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Notifications\InvitationNotification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create(['phone' => null]);
});

it('lets only administrators into the admin panel', function (?Role $role, int $status): void {
    $user = $role instanceof Role ? User::factory()->withRole($role)->create() : $this->admin;

    $this->actingAs($user)->get('/admin')->assertStatus($status);
})->with([
    'admin' => [null, 200],
    'customer' => [Role::Customer, 403],
    'instructor' => [Role::Instructor, 403],
]);

it('lists users with their roles', function (): void {
    $customer = User::factory()->customer()->create(['last_name' => 'Παπαδάκη']);
    $anonymised = User::factory()->customer()->create(['anonymized_at' => now()]);

    Livewire::actingAs($this->admin)->test(ListUsers::class)
        ->assertCanSeeTableRecords([$customer, $this->admin])
        ->assertCanNotSeeTableRecords([$anonymised])
        ->assertSee('Πελάτης');
});

it('creates a customer and emails an invitation', function (): void {
    Notification::fake();

    Livewire::actingAs($this->admin)->test(CreateUser::class)
        ->fillForm([
            'first_name' => 'Νίκος',
            'last_name' => 'Αλεξίου',
            'email' => 'Nikos@Example.com',
            'phone' => '6911111111',
            'locale' => 'el',
            'role' => Role::Customer->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::query()->where('email', 'nikos@example.com')->sole();
    expect($user->hasRole(Role::Customer->value))->toBeTrue()
        ->and($user->phone)->toBe('+306911111111')
        ->and($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, InvitationNotification::class);
});

it('requires a phone for customers but not for staff', function (): void {
    Notification::fake();
    $form = ['first_name' => 'Α', 'last_name' => 'Β', 'email' => 'staff@example.com', 'phone' => '', 'locale' => 'el'];

    Livewire::actingAs($this->admin)->test(CreateUser::class)
        ->fillForm([...$form, 'role' => Role::Customer->value])->call('create')
        ->assertHasFormErrors(['phone']);

    Livewire::actingAs($this->admin)->test(CreateUser::class)
        ->fillForm([...$form, 'role' => Role::Instructor->value])->call('create')
        ->assertHasNoFormErrors();
});

it('rejects a duplicate email or an invalid phone', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::actingAs($this->admin)->test(CreateUser::class)
        ->fillForm([
            'first_name' => 'Α', 'last_name' => 'Β', 'email' => 'taken@example.com',
            'phone' => '123', 'locale' => 'el', 'role' => Role::Customer->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'unique', 'phone']);
});

it('edits a user and changes their role', function (): void {
    $user = User::factory()->customer()->create();

    Livewire::actingAs($this->admin)->test(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertFormSet(['role' => Role::Customer->value])
        ->fillForm(['first_name' => 'Γιώργος', 'role' => Role::Instructor->value])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();
    expect($user->first_name)->toBe('Γιώργος')
        ->and($user->getRoleNames()->all())->toBe([Role::Instructor->value]);
});

it('does not let an administrator remove their own admin role', function (): void {
    Livewire::actingAs($this->admin)->test(EditUser::class, ['record' => $this->admin->getRouteKey()])
        ->fillForm(['role' => Role::Customer->value])
        ->call('save');

    expect($this->admin->fresh()?->hasRole(Role::Admin->value))->toBeTrue();
});

it('resends an invitation, issues a check-in code and anonymises from the table', function (): void {
    Notification::fake();
    $user = User::factory()->customer()->create(['last_login_at' => null]);
    $token = $user->checkin_token;

    Livewire::actingAs($this->admin)->test(ListUsers::class)
        ->callTableAction('resendInvitation', $user)
        ->callTableAction('regenerateCheckin', $user)
        ->callTableAction('anonymize', $user);

    Notification::assertSentTo($user, InvitationNotification::class);
    $user->refresh();
    expect($user->checkin_token)->not->toBe($token)
        ->and($user->isAnonymized())->toBeTrue();
});

it('does not let an administrator anonymise themselves', function (): void {
    Livewire::actingAs($this->admin)->test(ListUsers::class)
        ->assertTableActionHidden('anonymize', $this->admin);
});

it('authorises user management through the policy', function (): void {
    $customer = User::factory()->customer()->create();
    $instructor = User::factory()->instructor()->create();

    expect($this->admin->can('viewAny', User::class))->toBeTrue()
        ->and($this->admin->can('update', $instructor))->toBeTrue()
        ->and($customer->can('viewAny', User::class))->toBeFalse()
        ->and($instructor->can('update', $customer))->toBeFalse()
        ->and(UserResource::canViewAny())->toBeFalse();
});

it('lets a future role with user permissions manage customers but not staff', function (): void {
    $role = Spatie\Permission\Models\Role::create(['name' => 'reception', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_any_user', 'update_user']);
    $reception = User::factory()->create();
    $reception->assignRole('reception');

    expect($reception->can('update', User::factory()->customer()->create()))->toBeTrue()
        ->and($reception->can('update', User::factory()->instructor()->create()))->toBeFalse()
        ->and($reception->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});
