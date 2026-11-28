<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\Totp;

/**
 * ID-07: issues a new, not yet confirmed secret. 2FA counts as enabled only after
 * ConfirmTwoFactorSetup proves the authenticator app holds it.
 */
final readonly class StartTwoFactorSetup
{
    public function __construct(private Totp $totp) {}

    public function handle(User $user): string
    {
        $secret = $this->totp->newSecret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_step' => null,
        ])->save();

        return $secret;
    }
}
