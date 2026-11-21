<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Jobs;

use App\Modules\Scheduling\Actions\GenerateSessions;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Nightly top-up of the calendar (spec section 8).
 */
final class GenerateSessionsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(GenerateSessions $generate): void
    {
        $generate->handle();
    }
}
