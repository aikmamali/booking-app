<?php

declare(strict_types=1);

namespace App\Modules\Identity\Console;

use App\Modules\Identity\Actions\InviteUser;
use App\Modules\Identity\Data\InviteUserData;
use App\Modules\Identity\Enums\Locale;
use App\Modules\Identity\Enums\Role;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * Bootstraps the first administrator on a fresh installation. The person receives an
 * invitation email to set their password.
 */
final class CreateAdmin extends Command
{
    protected $signature = 'identity:create-admin {email} {first_name} {last_name}';

    protected $description = 'Create an administrator account and email an invitation to set the password';

    public function handle(InviteUser $invite): int
    {
        try {
            $user = $invite->handle(InviteUserData::validateAndCreate([
                'email' => mb_strtolower((string) $this->argument('email')),
                'first_name' => (string) $this->argument('first_name'),
                'last_name' => (string) $this->argument('last_name'),
                'phone' => null,
                'role' => Role::Admin->value,
                'locale' => Locale::Greek->value,
            ]));
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                $this->components->error(implode(' ', $messages));
            }

            return self::FAILURE;
        }

        $this->components->info("Administrator {$user->email} created; invitation sent.");

        return self::SUCCESS;
    }
}
