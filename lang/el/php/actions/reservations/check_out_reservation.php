<?php

// I18N-01 — el translation of lang/en/php/actions/reservations/check_out_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'override_reason_unknown' => 'Αυτός δεν είναι αναγνωρισμένος λόγος παράκαμψης στο check-out.',
    'override_note_required' => 'Μια παράκαμψη με λόγο «Άλλο» απαιτεί σημείωση που εξηγεί τι συνέβη.',
    'refused_confirmed' => 'Δεν είναι δυνατό το check-out σε επιβεβαιωμένη κράτηση: για να γίνει check-out, πρέπει πρώτα να έχει γίνει check-in. Αν ο επισκέπτης διέμεινε και αναχωρεί νωρίτερα, κάντε πρώτα check-in και μετά check-out. Αν δεν έφτασε ποτέ, σημειώστε την κράτηση ως μη εμφάνιση.',
    'refused_closed' => 'Δεν είναι δυνατό το check-out σε κράτηση με κατάσταση «:status»: η κράτηση έχει κλείσει και δεν πραγματοποιήθηκε διαμονή. Ένας επισκέπτης που εμφανίστηκε τελικά χρειάζεται νέα κράτηση.',
    'refused_other' => 'Δεν είναι δυνατό το check-out σε κράτηση με κατάσταση «:status».',
];
