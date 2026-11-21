<?php

declare(strict_types=1);

use App\Modules\Membership\Enums\MembershipStatus as S;
use App\Modules\Membership\Enums\TransactionType as T;

it('allows only the documented membership transitions', function (S $from, array $allowed): void {
    foreach (S::cases() as $to) {
        expect($from->canTransitionTo($to))->toBe(in_array($to, $allowed, true), "{$from->value} → {$to->value}");
    }
})->with([
    [S::Pending, [S::Active, S::Cancelled]],
    [S::Active, [S::Frozen, S::Expired, S::Cancelled]],
    [S::Frozen, [S::Active, S::Expired, S::Cancelled]],
    [S::Expired, []],
    [S::Cancelled, []],
]);

it('gives each ledger movement its sign', function (T $type, int $sign): void {
    expect($type->sign())->toBe($sign);
})->with([
    [T::Purchase, 1], [T::Refund, 1], [T::Debit, -1], [T::Expire, -1], [T::Adjustment, 0],
]);
