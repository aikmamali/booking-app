<?php

declare(strict_types=1);

use App\Modules\Identity\Actions\InviteUser;
use App\Modules\Identity\Data\InviteUserData;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Livewire\AcceptInvitation;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Notifications\InvitationNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

function inviteCustomer(): array
{
    Notification::fake();

    $user = app(InviteUser::class)->handle(InviteUserData::from([
        'first_name' => 'Κώστας', 'last_name' => 'Δημητρίου', 'email' => 'kostas@example.com',
        'phone' => '6900000000', 'role' => Role::Customer->value, 'locale' => 'el',
    ]));

    $token = null;
    Notification::assertSentTo($user, InvitationNotification::class, function (InvitationNotification $n) use (&$token): bool {
        $token = $n->token;

        return true;
    });

    return [$user, (string) $token];
}

it('sends an invitation mail with a link to set the password', function (): void {
    [$user, $token] = inviteCustomer();

    $mail = (new InvitationNotification($token))->toMail($user);

    expect($mail->actionUrl)->toContain('/app/invitation/'.$token)
        ->and($mail->subject)->toContain('Πρόσκληση');
});

it('lets the invited person set a password, which also verifies the email', function (): void {
    [$user, $token] = inviteCustomer();

    $this->get(route('app.invitation', ['token' => $token, 'email' => $user->email]))->assertOk();

    Livewire::withQueryParams(['email' => $user->email])
        ->test(AcceptInvitation::class, ['token' => $token])
        ->assertSet('email', $user->email)
        ->set('password', 'mypassword1')
        ->set('password_confirmation', 'mypassword1')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('app.login'));

    $user->refresh();
    expect(Hash::check('mypassword1', $user->password))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

it('rejects an invitation older than seven days', function (): void {
    [$user, $token] = inviteCustomer();
    $this->travel(7)->days();
    $this->travel(1)->minutes();

    Livewire::test(AcceptInvitation::class, ['token' => $token])
        ->set('email', $user->email)
        ->set('password', 'mypassword1')
        ->set('password_confirmation', 'mypassword1')
        ->call('save')
        ->assertHasErrors('email');
});

it('does not accept a password reset token as an invitation', function (): void {
    [$user] = inviteCustomer();
    $resetToken = Password::broker()->createToken($user);

    Livewire::test(AcceptInvitation::class, ['token' => $resetToken])
        ->set('email', $user->email)
        ->set('password', 'mypassword1')
        ->set('password_confirmation', 'mypassword1')
        ->call('save')
        ->assertHasErrors('email');
});

it('creates the first administrator from the console', function (): void {
    Notification::fake();

    $this->artisan('identity:create-admin', ['email' => 'Owner@Studio.gr', 'first_name' => 'Κατερίνα', 'last_name' => 'Μ'])
        ->assertSuccessful();

    $admin = User::query()->where('email', 'owner@studio.gr')->sole();
    expect($admin->hasRole(Role::Admin->value))->toBeTrue();
    Notification::assertSentTo($admin, InvitationNotification::class);
});

it('refuses to create an administrator with an email in use', function (): void {
    User::factory()->create(['email' => 'owner@studio.gr']);

    $this->artisan('identity:create-admin', ['email' => 'owner@studio.gr', 'first_name' => 'Α', 'last_name' => 'Β'])
        ->assertFailed();
});
