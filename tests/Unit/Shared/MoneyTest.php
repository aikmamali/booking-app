<?php

declare(strict_types=1);

use App\Shared\Money;

it('parses decimal strings without floats', function (string $input, int $cents): void {
    expect(Money::fromDecimal($input)->cents)->toBe($cents);
})->with([
    ['0', 0],
    ['12', 1200],
    ['12.5', 1250],
    ['12.50', 1250],
    ['0.07', 7],
    ['-3.20', -320],
]);

it('rejects malformed amounts', function (string $input): void {
    Money::fromDecimal($input);
})->with(['1.234', 'abc', '', '1,50'])->throws(InvalidArgumentException::class);

it('formats as a plain decimal', function (int $cents, string $expected): void {
    expect(Money::ofCents($cents)->toDecimal())->toBe($expected);
})->with([
    [0, '0.00'],
    [7, '0.07'],
    [1250, '12.50'],
    [-320, '-3.20'],
]);

it('adds, subtracts and multiplies', function (): void {
    $total = Money::ofCents(4500)->multiply(2)->add(Money::ofCents(150))->subtract(Money::ofCents(50));

    expect($total->cents)->toBe(9100)
        ->and($total->currency)->toBe('EUR')
        ->and(Money::zero()->isZero())->toBeTrue()
        ->and(Money::ofCents(-1)->isNegative())->toBeTrue()
        ->and(Money::ofCents(200)->greaterThan(Money::ofCents(199)))->toBeTrue()
        ->and(Money::ofCents(200)->equals(Money::fromDecimal('2')))->toBeTrue();
});

it('refuses to mix currencies', function (): void {
    Money::ofCents(100)->add(Money::ofCents(100, 'USD'));
})->throws(InvalidArgumentException::class);

it('serialises cents and currency', function (): void {
    expect(json_encode(Money::ofCents(1250)))->toBe('{"cents":1250,"currency":"EUR"}')
        ->and((string) Money::ofCents(1250))->toBe('12.50 EUR');
});
