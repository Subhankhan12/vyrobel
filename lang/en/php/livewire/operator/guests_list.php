<?php

// app/Livewire/Operator/GuestsList.php — the operator Guests CRM list (/app/guests): its subtitle, row labels, filter chips and sort order.

return [
    'subtitle_profiles' => '{1} :count guest profile|[0,*] :count guest profiles',
    'anonymized_guest' => 'Anonymized guest',
    'unnamed_guest' => 'Unnamed guest',
    // the Stay cell for a guest checked in right now
    'in_house' => 'In house',
    // a saved nationality the country list does not have, offered in Edit as it was saved
    'country_as_saved' => ':country (as saved)',
    // the filter chips (All is common.all)
    'chip_in_house' => 'In-house',
    'chip_upcoming' => 'Upcoming',
    'chip_vip' => 'VIP',
    // how the list is sorted — read after "Guests," in the table caption and after the count in the footer
    'sorted_name_asc' => 'A–Z by surname',
    'sorted_name_desc' => 'Z–A by surname',
    'sorted_stays_desc' => 'most stays first',
    'sorted_stays_asc' => 'fewest stays first',
    'sorted_last_stay_desc' => 'latest stay first',
    'sorted_last_stay_asc' => 'earliest stay first',
];
