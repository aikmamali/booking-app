<?php

declare(strict_types=1);

namespace App\Modules\Billing\Exceptions;

use App\Shared\Exceptions\UserFacingException;
use RuntimeException;

final class PaymentRejected extends RuntimeException implements UserFacingException
{
    public static function because(string $key): self
    {
        return new self($key);
    }

    public function translated(): string
    {
        return __($this->getMessage());
    }
}
