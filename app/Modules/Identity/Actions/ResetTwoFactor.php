<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Exceptions\TwoFactorRejected;
use App\Modules\Identity\Models\User;

/**
 * For a staff member who lost both phone and recovery codes: another admin clears 2FA,
 * and the next login asks for a new setup. Nobody can reset their own. Without an actor
 * the reset comes from the server console (identity:reset-two-factor).
 */
final class ResetTwoFactor
{
    public function handle(User $user, ?User $actor = null): void
    {
        if ($actor instanceof User && $user->is($actor)) {
            throw TwoFactorRejected::because('identity.two_factor.cannot_reset_self');
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_step' => null,
        ])->save();

        activity()->performedOn($user)->causedBy($actor)->withProperties(['via' => $actor instanceof User ? 'admin' : 'console'])->log('two_factor_reset');
    }
}
