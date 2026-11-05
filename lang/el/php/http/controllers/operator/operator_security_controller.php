<?php

// I18N-01 — el translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Εισαγάγετε έγκυρη διεύθυνση IP ή μπλοκ CIDR (π.χ. 203.0.113.4 ή 203.0.113.0/24).',
    'ip_already_listed' => 'Η διεύθυνση :ip βρίσκεται ήδη στη λίστα επιτρεπόμενων.',
    'allowlist_enabled_auto_added' => 'Η λίστα επιτρεπόμενων IP ενεργοποιήθηκε. Προσθέσαμε επίσης την τρέχουσα διεύθυνσή σας (:ip), ώστε να μην κλειδωθείτε έξω.',
    'ip_added' => 'Η διεύθυνση :ip προστέθηκε στη λίστα επιτρεπόμενων.',
    'remove_would_lock_out' => 'Η αφαίρεση της διεύθυνσης :ip θα σας κλείδωνε έξω — είναι ο μόνος κανόνας που επιτρέπει στη διεύθυνσή σας (:current) να έχει πρόσβαση στο back office. Προσθέστε πρώτα έναν κανόνα για τη διεύθυνσή σας ή αφαιρέστε τους υπόλοιπους.',
    'ip_removed_allowlist_off' => 'Η διεύθυνση :ip αφαιρέθηκε — η λίστα επιτρεπόμενων είναι απενεργοποιημένη: οποιοσδήποτε με έγκυρη σύνδεση έχει πρόσβαση στο back office.',
    'ip_removed' => 'Η διεύθυνση :ip αφαιρέθηκε από τη λίστα επιτρεπόμενων.',
    'session_already_ended' => 'Αυτή η συνεδρία έχει ήδη λήξει.',
    'signed_out_of_device' => 'Έγινε αποσύνδεση από τη συσκευή: :device.',
    'trusted_device_revoked' => 'Η αξιόπιστη συσκευή ανακλήθηκε.',
    'csv_title' => 'Εξαγωγή λογαριασμού Vyrobel',
    'csv_name' => 'Όνομα',
    'csv_email' => 'Διεύθυνση email',
    'csv_locale' => 'Γλώσσα',
    'csv_roles' => 'Ρόλοι',
    'csv_activity' => 'Δραστηριότητα',
    'csv_when' => 'Πότε',
    'csv_action' => 'Ενέργεια',
    'range_this_year' => 'Φέτος',
    'range_custom' => 'Προσαρμοσμένο διάστημα',
    'range_all_time' => 'Όλη η περίοδος',
];
