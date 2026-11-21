<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use Illuminate\Support\Facades\Password;

/**
 * ID-02. Always reports success, so the form does not reveal which emails have accounts.
 */
final class SendPasswordResetLink
{
    public function handle(string $email): void
    {
        Password::broker()->sendResetLink(['email' => $email, 'anonymized_at' => null]);
    }
}
