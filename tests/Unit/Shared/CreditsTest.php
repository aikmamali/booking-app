<?php

declare(strict_types=1);

use App\Shared\Credits;

it('parses decimal strings in steps of 0.5', function (string $input, int $halves): void {
    expect(Credits::of($input)->halves())->toBe($halves);
})->with([
    ['0', 0],
    ['1', 2],
    ['1.5', 3],
    ['1.50', 3],
    ['2.00', 4],
    ['-0.5', -1],
    [' 3.0 ', 6],
]);

it('accepts whole integers', function (): void {
    expect(Credits::of(3)->halves())->toBe(6)
        ->and(Credits::whole(-2)->halves())->toBe(-4);
});

it('rejects values that are not multiples of 0.5', function (string $input): void {
    Credits::of($input);
})->with(['1.25', '0.1', '1.55', 'abc', '', '1,5'])->throws(InvalidArgumentException::class);

it('formats as a decimal(6,2) string', function (int $halves, string $expected): void {
    expect(Credits::fromHalves($halves)->toDecimal())->toBe($expected);
})->with([
    [0, '0.00'],
    [1, '0.50'],
    [3, '1.50'],
    [4, '2.00'],
    [-1, '-0.50'],
    [-5, '-2.50'],
]);

it('does exact arithmetic', function (): void {
    $balance = Credits::of('10')
        ->subtract(Credits::of('1.5'))
        ->subtract(Credits::of('0.5'))
        ->add(Credits::of('1'));

    expect($balance->toDecimal())->toBe('9.00')
        ->and($balance->negate()->isNegative())->toBeTrue()
        ->and(Credits::zero()->isZero())->toBeTrue()
        ->and(Credits::of('0.5')->isPositive())->toBeTrue();
});

it('compares values', function (): void {
    expect(Credits::of('1.5')->equals(Credits::fromHalves(3)))->toBeTrue()
        ->and(Credits::of('2')->greaterThanOrEqual(Credits::of('2')))->toBeTrue()
        ->and(Credits::of('1.5')->greaterThanOrEqual(Credits::of('2')))->toBeFalse();
});

it('serialises to its decimal string', function (): void {
    expect(json_encode(['c' => Credits::of('1.5')]))->toBe('{"c":"1.50"}')
        ->and((string) Credits::of(2))->toBe('2.00');
});
