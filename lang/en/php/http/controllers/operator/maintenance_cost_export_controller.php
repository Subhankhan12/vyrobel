<?php

// app/Http/Controllers/Operator/MaintenanceCostExportController.php — the Maintenance Costs exports (CSV, Excel workbook) and "Email to accountant".

return [
    // the CSV's first line: a label and a note saying how amounts are written
    'csv_amounts' => 'Amounts',
    'csv_amounts_note' => 'major units, decimal point, no thousands separator',
    // column headings (CSV and the workbook's Line items tab)
    'col_date' => 'Date',
    'col_work_order' => 'Work order',
    'col_unit' => 'Unit',
    'col_category' => 'Category',
    // the CSV spells it "Labor", the workbook "Labour" — kept as each file had it
    'col_labor' => 'Labor',
    'col_parts' => 'Parts',
    'col_total' => 'Total',
    'col_currency' => 'Currency',
    // the label of the reconciling totals row at the foot of the line items
    'total_row' => 'Total',
    // the workbook's Summary tab (a tab name can be up to 31 characters, without : \ / ? * [ ])
    'sheet_summary' => 'Summary',
    'col_measure' => 'Measure',
    'col_value' => 'Value',
    'total_spend' => 'Total spend',
    'measure_labour' => 'Labour',
    'measure_parts' => 'Parts',
    'orders_closed' => 'Orders closed',
    'owner_billed' => 'Owner-billed',
    'operator_borne' => 'Operator-borne',
    'parts_on_order' => 'Parts on order',
    'average_per_order' => 'Average per order',
    'by_category' => 'By category',
    'col_orders' => 'Orders',
    'by_month' => 'By month',
    'top_units' => 'Top units',
    // the workbook's second tab (31 characters at most)
    'sheet_line_items' => 'Line items',
    'col_labour' => 'Labour',
    'no_email' => 'Your account has no email address.',
    'report_emailed' => 'Costs report emailed to :email.',
];
