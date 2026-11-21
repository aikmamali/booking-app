<?php

declare(strict_types=1);

use App\Modules\Booking\Enums\BookingStatus as S;

it('allows only the transitions of spec 7.1', function (S $from, array $allowed): void {
    foreach (S::cases() as $to) {
        expect($from->canTransitionTo($to))->toBe(in_array($to, $allowed, true), "{$from->value} → {$to->value}");
    }
})->with([
    [S::Confirmed, [S::Cancelled, S::Attended, S::NoShow]],
    [S::PendingPayment, [S::Confirmed, S::Expired, S::Cancelled]],
    [S::PendingCredits, [S::Confirmed, S::Expired, S::Cancelled]],
    [S::Cancelled, []],
    [S::Attended, []],
    [S::NoShow, []],
    [S::Expired, []],
]);

it('knows which statuses hold a seat', function (): void {
    expect(array_filter(S::cases(), fn (S $s): bool => $s->isActive()))
        ->toBe([S::Confirmed, S::PendingPayment, S::PendingCredits]);
});
