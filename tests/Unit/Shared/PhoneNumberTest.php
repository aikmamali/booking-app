<?php

declare(strict_types=1);

use App\Shared\PhoneNumber;

it('normalises valid numbers to E.164', function (string $input, string $expected): void {
    expect(PhoneNumber::parse($input)->e164)->toBe($expected);
})->with([
    'greek mobile' => ['6912345678', '+306912345678'],
    'greek mobile with spaces' => ['691 234 5678', '+306912345678'],
    'greek landline' => ['2101234567', '+302101234567'],
    'already e164' => ['+306912345678', '+306912345678'],
    'international prefix 00' => ['00306912345678', '+306912345678'],
    'foreign number' => ['+44 20 7946 0958', '+442079460958'],
    'dashes and brackets' => ['(+30) 691-234-5678', '+306912345678'],
]);

it('rejects invalid numbers', function (string $input): void {
    expect(PhoneNumber::isValid($input))->toBeFalse();
    PhoneNumber::parse($input);
})->with(['', '12345', '691234567', '0306912345678', 'abc', '+0123456789', '+3069123456789012'])
    ->throws(InvalidArgumentException::class);
