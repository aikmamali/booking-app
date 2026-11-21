<?php

declare(strict_types=1);

use App\Modules\Identity\Livewire\CheckinCard;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\CheckinCode;
use Livewire\Livewire;

it('shows the customer card with a QR code', function (): void {
    $user = User::factory()->customer()->create(['first_name' => 'Μαρία', 'last_name' => 'Κ']);

    $this->actingAs($user)->get(route('app.card'))
        ->assertOk()
        ->assertSee('Μαρία Κ')
        ->assertSee('<svg', false);
});

it('signs the code so it can be verified at the desk', function (): void {
    $user = User::factory()->customer()->create();
    $code = CheckinCode::for($user);

    expect($code)->toStartWith('CHK1.'.$user->checkin_token.'.')
        ->and(CheckinCode::resolve($code)?->id)->toBe($user->id);
});

it('rejects forged, edited or reissued codes', function (): void {
    $user = User::factory()->customer()->create();
    $other = User::factory()->customer()->create();
    $code = CheckinCode::for($user);
    [, , $signature] = explode('.', $code);

    expect(CheckinCode::resolve('CHK1.'.$other->checkin_token.'.'.$signature))->toBeNull()
        ->and(CheckinCode::resolve($user->checkin_token))->toBeNull()
        ->and(CheckinCode::resolve('garbage'))->toBeNull();

    Livewire::actingAs($user)->test(CheckinCard::class)
        ->call('reissue')
        ->assertSet('status', __('identity.card.reissued'));

    expect(CheckinCode::resolve($code))->toBeNull()
        ->and(CheckinCode::resolve(CheckinCode::for($user->fresh()))?->id)->toBe($user->id);
});

it('never resolves an anonymised account', function (): void {
    $user = User::factory()->customer()->create();
    $code = CheckinCode::for($user);
    $user->forceFill(['anonymized_at' => now()])->save();

    expect(CheckinCode::resolve($code))->toBeNull();
});

it('sends guests to the login page', function (): void {
    $this->get(route('app.card'))->assertRedirect(route('app.login'));
});
