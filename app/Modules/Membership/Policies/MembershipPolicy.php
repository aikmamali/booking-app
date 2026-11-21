<?php

declare(strict_types=1);

namespace App\Modules\Membership\Policies;

use App\Shared\Policies\PermissionPolicy;
use Illuminate\Contracts\Auth\Access\Authorizable;

final class MembershipPolicy extends PermissionPolicy
{
    protected function subject(): string
    {
        return 'membership';
    }

    /** MB-07: manual credit corrections are an admin task (spec section 2). */
    public function adjustCredits(Authorizable $actor): bool
    {
        return $actor->can('adjust_credits');
    }
}
