<?php

declare(strict_types=1);

namespace App\Modules\Booking\Listeners;

use App\Modules\Booking\Actions\CancelBookingsOfSession;
use App\Modules\Scheduling\Events\SessionCancelled;

final readonly class CancelBookingsWhenSessionCancelled
{
    public function __construct(private CancelBookingsOfSession $cancel) {}

    public function handle(SessionCancelled $event): void
    {
        $this->cancel->handle($event->sessionId, $event->reason);
    }
}
