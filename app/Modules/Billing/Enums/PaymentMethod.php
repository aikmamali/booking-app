<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

/**
 * How a payment was collected (BL-01). Public contract of the Billing module.
 */
enum PaymentMethod: string
{
    case Cash = 'cash';
    case Pos = 'pos';
    case Card = 'card';
    case Transfer = 'transfer';

    public function label(): string
    {
        return __('billing.methods.'.$this->value);
    }

    /**
     * Methods staff can record at the desk (online card payments come from Stripe).
     *
     * @return array<string, string>
     */
    public static function deskOptions(): array
    {
        $options = [];

        foreach ([self::Cash, self::Pos, self::Transfer] as $method) {
            $options[$method->value] = $method->label();
        }

        return $options;
    }
}
