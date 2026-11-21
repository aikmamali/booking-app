<?php

declare(strict_types=1);

namespace App\Shared\Policies;

use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * Resource policy driven by Filament Shield permission names
 * (view_any_service, create_service, ...). Admins pass through the super-admin gate.
 */
abstract class PermissionPolicy
{
    abstract protected function subject(): string;

    public function viewAny(Authorizable $actor): bool
    {
        return $this->allows($actor, 'view_any');
    }

    public function view(Authorizable $actor): bool
    {
        return $this->allows($actor, 'view');
    }

    public function create(Authorizable $actor): bool
    {
        return $this->allows($actor, 'create');
    }

    public function update(Authorizable $actor): bool
    {
        return $this->allows($actor, 'update');
    }

    public function delete(Authorizable $actor): bool
    {
        return $this->allows($actor, 'delete');
    }

    public function reorder(Authorizable $actor): bool
    {
        return $this->allows($actor, 'reorder');
    }

    private function allows(Authorizable $actor, string $ability): bool
    {
        return $actor->can($ability.'_'.$this->subject());
    }
}
