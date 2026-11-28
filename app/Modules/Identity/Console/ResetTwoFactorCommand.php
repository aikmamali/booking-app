<?php

declare(strict_types=1);

namespace App\Modules\Identity\Console;

use App\Modules\Identity\Actions\ResetTwoFactor;
use App\Modules\Identity\Models\User;
use Illuminate\Console\Command;

/**
 * Last resort when the only administrator lost both phone and recovery codes (ADR 0009).
 */
final class ResetTwoFactorCommand extends Command
{
    protected $signature = 'identity:reset-two-factor {email}';

    protected $description = 'Turn off two-step verification for a user; it is set up again at the next login';

    public function handle(ResetTwoFactor $reset): int
    {
        $user = User::query()->where('email', mb_strtolower((string) $this->argument('email')))->first();

        if (! $user instanceof User) {
            $this->components->error(__('identity.two_factor.console_unknown_user'));

            return self::FAILURE;
        }

        $reset->handle($user);
        $this->components->info(__('identity.two_factor.reset_done'));

        return self::SUCCESS;
    }
}
