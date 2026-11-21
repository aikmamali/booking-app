<?php

declare(strict_types=1);

namespace App\Modules\Booking\Jobs;

use App\Modules\Booking\Actions\PromoteFromWaitlist;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class PromoteFromWaitlistJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $sessionId) {}

    public function handle(PromoteFromWaitlist $promote): void
    {
        $promote->handle($this->sessionId);
    }
}
