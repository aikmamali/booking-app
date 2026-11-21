<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Identity\Notifications\InvitationNotification;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;

/**
 * (Re)sends the invitation link. A new token replaces any previous one.
 */
final class SendInvitation
{
    public function handle(User $user): void
    {
        /** @var PasswordBroker $broker */
        $broker = Password::broker(ResetPassword::INVITATION_BROKER);

        $user->notify(new InvitationNotification($broker->createToken($user)));
    }
}
