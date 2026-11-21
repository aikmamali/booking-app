<?php

declare(strict_types=1);

namespace App\Modules\Membership\Enums;

/**
 * Kinds of ledger movement (spec 7.5) and the sign each one must carry.
 */
enum TransactionType: string
{
    case Purchase = 'purchase';
    case Debit = 'debit';
    case Refund = 'refund';
    case Expire = 'expire';
    case Adjustment = 'adjustment';

    /**
     * +1 for credits in, -1 for credits out, 0 when either sign is allowed.
     */
    public function sign(): int
    {
        return match ($this) {
            self::Purchase, self::Refund => 1,
            self::Debit, self::Expire => -1,
            self::Adjustment => 0,
        };
    }

    public function label(): string
    {
        return __('membership.transaction_types.'.$this->value);
    }
}
