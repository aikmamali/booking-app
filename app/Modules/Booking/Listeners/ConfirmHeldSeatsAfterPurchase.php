<?php

declare(strict_types=1);

namespace App\Modules\Booking\Listeners;

use App\Modules\Booking\Actions\ConfirmPendingCreditBookings;
use App\Modules\Membership\Events\MembershipSold;

final readonly class ConfirmHeldSeatsAfterPurchase
{
    public function __construct(private ConfirmPendingCreditBookings $confirm) {}

    public function handle(MembershipSold $event): void
    {
        $this->confirm->handle($event->userId);
    }
}
