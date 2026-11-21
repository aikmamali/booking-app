<?php

declare(strict_types=1);

namespace App\Modules\Billing\Listeners;

use App\Modules\Billing\Actions\RecordPayment;
use App\Modules\Billing\Data\PaymentData;
use App\Modules\Membership\Events\MembershipSold;

/**
 * MB-02 / BL-01: selling a package at the desk records its payment. Free packages
 * (price 0) record nothing.
 */
final readonly class RecordPackageSale
{
    public const string MEMBERSHIP = 'membership';

    public function __construct(private RecordPayment $record) {}

    public function handle(MembershipSold $event): void
    {
        if ($event->priceCents === 0) {
            return;
        }

        $this->record->handle(new PaymentData(
            user_id: $event->userId,
            amount_cents: $event->priceCents,
            method: $event->method,
            payable_type: self::MEMBERSHIP,
            payable_id: $event->membershipId,
            recorded_by: $event->actorId,
        ));
    }
}
