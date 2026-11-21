<?php

declare(strict_types=1);

namespace App\Shared;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * An amount of money in integer cents. Floats are never used for money.
 * Single currency (EUR) for now; the currency is kept explicit so a second one cannot be mixed in silently.
 */
final readonly class Money implements JsonSerializable, Stringable
{
    public const string DEFAULT_CURRENCY = 'EUR';

    private function __construct(
        public int $cents,
        public string $currency,
    ) {}

    public static function ofCents(int $cents, string $currency = self::DEFAULT_CURRENCY): self
    {
        return new self($cents, $currency);
    }

    public static function zero(string $currency = self::DEFAULT_CURRENCY): self
    {
        return new self(0, $currency);
    }

    /**
     * Parses a decimal string such as "12.50" or "-3" without going through float.
     */
    public static function fromDecimal(string $amount, string $currency = self::DEFAULT_CURRENCY): self
    {
        if (preg_match('/^(-)?(\d+)(?:\.(\d{1,2}))?$/', trim($amount), $m) !== 1) {
            throw new InvalidArgumentException("Invalid money amount [{$amount}].");
        }

        $cents = ((int) $m[2]) * 100 + (int) str_pad($m[3] ?? '', 2, '0');

        return new self($m[1] === '-' ? -$cents : $cents, $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->cents + $other->cents, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->cents - $other->cents, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->cents * $factor, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency && $this->cents === $other->cents;
    }

    public function greaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->cents > $other->cents;
    }

    /**
     * Plain decimal representation, e.g. "12.50". Locale formatting belongs to the UI.
     */
    public function toDecimal(): string
    {
        $abs = abs($this->cents);

        return ($this->cents < 0 ? '-' : '').intdiv($abs, 100).'.'.str_pad((string) ($abs % 100), 2, '0', STR_PAD_LEFT);
    }

    public function __toString(): string
    {
        return $this->toDecimal().' '.$this->currency;
    }

    /**
     * @return array{cents: int, currency: string}
     */
    public function jsonSerialize(): array
    {
        return ['cents' => $this->cents, 'currency' => $this->currency];
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Currency mismatch: {$this->currency} vs {$other->currency}.");
        }
    }
}
