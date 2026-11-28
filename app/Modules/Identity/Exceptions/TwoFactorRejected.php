<?php

declare(strict_types=1);

namespace App\Modules\Identity\Exceptions;

use App\Shared\Exceptions\UserFacingException;
use RuntimeException;

final class TwoFactorRejected extends RuntimeException implements UserFacingException
{
    /**
     * @param  array<string, int|string>  $replace
     */
    private function __construct(string $key, private readonly array $replace = [])
    {
        parent::__construct($key);
    }

    /**
     * @param  array<string, int|string>  $replace
     */
    public static function because(string $key, array $replace = []): self
    {
        return new self($key, $replace);
    }

    public function translated(): string
    {
        return __($this->getMessage(), $this->replace);
    }
}
