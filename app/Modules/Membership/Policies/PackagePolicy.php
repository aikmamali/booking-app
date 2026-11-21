<?php

declare(strict_types=1);

namespace App\Modules\Membership\Policies;

use App\Shared\Policies\PermissionPolicy;
use Illuminate\Contracts\Auth\Access\Authorizable;

final class PackagePolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'package';
    }

    /** MB-02: selling a package at the desk. */
    public function sell(Authorizable $actor): bool
    {
        return $actor->can('sell_package');
    }
}
