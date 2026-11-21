<?php

declare(strict_types=1);

use App\Modules\Identity\Actions\ChangePassword;
use App\Modules\Identity\Livewire\ChangePasswordForm;
use App\Modules\Identity\Livewire\ForgotPassword;
use App\Modules\Identity\Livewire\ResetPasswordForm;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(fn () => Notification::fake());

it('emails a reset link to an existing customer', function (): void {
    $user = User::factory()->customer()->create();

    Livewire::test(ForgotPassword::class)->set('email', $user->email)->call('send')
        ->assertHasNoErrors()
        ->assertSet('status', __('passwords.sent'));

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

it('gives the same answer for unknown emails without sending anything', function (): void {
    Livewire::test(ForgotPassword::class)->set('email', 'nobody@example.com')->call('send')
        ->assertSet('status', __('passwords.sent'));

    Notification::assertNothingSent();
});

it('expires reset links after 60 minutes', function (): void {
    expect(config('auth.passwords.users.expire'))->toBe(60);
});

it('resets the password with a valid token', function (): void {
    $user = User::factory()->customer()->create();
    $token = Password::broker()->createToken($user);

    Livewire::test(ResetPasswordForm::class, ['token' => $token])
        ->set('email', $user->email)
        ->set('password', 'newsecret1')
        ->set('password_confirmation', 'newsecret1')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('app.login'));

    expect(Hash::check('newsecret1', (string) $user->fresh()?->password))->toBeTrue();
});

it('rejects an invalid or expired reset token', function (int $minutesLater): void {
    $user = User::factory()->customer()->create();
    $token = Password::broker()->createToken($user);
    $this->travel($minutesLater)->minutes();

    Livewire::test(ResetPasswordForm::class, ['token' => $minutesLater === 0 ? 'wrong-token' : $token])
        ->set('email', $user->email)
        ->set('password', 'newsecret1')
        ->set('password_confirmation', 'newsecret1')
        ->call('save')
        ->assertHasErrors('email');

    expect(Hash::check('password', (string) $user->fresh()?->password))->toBeTrue();
})->with(['wrong token' => 0, 'expired' => 61]);

it('changes the password when the current one is correct', function (): void {
    $user = User::factory()->customer()->create();

    Livewire::actingAs($user)->test(ChangePasswordForm::class)
        ->set('current_password', 'password')
        ->set('password', 'newsecret1')
        ->set('password_confirmation', 'newsecret1')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('status', __('identity.password.changed'))
        ->assertSet('current_password', '');

    expect(Hash::check('newsecret1', (string) $user->fresh()?->password))->toBeTrue();
});

it('refuses to change the password without the current one', function (): void {
    $user = User::factory()->customer()->create();

    Livewire::actingAs($user)->test(ChangePasswordForm::class)
        ->set('current_password', 'wrong')
        ->set('password', 'newsecret1')
        ->set('password_confirmation', 'newsecret1')
        ->call('save')
        ->assertHasErrors('current_password');

    expect(Hash::check('password', (string) $user->fresh()?->password))->toBeTrue();
});

it('validates the new password in the action as well', function (): void {
    $user = User::factory()->customer()->create();

    app(ChangePassword::class)->handle($user, 'not-the-password', 'newsecret1');
})->throws(ValidationException::class);
