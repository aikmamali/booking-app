<?php

declare(strict_types=1);

use App\Modules\Identity\Livewire\VerifyEmailNotice;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

it('verifies the email from the signed link', function (): void {
    $user = User::factory()->customer()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect(route('app.home'));

    expect($user->fresh()?->hasVerifiedEmail())->toBeTrue();
});

it('rejects a tampered verification link', function (): void {
    $user = User::factory()->customer()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1('someone-else@example.com'),
    ]);

    $this->actingAs($user)->get($url)->assertForbidden();

    expect($user->fresh()?->hasVerifiedEmail())->toBeFalse();
});

it('resends the verification email, at most three times a minute', function (): void {
    Notification::fake();
    $user = User::factory()->customer()->unverified()->create();
    $component = Livewire::actingAs($user)->test(VerifyEmailNotice::class);

    foreach (range(1, 4) as $ignored) {
        $component->call('resend');
    }

    Notification::assertSentToTimes($user, VerifyEmail::class, 3);
    $component->assertSet('status', __('passwords.throttled'));
});
