<?php

declare(strict_types=1);

namespace App\Shared\Casts;

use App\Shared\Credits;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Maps a decimal(6,2) column to Credits and back, never through float.
 *
 * @implements CastsAttributes<Credits|null, Credits|string|int|null>
 */
final class CreditsCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Credits
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value) && ! is_int($value)) {
            throw new InvalidArgumentException("Credits column [{$key}] must be read as a string.");
        }

        return Credits::of($value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Credits) {
            return $value->toDecimal();
        }

        return Credits::of($value)->toDecimal();
    }
}
