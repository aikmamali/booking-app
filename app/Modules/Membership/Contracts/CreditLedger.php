<?php

declare(strict_types=1);

namespace App\Modules\Membership\Contracts;

use App\Shared\Credits;

/**
 * Public contract of the Membership module for moving credits (spec 5.2). Booking uses it
 * to charge and refund; it never touches credit_transactions directly. Callers must run
 * inside a database transaction (an Action), because the ledger locks the membership row.
 */
interface CreditLedger
{
    public function balance(int $membershipId): Credits;

    /**
     * @return Credits the balance after the debit
     */
    public function debit(int $membershipId, Credits $amount, int $bookingId, ?int $actorId = null): Credits;

    /**
     * @return Credits the balance after the refund
     */
    public function refund(int $membershipId, Credits $amount, int $bookingId, ?int $actorId = null, ?string $reason = null): Credits;
}
