<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\User;

/**
 * The check-in token is random and can be reissued if a card is lost (spec section 11).
 */
final class RegenerateCheckinToken
{
    public function handle(User $user): string
    {
        $token = User::newCheckinToken();

        $user->forceFill(['checkin_token' => $token])->save();

        return $token;
    }
}
