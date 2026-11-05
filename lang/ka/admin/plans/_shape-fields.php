<?php

// I18N-01 — ka translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'ფასის მოდელი',
    'pricing_shape_intro' => '<strong>ფიქსირებული</strong> ტარიფი ერთ ფასს ითვალისწინებს, რამდენი ერთეულიც არ უნდა ჰქონდეს ანგარიშს. <strong>ერთეულზე</strong> დაფუძნებული ტარიფი ფასს თითოეულ აქტიურ ერთეულზე იანგარიშებს და ერთეულებს ყოველ პერიოდში თავიდან ითვლის.',
    'shape_label' => 'მოდელი',
    'shape_flat_option' => 'ფიქსირებული – ერთი ფასი პერიოდზე',
    'shape_per_unit_option' => 'ერთეულზე – ფასი × აქტიური ერთეულები',
    'billing_interval_label' => 'ანგარიშსწორების პერიოდი',
    'interval_monthly_option' => 'ყოველთვიური',
    'interval_yearly_option' => 'ყოველწლიური',
    'price_label' => 'ფასი',
    'price_hint' => 'საბოლოო თანხა – დღგ-ის ჩათვლით.',
    'unit_rate_label' => 'ფასი ერთეულზე',
    'unit_rate_hint' => 'გადასახადის გარეშე – Stripe დაამატებს კლიენტის საკუთარ გადასახადს.',
    'currency_label' => 'ვალუტა',
    'vat_pct_label' => 'დღგ % <span style="color: var(--ad-faint); font-weight: 400;">· არასავალდებულო</span>',
    'stripe_price_heading' => 'Stripe Price',
    'stripe_price_intro' => 'შექმენით Price Stripe-ის მართვის პანელში, შემდეგ კი მისი ID აქ ჩასვით – ამ პანელს მისი შექმნა არ შეუძლია. ერთეულზე დაფუძნებული Price უნდა იყოს <strong>ერთეულზე ანგარიშსწორებით (per-unit), გადასახადი ფასში არ შედის (exclusive)</strong>; ფიქსირებული – <strong>ლიცენზირებული (licensed), გადასახადი ფასში შედის (inclusive)</strong>. იხ. <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'Stripe Price-ის ID',
    'draft_not_offered' => 'მონახაზი – კლიენტებისთვის არ არის ხელმისაწვდომი.',
];
