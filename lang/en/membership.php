<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Memberships',
    'package' => ['label' => 'Package', 'plural' => 'Packages'],
    'membership' => ['label' => 'Membership', 'plural' => 'Memberships'],
    'status' => [
        'pending' => 'Awaiting payment',
        'active' => 'Active',
        'frozen' => 'Paused',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
    ],
    'transaction_types' => [
        'purchase' => 'Purchase',
        'debit' => 'Booking',
        'refund' => 'Refund',
        'expire' => 'Expiry',
        'adjustment' => 'Correction',
    ],
    'fields' => [
        'name' => 'Name',
        'credits' => 'Credits',
        'validity_days' => 'Validity',
        'price' => 'Price',
        'vat_rate' => 'VAT',
        'services' => 'Services covered',
        'is_purchasable_online' => 'Sold online',
        'is_active' => 'Active',
        'customer' => 'Customer',
        'starts_on' => 'Start',
        'ends_on' => 'End',
        'period' => 'Period',
        'balance' => 'Balance',
        'status' => 'Status',
        'payment_method' => 'Payment method',
        'adjustment' => 'Credits (+ or −)',
        'reason' => 'Reason',
    ],
    'units' => ['days' => 'days'],
    'actions' => [
        'sell' => 'Sell package',
        'adjust' => 'Correct credits',
    ],
    'hints' => [
        'adjustment' => 'Positive to add, negative to remove. In steps of 0.5.',
    ],
    'notices' => [
        'sold' => 'The membership was created.',
        'adjusted' => 'The correction was recorded.',
    ],
    'ledger' => [
        'title' => 'Credit history',
        'when' => 'When',
        'type' => 'Movement',
        'amount' => 'Credits',
        'balance_after' => 'Balance',
        'actor' => 'By',
    ],
    'mine' => [
        'nav' => 'Membership',
        'title' => 'My membership',
        'empty' => 'You have no membership yet. Ask at the front desk about our packages.',
    ],
    'errors' => [
        'insufficient_credits' => 'Not enough credits.',
        'zero_amount' => 'The movement needs an amount.',
        'reason_required' => 'A reason is required.',
        'not_a_customer' => 'This user is not an active customer.',
        'package_inactive' => 'The package is not active.',
        'package_incomplete' => 'The package needs credits and at least one service.',
        'starts_in_past' => 'The start date cannot be in the past.',
    ],
];
