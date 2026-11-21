<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Finance',
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
    ],
    'payables' => [
        'membership' => 'Package',
        'booking' => 'Single class',
        'other' => 'Other',
    ],
    'actions' => [
        'record' => 'Record payment',
    ],
    'notices' => [
        'recorded' => 'The payment was recorded.',
    ],
    'errors' => [
        'amount' => 'The amount must be positive.',
    ],
    'methods' => [
        'cash' => 'Cash',
        'pos' => 'POS',
        'card' => 'Card online',
        'transfer' => 'Bank transfer',
    ],
];
