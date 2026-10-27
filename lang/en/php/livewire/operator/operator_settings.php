<?php

// app/Livewire/Operator/OperatorSettings.php — the operator Settings page's class.

return [
    'no_property_configured' => 'No property configured',
    // :plan is the plan's name from the catalogue, e.g. Essential
    'plan_tier' => 'Vyrobel :plan',
    'no_plan' => 'No plan',
    'price_per_month' => ':price/mo',
    'commission_not_set' => 'Not set',
    // :rate is the agreement's commission percentage, e.g. 18
    'commission_rate' => ':rate% commission',
    'scope_all_properties' => 'All properties',
    'scope_this_property_only' => 'This property only',
    // only reached for 2 or more properties
    'scope_properties' => '{1} :count property|[0,*] :count properties',
    'role_admin' => 'Admin',
    'role_admin_blurb' => 'Everything — settings, rates, finance, team. Set at group level (Group Admin / Property Owner), applies to all properties.',
    'role_front_desk' => 'Front desk',
    'role_front_desk_blurb' => 'Reservations, calendar, folios, guests & messages — no settings or owner finance.',
    'role_housekeeping' => 'Housekeeping',
    'role_housekeeping_blurb' => 'The housekeeping board only — room statuses and their own assignments.',
    // the tax's kind, shown beside its name in the taxes editor
    'tax_kind_vat' => 'Vat',
    'tax_kind_city_tax' => 'City tax',
    'tax_kind_tourism_tax' => 'Tourism tax',
    'tax_kind_other' => 'Other',
];
