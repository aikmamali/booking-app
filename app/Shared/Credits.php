<?php

declare(strict_types=1);

namespace App\Shared;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * A number of credits in steps of 0.5. Stored internally as an integer count of halves,
 * so arithmetic is exact. Persisted as decimal(6,2) strings such as "1.50" (spec section 6).
 * May be negative: ledger entries carry signed amounts.
 */
final readonly class Credits implements JsonSerializable, Stringable
{
    private function __construct(private int $halves) {}

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromHalves(int $halves): self
    {
        return new self($halves);
    }

    public static function whole(int $credits): self
    {
        return new self($credits * 2);
    }

    /**
     * Parses "1", "1.5", "1.50", "-0.5". Anything that is not a multiple of 0.5 is rejected.
     */
    public static function of(string|int $value): self
    {
        if (is_int($value)) {
            return self::whole($value);
        }

        if (preg_match('/^(-)?(\d+)(?:\.(\d+))?$/', trim($value), $m) !== 1) {
            throw new InvalidArgumentException("Invalid credits value [{$value}].");
        }

        $fraction = rtrim($m[3] ?? '', '0');

        if ($fraction !== '' && $fraction !== '5') {
            throw new InvalidArgumentException("Credits must be in steps of 0.5, got [{$value}].");
        }

        $halves = ((int) $m[2]) * 2 + ($fraction === '5' ? 1 : 0);

        return new self($m[1] === '-' ? -$halves : $halves);
    }

    public function halves(): int
    {
        return $this->halves;
    }

    public function add(self $other): self
    {
        return new self($this->halves + $other->halves);
    }

    public function subtract(self $other): self
    {
        return new self($this->halves - $other->halves);
    }

    public function negate(): self
    {
        return new self(-$this->halves);
    }

    public function isZero(): bool
    {
        return $this->halves === 0;
    }

    public function isNegative(): bool
    {
        return $this->halves < 0;
    }

    public function isPositive(): bool
    {
        return $this->halves > 0;
    }

    public function equals(self $other): bool
    {
        return $this->halves === $other->halves;
    }

    public function greaterThanOrEqual(self $other): bool
    {
        return $this->halves >= $other->halves;
    }

    /**
     * Decimal string matching the decimal(6,2) column, e.g. "1.50", "-0.50", "2.00".
     */
    public function toDecimal(): string
    {
        $abs = abs($this->halves);

        return ($this->halves < 0 ? '-' : '').intdiv($abs, 2).($abs % 2 === 1 ? '.50' : '.00');
    }

    public function __toString(): string
    {
        return $this->toDecimal();
    }

    public function jsonSerialize(): string
    {
        return $this->toDecimal();
    }
}
