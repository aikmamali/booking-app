<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * A user holds exactly one role. Admins cannot remove their own admin role, so the
 * panel can never be left without an administrator by accident.
 */
final class ChangeUserRole
{
    public function handle(User $actor, User $user, Role $role): void
    {
        if ($actor->is($user) && $user->hasRole(Role::Admin->value) && $role !== Role::Admin) {
            throw ValidationException::withMessages(['role' => __('identity.admin.cannot_demote_self')]);
        }

        $user->syncRoles([$role->value]);
    }
}
