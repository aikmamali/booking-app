<?php

declare(strict_types=1);

namespace App\Modules\Booking\Listeners;

use App\Modules\Booking\Actions\RecordFixedSeatCancellation;
use App\Modules\Booking\Events\BookingCancelled;

final readonly class TrackFixedSeatCancellations
{
    public function __construct(private RecordFixedSeatCancellation $record) {}

    public function handle(BookingCancelled $event): void
    {
        $this->record->handle($event->bookingId, $event->bySession);
    }
}
