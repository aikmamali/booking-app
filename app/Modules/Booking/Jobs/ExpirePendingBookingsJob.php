<?php

declare(strict_types=1);

namespace App\Modules\Booking\Jobs;

use App\Modules\Booking\Actions\ExpirePendingBookings;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ExpirePendingBookingsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(ExpirePendingBookings $action): void
    {
        $action->handle();
    }
}
