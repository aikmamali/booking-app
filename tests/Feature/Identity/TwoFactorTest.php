<?php

declare(strict_types=1);

use App\Modules\Identity\Actions\ConfirmTwoFactorSetup;
use App\Modules\Identity\Actions\RegenerateRecoveryCodes;
use App\Modules\Identity\Actions\ResetTwoFactor;
use App\Modules\Identity\Actions\StartTwoFactorSetup;
use App\Modules\Identity\Actions\VerifyTwoFactorChallenge;
use App\Modules\Identity\Exceptions\TwoFactorRejected;
use App\Modules\Identity\Filament\Auth\TwoFactorChallenge;
use App\Modules\Identity\Filament\Auth\TwoFactorSetup;
use App\Modules\Identity\Filament\Resources\UserResource\Pages\ListUsers;
use App\Modules\Identity\Http\Middleware\RequireTwoFactor;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\Totp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function (): void {
    config(['identity.two_factor.enforced' => true]);
});

function codeFor(User $user): string
{
    return (new Totp)->currentCode((string) $user->refresh()->two_factor_secret);
}

describe('admin panel gate', function (): void {
    it('sends staff without 2FA to the setup page', function (): void {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin')->assertRedirect(route('filament.admin.two-factor.setup'));
    });

    it('sends staff with 2FA to the challenge until they pass it', function (): void {
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create())
            ->get('/admin/users')->assertRedirect(route('filament.admin.two-factor.challenge'));
    });

    it('opens the panel once the challenge is passed in this session', function (): void {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $this->actingAs($admin);

        Livewire::test(TwoFactorChallenge::class)
            ->set('data.code', codeFor($admin))
            ->call('verify')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->get('/admin')->assertOk();
    });

    it('leaves the 2FA pages reachable', function (): void {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('filament.admin.two-factor.setup'))->assertOk();
    });

    it('does nothing when enforcement is off', function (): void {
        config(['identity.two_factor.enforced' => false]);

        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();
    });
});

describe('setup', function (): void {
    it('stores the secret encrypted and keeps it across reloads', function (): void {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(TwoFactorSetup::class);
        $secret = $admin->refresh()->two_factor_secret;
        Livewire::test(TwoFactorSetup::class);

        expect($secret)->not->toBeNull()
            ->and($admin->refresh()->two_factor_secret)->toBe($secret)
            ->and($admin->hasTwoFactorEnabled())->toBeFalse()
            ->and(DB::table('users')->where('id', $admin->id)->value('two_factor_secret'))->not->toBe($secret);
    });

    it('enables 2FA with a valid code and shows 8 recovery codes once', function (): void {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $page = Livewire::test(TwoFactorSetup::class);
        $page->set('data.code', codeFor($admin))->call('confirm')->assertHasNoErrors();

        expect($page->get('recoveryCodes'))->toHaveCount(8)
            ->and($admin->refresh()->hasTwoFactorEnabled())->toBeTrue();
        $this->get('/admin')->assertOk();
    });

    it('rejects a wrong code', function (): void {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(TwoFactorSetup::class)
            ->set('data.code', '000000')->call('confirm')->assertHasErrors(['data.code']);

        expect($admin->refresh()->hasTwoFactorEnabled())->toBeFalse();
    });

    it('cannot confirm without a setup in progress', function (): void {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        app(ConfirmTwoFactorSetup::class)->handle($admin, codeFor($admin));
    })->throws(TwoFactorRejected::class);

    it('replaces recovery codes on request', function (): void {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $codes = app(RegenerateRecoveryCodes::class)->handle($admin);

        expect($codes)->toHaveCount(8)->not->toContain('aaaaa-11111')
            ->and($admin->refresh()->two_factor_recovery_codes)->toBe($codes);
    });

    it('cannot regenerate recovery codes without 2FA', function (): void {
        app(RegenerateRecoveryCodes::class)->handle(User::factory()->admin()->create());
    })->throws(TwoFactorRejected::class);

    it('starting again discards the old secret', function (): void {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $old = $admin->two_factor_secret;

        app(StartTwoFactorSetup::class)->handle($admin);

        expect($admin->refresh()->two_factor_secret)->not->toBe($old)
            ->and($admin->hasTwoFactorEnabled())->toBeFalse();
    });
});

describe('challenge', function (): void {
    it('refuses the same code twice', function (): void {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $verify = app(VerifyTwoFactorChallenge::class);
        $code = codeFor($admin);

        $verify->handle($admin, $code);

        expect($admin->refresh()->two_factor_last_step)->toBeInt()
            ->and(fn () => $verify->handle($admin, $code))->toThrow(TwoFactorRejected::class);
    });

    it('accepts each recovery code once', function (): void {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $verify = app(VerifyTwoFactorChallenge::class);

        $verify->handle($admin, ' AAAAA-33333 ');

        expect($admin->refresh()->two_factor_recovery_codes)->toHaveCount(7)->not->toContain('aaaaa-33333');
        expect(fn () => $verify->handle($admin, 'aaaaa-33333'))->toThrow(TwoFactorRejected::class);
    });

    it('rejects a wrong code with a validation error', function (): void {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $this->actingAs($admin);

        Livewire::test(TwoFactorChallenge::class)
            ->set('data.code', '123')->call('verify')->assertHasErrors(['data.code']);

        $this->get('/admin')->assertRedirect(route('filament.admin.two-factor.challenge'));
    });

    it('locks out after 5 wrong attempts per minute', function (): void {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $verify = app(VerifyTwoFactorChallenge::class);

        foreach (range(1, 5) as $ignored) {
            try {
                $verify->handle($admin, '000000');
            } catch (TwoFactorRejected) {
            }
        }

        expect(fn () => $verify->handle($admin, codeFor($admin)))
            ->toThrow(TwoFactorRejected::class, 'identity.two_factor.throttled');

        RateLimiter::clear('two-factor:'.$admin->id);
        $verify->handle($admin, codeFor($admin));
    });

    it('rejects users without 2FA', function (): void {
        app(VerifyTwoFactorChallenge::class)->handle(User::factory()->admin()->create(), '123456');
    })->throws(TwoFactorRejected::class);
});

describe('reset by another admin', function (): void {
    it('clears 2FA so the next login sets it up again', function (): void {
        $actor = User::factory()->admin()->create();
        $admin = User::factory()->admin()->withTwoFactor()->create();

        app(ResetTwoFactor::class)->handle($admin, $actor);

        $admin->refresh();
        expect($admin->two_factor_secret)->toBeNull()
            ->and($admin->two_factor_recovery_codes)->toBeNull()
            ->and($admin->hasTwoFactorEnabled())->toBeFalse();
        $this->assertDatabaseHas('activity_log', ['description' => 'two_factor_reset', 'subject_id' => $admin->id, 'causer_id' => $actor->id]);
    });

    it('cannot reset your own', function (): void {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        app(ResetTwoFactor::class)->handle($admin, $admin);
    })->throws(TwoFactorRejected::class);

    it('offers the reset action on others only', function (): void {
        $actor = User::factory()->admin()->withTwoFactor()->create();
        $other = User::factory()->admin()->withTwoFactor()->create();
        config(['identity.two_factor.enforced' => false]);
        $this->actingAs($actor);

        Livewire::test(ListUsers::class)
            ->assertTableActionVisible('resetTwoFactor', $other)
            ->assertTableActionHidden('resetTwoFactor', $actor)
            ->callTableAction('resetTwoFactor', $other);

        expect($other->refresh()->two_factor_secret)->toBeNull();
    });
});

it('returns to the interrupted admin page after the challenge', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $this->actingAs($admin)->get('/admin/users');

    Livewire::test(TwoFactorChallenge::class)
        ->set('data.code', codeFor($admin))
        ->call('verify')
        ->assertRedirect(url('/admin/users'));
});

it('also guards Livewire requests from admin pages', function (): void {
    expect(Livewire::getPersistentMiddleware())->toContain(RequireTwoFactor::class);
});

it('can be reset from the server console', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();

    $this->artisan('identity:reset-two-factor', ['email' => strtoupper($admin->email)])->assertSuccessful();
    $this->artisan('identity:reset-two-factor', ['email' => 'nobody@example.com'])->assertFailed();

    expect($admin->refresh()->two_factor_secret)->toBeNull();
    $this->assertDatabaseHas('activity_log', ['description' => 'two_factor_reset', 'subject_id' => $admin->id, 'causer_id' => null]);
});
