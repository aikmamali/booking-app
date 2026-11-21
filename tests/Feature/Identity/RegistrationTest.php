<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Livewire\Register;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function registrationForm(array $overrides = []): array
{
    return [
        'first_name' => 'Μαρία',
        'last_name' => 'Παπαδοπούλου',
        'email' => 'maria@example.com',
        'phone' => '691 234 5678',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'accept_terms' => true,
        'marketing_consent' => false,
        ...$overrides,
    ];
}

function submitRegistration(array $overrides = []): Testable
{
    $component = Livewire::test(Register::class);

    foreach (registrationForm($overrides) as $field => $value) {
        $component->set($field, $value);
    }

    return $component->call('register');
}

beforeEach(function (): void {
    Notification::fake();
    $this->travelTo('2026-11-21 10:00:00');
});

it('registers a customer, records terms acceptance and sends the verification email', function (): void {
    submitRegistration(['email' => ' Maria@Example.com '])
        ->assertHasNoErrors()
        ->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', 'maria@example.com')->sole();

    expect($user->hasRole(Role::Customer->value))->toBeTrue()
        ->and($user->phone)->toBe('+306912345678')
        ->and($user->terms_accepted_version)->toBe('2026-11-draft')
        ->and($user->terms_accepted_at?->toDateTimeString())->toBe('2026-11-21 10:00:00')
        ->and($user->marketing_consent_at)->toBeNull()
        ->and($user->checkin_token)->toHaveLength(40)
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->locale->value)->toBe('el');

    $this->assertAuthenticatedAs($user);
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('records marketing consent with its time', function (): void {
    submitRegistration(['marketing_consent' => true])->assertHasNoErrors();

    expect(User::query()->sole()->marketing_consent_at?->toDateTimeString())->toBe('2026-11-21 10:00:00');
});

it('rejects invalid registrations', function (array $overrides, string $field): void {
    submitRegistration($overrides)->assertHasErrors($field);

    expect(User::query()->count())->toBe(0);
})->with([
    'missing first name' => [['first_name' => ''], 'first_name'],
    'missing last name' => [['last_name' => ''], 'last_name'],
    'invalid email' => [['email' => 'not-an-email'], 'email'],
    'missing phone' => [['phone' => ''], 'phone'],
    'invalid phone' => [['phone' => '12345'], 'phone'],
    'short password' => [['password' => 'abc1', 'password_confirmation' => 'abc1'], 'password'],
    'password without numbers' => [['password' => 'abcdefghij', 'password_confirmation' => 'abcdefghij'], 'password'],
    'unconfirmed password' => [['password_confirmation' => 'other123'], 'password'],
    'terms not accepted' => [['accept_terms' => false], 'accept_terms'],
]);

it('rejects an email that is already registered', function (): void {
    User::factory()->create(['email' => 'maria@example.com']);

    submitRegistration()->assertHasErrors('email');
});

it('throttles repeated registrations from the same address', function (): void {
    foreach (range(1, 5) as $i) {
        submitRegistration(['email' => "user{$i}@example.com"])->assertHasNoErrors();
        auth()->logout();
    }

    submitRegistration(['email' => 'user6@example.com'])->assertHasErrors('email');
});
