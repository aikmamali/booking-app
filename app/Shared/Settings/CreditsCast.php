<?php

declare(strict_types=1);

namespace App\Shared\Settings;

use App\Shared\Credits;
use InvalidArgumentException;
use Spatie\LaravelSettings\SettingsCasts\SettingsCast;

/**
 * Stores Credits in settings as a decimal string ("1.50").
 */
final class CreditsCast implements SettingsCast
{
    public function get(mixed $payload): Credits
    {
        if (! is_string($payload) && ! is_int($payload)) {
            throw new InvalidArgumentException('Credits setting must be a string or integer.');
        }

        return Credits::of($payload);
    }

    public function set(mixed $payload): string
    {
        if (! $payload instanceof Credits) {
            throw new InvalidArgumentException('Credits setting expects a Credits instance.');
        }

        return $payload->toDecimal();
    }
}
