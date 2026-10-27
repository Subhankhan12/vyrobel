<?php

// app/Livewire/Operator/OperatorOwners.php — the Owners list's chips, sort phrases, figures and invite labels.

return [
    // the chips (All is common.all)
    'chip_ready' => 'Ready to pay',
    'chip_hold' => 'On hold',
    'chip_review' => 'Needs review',
    'chip_paused' => 'Payouts paused',
    'chip_invited' => 'Invited',
    // how the list is sorted (read in the caption and the summary)
    'sorted_name_asc' => 'by owner, A–Z',
    'sorted_name_desc' => 'by owner, Z–A',
    'sorted_units_desc' => 'most units first',
    'sorted_units_asc' => 'fewest units first',
    'sorted_net_desc' => 'largest net payable first',
    'sorted_net_asc' => 'smallest net payable first',
    'sorted_status_asc' => 'what needs you first',
    'sorted_status_desc' => 'what needs you last',
    // the figures' sub-lines (each part joined by " · ")
    'kpi_invited' => ':number invited',
    'kpi_units' => '{1} :count unit|[0,*] :count units',
    'kpi_owners_ready' => '{1} :count owner ready|[0,*] :count owners ready',
    'kpi_paused_not_counted' => ':number paused, not counted',
    'kpi_hold_none' => 'nothing waiting',
    'kpi_hold_waiting' => ':amounts waiting',
    'kpi_review_sub' => ':number with lines the booking channel hasn’t confirmed',
    // how owners are paid
    'schedule' => ':day business day of the month',
    'ordinal_st' => ':numberst',
    'ordinal_nd' => ':numbernd',
    'ordinal_rd' => ':numberrd',
    'ordinal_th' => ':numberth',
    // an invite's role
    'role_manager' => 'Property manager',
    'role_accountant' => 'Accountant',
    'role_owner' => 'Owner',
    // a unit's owner whose name is not found
    'another_owner' => 'another owner',
];
