<?php

declare(strict_types=1);

namespace App\Modules\Membership\Actions;

use App\Modules\Membership\Enums\TransactionType;
use App\Modules\Membership\Events\CreditsAdjusted;
use App\Modules\Membership\Ledger\DatabaseCreditLedger;
use App\Modules\Membership\Models\CreditTransaction;
use App\Modules\Membership\Models\Membership;
use App\Shared\Credits;
use Illuminate\Support\Facades\DB;

/**
 * MB-07: a manual correction is a new `adjustment` entry with a mandatory reason; the
 * balance is never edited. It cannot take the balance below zero.
 */
final readonly class AdjustCredits
{
    public function __construct(private DatabaseCreditLedger $ledger) {}

    public function handle(Membership $membership, Credits $amount, string $reason, ?int $actorId): CreditTransaction
    {
        return DB::transaction(function () use ($membership, $amount, $reason, $actorId): CreditTransaction {
            $entry = $this->ledger->record($membership->id, TransactionType::Adjustment, $amount, trim($reason), actorId: $actorId);

            event(new CreditsAdjusted($membership->id, $amount->toDecimal(), trim($reason)));

            return $entry;
        });
    }
}
