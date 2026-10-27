<?php

// resources/views/admin/partials/fields.blade.php — the console's field system (the searchable dropdown's own words).

return [
    'choose' => 'Choose',
    'search_placeholder' => 'Search…',
    'search_named' => 'Search :name',
    'none_chosen' => 'None chosen',
    // a dropdown's accessible name: the field's label, then what it holds
    'trigger_label' => ':name: :value',
    'chosen_count' => '{1} :count chosen — :list|[0,*] :count chosen — :list',
    'nothing_matches' => 'Nothing matches “:filter”.',
    'choose_an_option' => 'Choose an option.',
];
