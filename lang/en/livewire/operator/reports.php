<?php

// resources/views/livewire/operator/reports.blade.php — the operator back office's Reports page (/app/reports).

return [
    'page_title' => 'Reports',
    // the page subtitle: :scope is the property name (or "All properties"), :range the reporting period's label
    'subtitle' => ':scope · arrivals in :range',
    // the "jump to a specific month" picker
    'reporting_month' => 'Reporting month',
    'jump_to_month' => 'Jump to month…',
    // the Exports menu
    'exports' => 'Exports',
    'pdf_report' => 'PDF report',
    'pdf_report_sub' => 'Revenue, KPIs & channel mix',
    'csv_export' => 'CSV export',
    'csv_export_sub' => 'Channel & month rows · opens in Excel',
    'excel_workbook' => 'Excel workbook',
    'excel_workbook_sub' => 'Channels & trend tabs · figures sum',
    'coming_soon' => 'Coming soon',
    'email_as_pdf' => 'Email as PDF',
    'soon' => 'Soon',
    'email_as_pdf_sub' => 'Send the report to your inbox',
    // the empty period
    'empty' => 'No arrivals or costs in :range for :scope yet. Real data only — nothing is invented for an empty period.',
    'kpi_note' => 'Occupancy · ADR · RevPAR are on the stay-night basis; deltas are vs the same period last year, shown only where prior-year data exists. Revenue below is on the folio (arrival) basis.',
    // Revenue card
    'revenue' => 'Revenue',
    // the arrivals count beside the Revenue heading; 0 printed "0 arrivals" before
    'arrivals_count' => '{1} :count arrival|[0,*] :count arrivals',
    'room' => 'Room',
    'ancillary' => 'Ancillary',
    'ancillary_note' => '· add-ons, products, fees',
    'gross_revenue' => 'Gross revenue',
    'tax_collected' => 'Tax collected',
    'tax_collected_note' => '· pass-through',
    // Costs card
    'costs' => 'Costs',
    'costs_note' => 'real data only',
    'maintenance' => 'Maintenance',
    'maintenance_note' => '· resolved work orders',
    'not_tracked_yet' => 'Not tracked yet',
    'net' => 'Net (revenue − tracked costs)',
    // Top units card
    'top_units' => 'Top units',
    'top_units_note' => 'by room revenue · arrivals in :range',
    'no_unit_revenue' => 'No unit revenue in this range yet.',
    'col_unit' => 'Unit',
    'col_nights' => 'Nights',
    'col_room_revenue' => 'Room revenue',
    // Revenue by channel card
    'revenue_by_channel' => 'Revenue by channel',
    'revenue_by_channel_note' => 'by booking source · reconciles to gross',
    'no_channel_revenue' => 'No channel revenue in this range yet.',
    'col_channel' => 'Channel',
    'col_bookings' => 'Bookings',
    'col_revenue' => 'Revenue',
    'col_share' => 'Share',
    // Revenue by month card
    'revenue_by_month' => 'Revenue by month',
];
