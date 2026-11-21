<?php

declare(strict_types=1);

namespace App\Modules\Booking\Jobs;

use App\Modules\Booking\Actions\CloseFinishedSessions;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class CloseFinishedSessionsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(CloseFinishedSessions $action): void
    {
        $action->handle();
    }
}
