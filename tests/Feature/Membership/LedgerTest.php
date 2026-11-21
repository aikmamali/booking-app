<?php

declare(strict_types=1);

use App\Modules\Booking\Models\Booking;
use App\Modules\Membership\Contracts\CreditLedger;
use App\Modules\Membership\Enums\TransactionType;
use App\Modules\Membership\Exceptions\LedgerRejected;
use App\Modules\Membership\Ledger\DatabaseCreditLedger;
use App\Modules\Membership\Models\CreditTransaction;
use App\Shared\Credits;
use Illuminate\Support\Facades\DB;

function ledger(): DatabaseCreditLedger
{
    return app(DatabaseCreditLedger::class);
}

it('starts with the purchased credits', function (): void {
    $membership = soldMembership(package: ['credits' => Credits::of('8.5')]);

    $entry = CreditTransaction::query()->sole();
    expect($entry->type)->toBe(TransactionType::Purchase)
        ->and($entry->amount->toDecimal())->toBe('8.50')
        ->and($entry->balance_after->toDecimal())->toBe('8.50')
        ->and(app(CreditLedger::class)->balance($membership->id)->toDecimal())->toBe('8.50');
});

it('debits and refunds through the public contract', function (): void {
    $membership = soldMembership();
    $contract = app(CreditLedger::class);
    $bookingId = Booking::factory()->create()->id;

    DB::transaction(function () use ($contract, $membership, $bookingId): void {
        expect($contract->debit($membership->id, Credits::of('1.5'), bookingId: $bookingId)->toDecimal())->toBe('8.50')
            ->and($contract->refund($membership->id, Credits::of('1.5'), bookingId: $bookingId)->toDecimal())->toBe('10.00');
    });

    $entries = CreditTransaction::query()->orderBy('id')->get();
    expect($entries->pluck('type')->all())->toBe([TransactionType::Purchase, TransactionType::Debit, TransactionType::Refund])
        ->and($entries[1]->amount->toDecimal())->toBe('-1.50')
        ->and($entries[1]->booking_id)->toBe($bookingId);
});

it('never lets the balance go below zero', function (): void {
    $membership = soldMembership(package: ['credits' => Credits::of(1)]);

    $bookingId = Booking::factory()->create()->id;

    DB::transaction(fn (): Credits => ledger()->debit($membership->id, Credits::of('1.5'), bookingId: $bookingId));
})->throws(LedgerRejected::class, 'membership.errors.insufficient_credits');

it('rejects entries with the wrong sign or no amount', function (TransactionType $type, string $amount, string $error): void {
    $membership = soldMembership();

    expect(fn () => DB::transaction(fn (): CreditTransaction => ledger()->record($membership->id, $type, Credits::of($amount), 'x')))
        ->toThrow($error);
})->with([
    [TransactionType::Debit, '1', LogicException::class],
    [TransactionType::Refund, '-1', LogicException::class],
    [TransactionType::Purchase, '-1', LogicException::class],
    [TransactionType::Expire, '1', LogicException::class],
    [TransactionType::Adjustment, '0', LedgerRejected::class],
]);

it('requires a reason for adjustments', function (): void {
    $membership = soldMembership();

    DB::transaction(fn (): CreditTransaction => ledger()->record($membership->id, TransactionType::Adjustment, Credits::of(1), '  '));
})->throws(LedgerRejected::class, 'membership.errors.reason_required');

it('refuses to write outside a database transaction', function (): void {
    // Step out of the test's RefreshDatabase transaction; rolling back leaves nothing behind.
    DB::rollBack();

    try {
        ledger()->record(1, TransactionType::Refund, Credits::of(1));
    } finally {
        DB::beginTransaction();
    }
})->throws(LogicException::class, 'inside a database transaction');

it('is append-only', function (string $operation): void {
    soldMembership();
    $entry = CreditTransaction::query()->sole();

    $operation === 'update' ? $entry->update(['reason' => 'tamper']) : $entry->delete();
})->with(['update', 'delete'])->throws(LogicException::class, 'append-only');

/*
| Ledger invariant (spec 7.5): for random sequences of movements, the latest balance_after
| equals SUM(amount) and no balance_after is ever negative.
*/
it('keeps the ledger invariant under random movements', function (int $seed): void {
    mt_srand($seed);
    $membership = soldMembership(package: ['credits' => Credits::fromHalves(mt_rand(1, 20))]);
    $types = [TransactionType::Debit, TransactionType::Refund, TransactionType::Adjustment, TransactionType::Expire];

    foreach (range(1, 60) as $step) {
        $type = $types[mt_rand(0, 3)];
        $halves = mt_rand(1, 6) * ($type->sign() === 0 ? (mt_rand(0, 1) === 1 ? 1 : -1) : $type->sign());

        try {
            DB::transaction(fn (): CreditTransaction => ledger()->record($membership->id, $type, Credits::fromHalves($halves), "step {$step}"));
        } catch (LedgerRejected) {
            // Rejected movements (insufficient credits) must leave no trace.
        }
    }

    $entries = CreditTransaction::query()->where('membership_id', $membership->id)->orderBy('id')->get();
    $sum = $entries->reduce(fn (Credits $carry, CreditTransaction $e): Credits => $carry->add($e->amount), Credits::zero());
    $running = Credits::zero();

    foreach ($entries as $entry) {
        $running = $running->add($entry->amount);
        expect($entry->balance_after->equals($running))->toBeTrue()
            ->and($entry->balance_after->isNegative())->toBeFalse();
    }

    expect($entries->last()?->balance_after->equals($sum))->toBeTrue()
        ->and(ledger()->balance($membership->id)->equals($sum))->toBeTrue();
})->with(range(1, 10));
