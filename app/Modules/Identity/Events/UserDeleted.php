<?php

declare(strict_types=1);

namespace App\Modules\Identity\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A user deleted their account and was anonymised (ID-04). Carries only the id: the
 * personal data is already gone.
 */
final readonly class UserDeleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $userId) {}
}
