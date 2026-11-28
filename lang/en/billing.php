<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Finance',
    'expense' => ['label' => 'Expense', 'plural' => 'Expenses'],
    'expense_categories' => [
        'rent' => 'Rent',
        'utilities' => 'Utilities (power, water, phone)',
        'payroll' => 'Payroll',
        'equipment' => 'Equipment',
        'supplies' => 'Supplies',
        'maintenance' => 'Maintenance',
        'marketing' => 'Marketing',
        'taxes' => 'Taxes and contributions',
        'other' => 'Other',
    ],
    'payment' => ['label' => 'Payment', 'plural' => 'Payments'],
    'status' => [
        'pending' => 'Pending',
        'succeeded' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ],
    'fields' => [
        'paid_at' => 'Date',
        'customer' => 'Customer',
        'amount' => 'Amount',
        'total' => 'Total',
        'method' => 'Method',
        'for' => 'For',
        'status' => 'Status',
        'recorded_by' => 'Recorded by',
        'note' => 'Note',
        'period' => 'Period',
        'from' => 'From',
        'until' => 'Until',
        'category' => 'Category',
        'spent_on' => 'Date spent',
        'attachment' => 'Attachment (receipt, invoice)',
    ],
    'payables' => [
        'membership' => 'Package',
        'booking' => 'Single class',
        'other' => 'Other',
    ],
    'actions' => [
        'record' => 'Record payment',
        'record_expense' => 'Record expense',
        'download_attachment' => 'Attachment',
    ],
    'notices' => [
        'recorded' => 'The payment was recorded.',
    ],
    'errors' => [
        'amount' => 'The amount must be positive.',
        'expense_in_future' => 'The expense date cannot be in the future.',
    ],
    'methods' => [
        'cash' => 'Cash',
        'pos' => 'POS',
        'card' => 'Card online',
        'transfer' => 'Bank transfer',
    ],
];
