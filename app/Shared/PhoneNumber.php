<?php

declare(strict_types=1);

namespace App\Shared;

use InvalidArgumentException;
use Stringable;

/**
 * A phone number in E.164 form (+306912345678). Greek national numbers (10 digits starting
 * with 2 or 69) are accepted without a country code and normalised to +30.
 */
final readonly class PhoneNumber implements Stringable
{
    private function __construct(public string $e164) {}

    public static function parse(string $input): self
    {
        $normalised = self::normalise($input);

        if ($normalised === null) {
            throw new InvalidArgumentException("Invalid phone number [{$input}].");
        }

        return new self($normalised);
    }

    public static function isValid(string $input): bool
    {
        return self::normalise($input) !== null;
    }

    public function __toString(): string
    {
        return $this->e164;
    }

    private static function normalise(string $input): ?string
    {
        $digits = preg_replace('/[\s\-().\/]/', '', $input) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = '+'.substr($digits, 2);
        }

        if (preg_match('/^(2\d{9}|69\d{8})$/', $digits) === 1) {
            $digits = '+30'.$digits;
        }

        return preg_match('/^\+[1-9]\d{7,14}$/', $digits) === 1 ? $digits : null;
    }
}
