<?php

// app/Support/Owner/OwnerUpcomingStays.php — the owner Overview's "Upcoming stays" rows (status, source, detail line).

return [
    'not_yet_attributed' => 'Not yet attributed',
    // ":unit · 3 nights" — the unit's name (or "—") then the stay's length
    'detail_unit_nights' => '{1} :unit · :count night|[0,*] :unit · :count nights',
    'status_confirmed' => 'Confirmed',
    'status_pending' => 'Pending',
    'source_direct' => 'Direct',
    'source_corporate' => 'Corporate',
    'source_group' => 'Group',
    'source_walk_in' => 'Walk-in',
    'source_ota' => 'OTA',
];
