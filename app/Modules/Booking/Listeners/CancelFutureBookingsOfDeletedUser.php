<?php

declare(strict_types=1);

namespace App\Modules\Booking\Listeners;

use App\Modules\Booking\Actions\CancelBookingsOfSession;
use App\Modules\Booking\Models\Booking;
use App\Modules\Identity\Events\UserDeleted;
use App\Shared\Clock;

/**
 * ID-04: deleting an account cancels its future bookings (with refund) and frees the seats.
 */
final readonly class CancelFutureBookingsOfDeletedUser
{
    public function __construct(
        private CancelBookingsOfSession $cancel,
        private Clock $clock,
    ) {}

    public function handle(UserDeleted $event): void
    {
        $ids = Booking::query()
            ->active()
            ->where('user_id', $event->userId)
            ->whereHas('session', fn ($q) => $q->where('starts_at', '>', $this->clock->now()))
            ->pluck('id')
            ->all();

        $this->cancel->cancelAll($ids, 'account_deleted', bySession: false);
    }
}
