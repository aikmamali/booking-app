<?php

declare(strict_types=1);

namespace App\Modules\Booking\Jobs;

use App\Modules\Booking\Actions\ExpireWaitlistOffers;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ExpireWaitlistOffersJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(ExpireWaitlistOffers $expire): void
    {
        $expire->handle();
    }
}
