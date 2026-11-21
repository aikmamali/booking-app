<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * ID-02 (reset link, 60 minutes) and ID-05 (invitation link). The broker decides which
 * token table and expiry apply.
 */
final class ResetPassword
{
    public const string RESET_BROKER = 'users';

    public const string INVITATION_BROKER = 'invitations';

    public function handle(string $email, string $token, string $password, string $broker = self::RESET_BROKER): void
    {
        $status = Password::broker($broker)->reset(
            ['email' => $email, 'token' => $token, 'password' => $password],
            function (User $user, string $password) use ($broker): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ]);

                // Opening the invitation link proves the address belongs to the user.
                if ($broker === self::INVITATION_BROKER && $user->email_verified_at === null) {
                    $user->markEmailAsVerified();
                }

                $user->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __(is_string($status) ? $status : 'passwords.token')]);
        }
    }
}
