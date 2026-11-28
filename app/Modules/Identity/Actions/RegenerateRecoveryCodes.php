<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Exceptions\TwoFactorRejected;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\RecoveryCodes;

/**
 * Replaces all recovery codes; the old ones stop working.
 */
final class RegenerateRecoveryCodes
{
    /**
     * @return list<string>
     */
    public function handle(User $user): array
    {
        if (! $user->hasTwoFactorEnabled()) {
            throw TwoFactorRejected::because('identity.two_factor.not_enabled');
        }

        $codes = RecoveryCodes::generate();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();
        activity()->performedOn($user)->causedBy($user)->log('two_factor_recovery_codes_regenerated');

        return $codes;
    }
}
