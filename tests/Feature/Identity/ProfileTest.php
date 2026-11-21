<?php

declare(strict_types=1);

use App\Modules\Identity\Actions\DeleteAccount;
use App\Modules\Identity\Actions\RegenerateCheckinToken;
use App\Modules\Identity\Events\UserDeleted;
use App\Modules\Identity\Livewire\DeleteAccountForm;
use App\Modules\Identity\Livewire\EditProfileForm;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function profileForm(User $user): Testable
{
    return Livewire::actingAs($user)->test(EditProfileForm::class);
}

it('shows the profile page', function (): void {
    $user = User::factory()->customer()->create(['first_name' => 'Ελένη']);

    $this->actingAs($user)->get(route('app.profile'))->assertOk()
        ->assertSeeLivewire(EditProfileForm::class)
        ->assertSeeLivewire(DeleteAccountForm::class);

    profileForm($user)->assertSet('first_name', 'Ελένη');
});

it('updates the profile details', function (): void {
    Notification::fake();
    $user = User::factory()->customer()->create();

    profileForm($user)
        ->set('first_name', 'Ελένη')
        ->set('last_name', 'Γεωργίου')
        ->set('phone', '6987654321')
        ->set('phone_alt', '2101234567')
        ->set('vat_number', '123456789')
        ->set('locale', 'en')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('app.profile'));

    $user->refresh();
    expect($user->first_name)->toBe('Ελένη')
        ->and($user->phone)->toBe('+306987654321')
        ->and($user->phone_alt)->toBe('+302101234567')
        ->and($user->vat_number)->toBe('123456789')
        ->and($user->locale->value)->toBe('en')
        ->and($user->hasVerifiedEmail())->toBeTrue();

    Notification::assertNothingSent();
});

it('asks for verification again when the email changes', function (): void {
    Notification::fake();
    $user = User::factory()->customer()->create();

    profileForm($user)->set('email', 'new@example.com')->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('verification.notice'));

    expect($user->fresh()?->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('rejects invalid profile data', function (string $field, string $value): void {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->customer()->create();

    profileForm($user)->set($field, $value)->call('save')->assertHasErrors($field);
})->with([
    ['first_name', ''],
    ['email', 'taken@example.com'],
    ['phone', ''],
    ['phone', '123'],
    ['phone_alt', '123'],
    ['vat_number', '12345'],
    ['locale', 'fr'],
]);

it('anonymises the account on deletion and logs the customer out', function (): void {
    Event::fake([UserDeleted::class]);
    $user = User::factory()->customer()->create([
        'first_name' => 'Μαρία', 'phone' => '+306912345678', 'vat_number' => '123456789',
    ]);
    $oldToken = $user->checkin_token;

    Livewire::actingAs($user)->test(DeleteAccountForm::class)
        ->set('password', 'password')
        ->set('confirm', true)
        ->call('delete')
        ->assertHasNoErrors()
        ->assertRedirect(route('app.login'));

    $user->refresh();
    expect($user->first_name)->not->toBe('Μαρία')
        ->and($user->email)->toEndWith('@anonymized.invalid')
        ->and($user->phone)->toBeNull()
        ->and($user->vat_number)->toBeNull()
        ->and($user->checkin_token)->not->toBe($oldToken)
        ->and($user->isAnonymized())->toBeTrue()
        ->and($user->getRoleNames())->toBeEmpty();

    $this->assertGuest();
    Event::assertDispatched(UserDeleted::class, fn (UserDeleted $e): bool => $e->userId === $user->id);
});

it('requires the password and an explicit confirmation to delete', function (array $input, string $field): void {
    $user = User::factory()->customer()->create();

    Livewire::actingAs($user)->test(DeleteAccountForm::class)
        ->set($input)
        ->call('delete')
        ->assertHasErrors($field);

    expect($user->fresh()?->isAnonymized())->toBeFalse();
})->with([
    'wrong password' => [['password' => 'wrong', 'confirm' => true], 'password'],
    'not confirmed' => [['password' => 'password', 'confirm' => false], 'confirm'],
]);

it('removes the deleted user\'s other sessions', function (): void {
    $user = User::factory()->customer()->create();
    DB::table('sessions')->insert([
        'id' => 'other-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time(),
    ]);

    app(DeleteAccount::class)->handle($user);

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
});

it('dispatches UserDeleted only after the transaction commits', function (): void {
    Event::fake([UserDeleted::class]);
    $user = User::factory()->customer()->create();

    DB::beginTransaction();
    app(DeleteAccount::class)->handle($user);
    Event::assertNotDispatched(UserDeleted::class);
    DB::commit();

    Event::assertDispatched(UserDeleted::class);
});

it('issues a new unique check-in token', function (): void {
    $user = User::factory()->customer()->create();
    $old = $user->checkin_token;

    $new = app(RegenerateCheckinToken::class)->handle($user);

    expect($new)->not->toBe($old)->toHaveLength(40)
        ->and($user->fresh()?->checkin_token)->toBe($new);
});
