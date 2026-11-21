<?php

declare(strict_types=1);

return [
    'greeting' => 'Γεια σου :name,',
    'details' => ':service · :date στις :time · :instructor · Κράτηση :reference',
    'view_booking' => 'Δες την κράτηση',
    'confirmed' => [
        'subject' => 'Επιβεβαίωση κράτησης: :service, :date :time',
        'body' => 'Η κράτησή σου επιβεβαιώθηκε. Θα βρεις συνημμένο αρχείο για το ημερολόγιό σου.',
    ],
    'cancelled' => [
        'subject' => 'Ακύρωση κράτησης: :service, :date :time',
        'body' => 'Η κράτησή σου ακυρώθηκε. Αν δικαιούσαι επιστροφή, το credit έχει επιστραφεί στη συνδρομή σου.',
    ],
    'reminder' => [
        'subject' => 'Υπενθύμιση: :service, :date :time',
        'body' => 'Σου θυμίζουμε το μάθημά σου.',
    ],
];
