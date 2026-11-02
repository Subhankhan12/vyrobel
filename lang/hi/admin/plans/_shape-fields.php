<?php

// I18N-01 — hi translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'कीमत का ढाँचा',
    'pricing_shape_intro' => '<strong>फ़्लैट</strong> प्लान एक ही कीमत लेता है, चाहे अकाउंट में कितने भी कमरे हों। <strong>प्रति-कमरा</strong> प्लान हर सक्रिय कमरे के लिए एक रेट लेता है, जिसे हर अवधि में फिर से गिना जाता है।',
    'shape_label' => 'ढाँचा',
    'shape_flat_option' => 'फ़्लैट — हर अवधि की एक कीमत',
    'shape_per_unit_option' => 'प्रति कमरा — रेट × सक्रिय कमरे',
    'billing_interval_label' => 'बिलिंग अवधि',
    'interval_monthly_option' => 'मासिक',
    'interval_yearly_option' => 'वार्षिक',
    'price_label' => 'कीमत',
    'price_hint' => 'अंतिम राशि — VAT सहित।',
    'unit_rate_label' => 'प्रति कमरा रेट',
    'unit_rate_hint' => 'कर से पहले — Stripe ग्राहक का अपना कर जोड़ता है।',
    'currency_label' => 'मुद्रा',
    'vat_pct_label' => 'VAT % <span style="color: var(--ad-faint); font-weight: 400;">· वैकल्पिक</span>',
    'stripe_price_heading' => 'Stripe प्राइस',
    'stripe_price_intro' => 'Stripe डैशबोर्ड में प्राइस बनाएँ, फिर उसकी आईडी यहाँ पेस्ट करें — यह पैनल इसे नहीं बना सकता। प्रति-कमरा प्राइस <strong>प्रति-यूनिट बिलिंग, टैक्स बिहेवियर एक्सक्लूसिव</strong> होना चाहिए; फ़्लैट प्राइस <strong>लाइसेंस्ड, इनक्लूसिव</strong>। देखें <code>docs/BILLING-STRIPE-PRICES.md</code>।',
    'stripe_price_id_label' => 'Stripe प्राइस आईडी',
    'draft_not_offered' => 'ड्राफ़्ट — ग्राहकों को ऑफ़र नहीं किया जाता।',
];
