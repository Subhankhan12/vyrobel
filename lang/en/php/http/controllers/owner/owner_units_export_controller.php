<?php

// app/Http/Controllers/Owner/OwnerUnitsExportController.php — the My Units occupancy CSV (column headings, the total row).

return [
    'col_date' => 'Date',
    'col_units_booked' => 'Units booked',
    'col_total_units' => 'Total units',
    'col_booked_pct' => 'Booked %',
    'month_total' => 'Month total',
    // the English printed "nights"/"units" for every count before; kept so the file reads the same
    'total_nights' => '{1} :count night|[0,*] :count nights',
    'total_units' => '{1} :count unit|[0,*] :count units',
];
