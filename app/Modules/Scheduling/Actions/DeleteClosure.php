<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Actions;

use App\Modules\Scheduling\Models\Closure;

/**
 * Removing a closure fills the freed days with sessions again. Sessions it already
 * cancelled stay cancelled: customers were told, so they are not silently revived.
 */
final readonly class DeleteClosure
{
    public function __construct(private GenerateSessions $generateSessions) {}

    public function handle(Closure $closure): void
    {
        $closure->delete();

        $this->generateSessions->handle();
    }
}
