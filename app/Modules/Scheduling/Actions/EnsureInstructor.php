<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Actions;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Scheduling\Exceptions\SessionChangeRejected;

/**
 * Sessions and schedules may only name a user with the instructor role.
 */
final class EnsureInstructor
{
    public function handle(int $userId): void
    {
        $user = User::query()->find($userId);

        if ($user === null || ! $user->hasRole(Role::Instructor->value)) {
            throw SessionChangeRejected::because('scheduling.errors.not_instructor');
        }
    }
}
