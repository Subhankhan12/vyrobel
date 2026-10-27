<?php

// app/Livewire/Operator/RatesGrid.php — the operator Rates page's texts built in the component.

return [
    // ⚠️ R-SEASON: the English MUST stay word for word App\Support\Pricing\SeasonPrecedence::SENTENCE (SeasonPrecedenceTest
    // asserts the page contains that constant) — re-applying the covering seasons weakest-first IS the design.
    'season_rule' => 'When seasons overlap, the shorter season sets the price for the nights they share; if two are the same length, the one created most recently wins.',
    // a derived rate plan's line: its adjustment and the plan it follows
    'derived_from' => ':pct% · derived from :plan',
    'derived_from_base' => ':pct% · derived from base',
    'free_cancellation' => 'Free cancellation',
    'free_cancellation_main' => 'Free cancellation · the grid above',
    'non_refundable' => 'Non-refundable',
    'non_refundable_main' => 'Non-refundable · the grid above',
    'no_prices_fortnight' => 'No prices in this fortnight',
    // :priced = nights with a price, :count = nights in the fortnight, :range = the lowest price (– the highest)
    'priced_on' => '{1} Priced on :priced of :count night this fortnight · :range|[0,*] Priced on :priced of :count nights this fortnight · :range',
    'channel_direct' => 'Direct',
    'direct_booking' => 'Direct booking',
    'status_live' => 'Live',
    'status_connected' => 'Connected',
    'status_needs_attention' => 'Needs attention',
    'status_disabled' => 'Disabled',
    'status_not_connected' => 'Not connected',
    'channels_summary' => '{1} :count live · OTA sync needs setup|[0,*] :count live · OTA sync needs setup',
    // the editor's plan picker, for a room type with more than one base plan
    'plan_choice_main' => ':plan — the plan the grid shows',
    'plan_choice_off_sale' => ':plan (off sale)',
    'plan_choice_main_off_sale' => ':plan — the plan the grid shows (off sale)',
    // under a room type's name: :affected = units a room-type save reaches, :count = all its units
    'units_label_partial' => '{1} :affected of :count unit · :overridden priced individually|[0,*] :affected of :count units · :overridden priced individually',
    'units_count' => '{1} :count unit|[0,*] :count units',
    'subtitle' => '{1} :count room type|[0,*] :count room types',
];
