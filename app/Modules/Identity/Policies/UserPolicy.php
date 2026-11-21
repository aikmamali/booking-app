<?php

declare(strict_types=1);

namespace App\Modules\Identity\Policies;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;

/**
 * Staff access to user records in the admin panel. Admins pass every check through the
 * Shield super-admin gate; other roles need the explicit permission. Staff accounts are
 * managed by admins only (spec section 2).
 */
final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('view_any_user');
    }

    public function view(User $actor): bool
    {
        return $actor->can('view_user');
    }

    public function create(User $actor): bool
    {
        return $actor->can('create_user');
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->can('update_user') && $this->mayManage($actor, $user);
    }

    public function delete(User $actor, User $user): bool
    {
        return $actor->can('delete_user') && $this->mayManage($actor, $user) && $actor->isNot($user);
    }

    private function mayManage(User $actor, User $user): bool
    {
        return ! ($user->primaryRole()?->isStaff() ?? false) || $actor->hasRole(Role::Admin->value);
    }
}
