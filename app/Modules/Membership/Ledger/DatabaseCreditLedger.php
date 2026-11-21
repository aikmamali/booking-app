<?php

declare(strict_types=1);

namespace App\Modules\Membership\Ledger;

use App\Modules\Membership\Contracts\CreditLedger;
use App\Modules\Membership\Enums\TransactionType;
use App\Modules\Membership\Exceptions\LedgerRejected;
use App\Modules\Membership\Models\CreditTransaction;
use App\Modules\Membership\Models\Membership;
use App\Shared\Credits;
use LogicException;

/**
 * Append-only ledger over credit_transactions (spec 7.5). Every entry locks the membership
 * row, reads the latest balance_after and writes a new row; balances are never updated.
 * Invariant: the latest balance_after equals SUM(amount) and is never negative.
 */
final class DatabaseCreditLedger implements CreditLedger
{
    public function balance(int $membershipId): Credits
    {
        $latest = CreditTransaction::query()
            ->where('membership_id', $membershipId)
            ->orderByDesc('id')
            ->value('balance_after');

        return $latest === null ? Credits::zero() : Credits::of((string) $latest);
    }

    public function debit(int $membershipId, Credits $amount, int $bookingId, ?int $actorId = null): Credits
    {
        return $this->record($membershipId, TransactionType::Debit, $amount->negate(), bookingId: $bookingId, actorId: $actorId)->balance_after;
    }

    public function refund(int $membershipId, Credits $amount, int $bookingId, ?int $actorId = null, ?string $reason = null): Credits
    {
        return $this->record($membershipId, TransactionType::Refund, $amount, $reason, $bookingId, $actorId)->balance_after;
    }

    /**
     * Writes one entry. $amount carries its sign (negative for debit and expire).
     */
    public function record(
        int $membershipId,
        TransactionType $type,
        Credits $amount,
        ?string $reason = null,
        ?int $bookingId = null,
        ?int $actorId = null,
    ): CreditTransaction {
        $this->guardAmount($type, $amount, $reason);
        $this->lockMembership($membershipId);

        $balance = $this->balance($membershipId)->add($amount);

        if ($balance->isNegative()) {
            throw LedgerRejected::because('membership.errors.insufficient_credits');
        }

        return CreditTransaction::query()->create([
            'membership_id' => $membershipId,
            'booking_id' => $bookingId,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balance,
            'reason' => $reason,
            'actor_id' => $actorId,
        ]);
    }

    private function guardAmount(TransactionType $type, Credits $amount, ?string $reason): void
    {
        if ($amount->isZero()) {
            throw LedgerRejected::because('membership.errors.zero_amount');
        }

        $sign = $amount->isNegative() ? -1 : 1;

        if ($type->sign() !== 0 && $type->sign() !== $sign) {
            throw new LogicException("A {$type->value} entry cannot have that sign.");
        }

        if ($type === TransactionType::Adjustment && trim((string) $reason) === '') {
            throw LedgerRejected::because('membership.errors.reason_required');
        }
    }

    private function lockMembership(int $membershipId): void
    {
        $query = Membership::query()->whereKey($membershipId);

        if ($query->getConnection()->transactionLevel() === 0) {
            throw new LogicException('Ledger entries must be written inside a database transaction.');
        }

        $query->lockForUpdate()->firstOrFail();
    }
}
