<?php

declare(strict_types=1);

return [
    'navigation_group' => 'Συνδρομές',
    'package' => ['label' => 'Πακέτο', 'plural' => 'Πακέτα'],
    'membership' => ['label' => 'Συνδρομή', 'plural' => 'Συνδρομές'],
    'status' => [
        'pending' => 'Σε αναμονή πληρωμής',
        'active' => 'Ενεργή',
        'frozen' => 'Σε παύση',
        'expired' => 'Έληξε',
        'cancelled' => 'Ακυρώθηκε',
    ],
    'transaction_types' => [
        'purchase' => 'Αγορά',
        'debit' => 'Κράτηση',
        'refund' => 'Επιστροφή',
        'expire' => 'Λήξη',
        'adjustment' => 'Διόρθωση',
    ],
    'fields' => [
        'name' => 'Όνομα',
        'credits' => 'Credits',
        'validity_days' => 'Ισχύς',
        'price' => 'Τιμή',
        'vat_rate' => 'ΦΠΑ',
        'services' => 'Υπηρεσίες που καλύπτει',
        'is_purchasable_online' => 'Αγορά online',
        'is_active' => 'Ενεργό',
        'customer' => 'Πελάτης',
        'starts_on' => 'Έναρξη',
        'ends_on' => 'Λήξη',
        'period' => 'Περίοδος',
        'balance' => 'Υπόλοιπο',
        'status' => 'Κατάσταση',
        'payment_method' => 'Τρόπος πληρωμής',
        'adjustment' => 'Credits (+ ή −)',
        'reason' => 'Αιτιολογία',
    ],
    'units' => ['days' => 'ημέρες'],
    'actions' => [
        'sell' => 'Πώληση πακέτου',
        'adjust' => 'Διόρθωση credits',
    ],
    'hints' => [
        'adjustment' => 'Θετικό για προσθήκη, αρνητικό για αφαίρεση. Σε βήματα του 0,5.',
    ],
    'notices' => [
        'sold' => 'Η συνδρομή δημιουργήθηκε.',
        'adjusted' => 'Η διόρθωση καταχωρήθηκε.',
    ],
    'ledger' => [
        'title' => 'Ιστορικό κινήσεων',
        'when' => 'Πότε',
        'type' => 'Κίνηση',
        'amount' => 'Credits',
        'balance_after' => 'Υπόλοιπο',
        'actor' => 'Από',
    ],
    'mine' => [
        'nav' => 'Συνδρομή',
        'title' => 'Η συνδρομή μου',
        'empty' => 'Δεν έχεις ακόμα συνδρομή. Ρώτησε στην υποδοχή για τα πακέτα μας.',
    ],
    'errors' => [
        'insufficient_credits' => 'Δεν υπάρχουν αρκετά credits.',
        'zero_amount' => 'Η κίνηση πρέπει να έχει ποσό.',
        'reason_required' => 'Η αιτιολογία είναι υποχρεωτική.',
        'not_a_customer' => 'Ο χρήστης δεν είναι ενεργός πελάτης.',
        'package_inactive' => 'Το πακέτο δεν είναι ενεργό.',
        'package_incomplete' => 'Το πακέτο χρειάζεται credits και τουλάχιστον μία υπηρεσία.',
        'starts_in_past' => 'Η έναρξη δεν μπορεί να είναι στο παρελθόν.',
    ],
];
