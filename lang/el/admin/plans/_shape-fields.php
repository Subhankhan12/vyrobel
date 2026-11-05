<?php

// I18N-01 — el translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Μοντέλο τιμολόγησης',
    'pricing_shape_intro' => 'Ένα <strong>σταθερό</strong> πακέτο χρεώνει μία τιμή όσες μονάδες κι αν λειτουργεί ο λογαριασμός. Ένα πακέτο <strong>ανά μονάδα</strong> χρεώνει μια τιμή για κάθε ενεργή μονάδα, με νέα καταμέτρηση σε κάθε περίοδο.',
    'shape_label' => 'Μοντέλο',
    'shape_flat_option' => 'Σταθερό — μία τιμή ανά περίοδο',
    'shape_per_unit_option' => 'Ανά μονάδα — τιμή × ενεργές μονάδες',
    'billing_interval_label' => 'Κύκλος τιμολόγησης',
    'interval_monthly_option' => 'Μηνιαία',
    'interval_yearly_option' => 'Ετήσια',
    'price_label' => 'Τιμή',
    'price_hint' => 'Το τελικό ποσό — με ΦΠΑ.',
    'unit_rate_label' => 'Τιμή ανά μονάδα',
    'unit_rate_hint' => 'Χωρίς φόρο — το Stripe προσθέτει τον φόρο του πελάτη.',
    'currency_label' => 'Νόμισμα',
    'vat_pct_label' => 'ΦΠΑ % <span style="color: var(--ad-faint); font-weight: 400;">· προαιρετικό</span>',
    'stripe_price_heading' => 'Τιμή Stripe',
    'stripe_price_intro' => 'Δημιουργήστε την τιμή (Price) στον πίνακα εργαλείων του Stripe και επικολλήστε εδώ το ID της — αυτή η φόρμα δεν μπορεί να τη δημιουργήσει. Μια τιμή ανά μονάδα πρέπει να έχει <strong>τιμολόγηση ανά μονάδα (per-unit) και φόρο εκτός τιμής (exclusive)</strong>, ενώ μια σταθερή τιμή πρέπει να έχει <strong>μοντέλο licensed και φόρο εντός τιμής (inclusive)</strong>. Δείτε το <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'ID τιμής Stripe',
    'draft_not_offered' => 'Πρόχειρο — δεν προσφέρεται στους πελάτες.',
];
