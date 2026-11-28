<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

/**
 * BL-02: expense categories of a small studio.
 */
enum ExpenseCategory: string
{
    case Rent = 'rent';
    case Utilities = 'utilities';
    case Payroll = 'payroll';
    case Equipment = 'equipment';
    case Supplies = 'supplies';
    case Maintenance = 'maintenance';
    case Marketing = 'marketing';
    case Taxes = 'taxes';
    case Other = 'other';

    public function label(): string
    {
        return __('billing.expense_categories.'.$this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $category) {
            $options[$category->value] = $category->label();
        }

        return $options;
    }
}
