<?php

declare(strict_types=1);

namespace App\Modules\Membership\Exceptions;

use App\Shared\Exceptions\UserFacingException;
use RuntimeException;

/**
 * A ledger movement or membership change broke a rule. The message is a translation key.
 */
final class LedgerRejected extends RuntimeException implements UserFacingException
{
    /**
     * @param  array<string, scalar>  $replace
     */
    public function __construct(public readonly string $key, public readonly array $replace = [])
    {
        parent::__construct($key);
    }

    /**
     * @param  array<string, scalar>  $replace
     */
    public static function because(string $key, array $replace = []): self
    {
        return new self($key, $replace);
    }

    public function translated(): string
    {
        return __($this->key, $this->replace);
    }
}
