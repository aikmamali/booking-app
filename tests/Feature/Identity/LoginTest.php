<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Middleware\ApplySessionLifetime;
use App\Modules\Identity\Livewire\Login;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Settings\IdentitySettings;
use Illuminate\Session\Middleware\StartSession;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function attemptLogin(string $email, string $password, bool $remember = false): Testable
{
    return Livewire::test(Login::class)
        ->set('email', $email)
        ->set('password', $password)
        ->set('remember', $remember)
        ->call('login');
}

it('logs a customer in and records the login time', function (): void {
    $this->travelTo('2026-11-21 09:30:00');
    $user = User::factory()->customer()->create(['email' => 'maria@example.com']);

    attemptLogin('maria@example.com', 'password')->assertHasNoErrors()->assertRedirect(route('app.home'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()?->last_login_at?->toDateTimeString())->toBe('2026-11-21 09:30:00');
});

it('sets a remember-me cookie for the PWA when asked', function (): void {
    $user = User::factory()->customer()->create();

    attemptLogin($user->email, 'password', remember: true)->assertHasNoErrors();

    expect($user->fresh()?->remember_token)->not->toBeNull();
});

it('rejects a wrong password', function (): void {
    $user = User::factory()->customer()->create();

    attemptLogin($user->email, 'wrong-password')->assertHasErrors(['email']);

    $this->assertGuest();
});

it('rejects anonymised accounts', function (): void {
    $user = User::factory()->customer()->create(['anonymized_at' => now()]);

    attemptLogin($user->email, 'password')->assertHasErrors(['email']);

    $this->assertGuest();
});

it('locks out after five failed attempts per minute', function (): void {
    $user = User::factory()->customer()->create();

    foreach (range(1, 5) as $ignored) {
        attemptLogin($user->email, 'wrong-password');
    }

    attemptLogin($user->email, 'password')
        ->assertHasErrors(['email'])
        ->assertSee(trim(explode(':seconds', __('auth.throttle'))[0]));
    $this->assertGuest();

    $this->travel(61)->seconds();

    attemptLogin($user->email, 'password')->assertHasNoErrors();
    $this->assertAuthenticated();
});

it('sends unverified customers to the verification notice', function (): void {
    $user = User::factory()->customer()->unverified()->create();

    $this->actingAs($user)->get(route('app.home'))->assertRedirect(route('verification.notice'));
});

it('logs out and invalidates the session', function (): void {
    $user = User::factory()->customer()->create();

    $this->actingAs($user)->post(route('app.logout'))->assertRedirect(route('app.login'));

    $this->assertGuest();
});

it('takes the session lifetime from settings', function (): void {
    $settings = app(IdentitySettings::class);
    $settings->session_lifetime_minutes = 45;
    $settings->save();

    $this->get(route('app.login'))->assertOk();

    expect(config('session.lifetime'))->toBe(45);
});

it('applies the session lifetime before the session starts', function (): void {
    $web = app('router')->getMiddlewareGroups()['web'];

    expect(array_search(ApplySessionLifetime::class, $web, true))
        ->toBeLessThan(array_search(StartSession::class, $web, true));
});
