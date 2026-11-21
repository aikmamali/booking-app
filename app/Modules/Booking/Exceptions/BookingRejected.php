<?php

declare(strict_types=1);

namespace App\Modules\Booking\Exceptions;

use App\Shared\Exceptions\UserFacingException;
use RuntimeException;

/**
 * BookingRejected::because('booking.full'): the UI shows the translated message.
 */
final class BookingRejected extends RuntimeException implements UserFacingException
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
