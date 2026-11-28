<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Exceptions\TwoFactorRejected;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\RecoveryCodes;
use App\Modules\Identity\Support\Totp;
use Carbon\CarbonImmutable;

/**
 * Enables 2FA once the first code from the app matches. Returns the recovery codes,
 * which are shown to the user once.
 */
final readonly class ConfirmTwoFactorSetup
{
    public function __construct(private Totp $totp) {}

    /**
     * @return list<string>
     */
    public function handle(User $user, string $code): array
    {
        if ($user->two_factor_secret === null || $user->hasTwoFactorEnabled()) {
            throw TwoFactorRejected::because('identity.two_factor.not_in_setup');
        }

        $step = $this->totp->verify($user->two_factor_secret, $code, null);

        if ($step === null) {
            throw TwoFactorRejected::because('identity.two_factor.invalid_code');
        }

        $codes = RecoveryCodes::generate();

        $user->forceFill([
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => CarbonImmutable::now(),
            'two_factor_last_step' => $step,
        ])->save();

        activity()->performedOn($user)->causedBy($user)->log('two_factor_enabled');

        return $codes;
    }
}
