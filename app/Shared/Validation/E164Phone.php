<?php

declare(strict_types=1);

namespace App\Shared\Validation;

use App\Shared\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class E164Phone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! PhoneNumber::isValid($value)) {
            $fail('validation.phone')->translate();
        }
    }
}
