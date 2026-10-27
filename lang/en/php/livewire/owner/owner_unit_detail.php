<?php

// app/Livewire/Owner/OwnerUnitDetail.php — the labels the owner Unit Detail page builds in PHP.

return [
    // a stay's or statement's first and last day, e.g. "3 Oct – 5 Oct 2026"
    'date_range' => ':start – :end',
    // the day-detail popover: shown when a booking has no reservation to name the guest from
    'guest_fallback' => 'Guest',
    // the day-detail popover's stay length
    'nights' => '{1} :count night|[0,*] :count nights',
    // a blocked day's reason (inventory_blocks.reason); "Blocked" when none is stored
    'block_reason_none' => 'Blocked',
    'block_reason_maintenance' => 'Maintenance',
    'block_reason_owner' => 'Owner',
    'block_reason_manual' => 'Manual',
    'block_reason_overbooking_protection' => 'Overbooking protection',
    // cleaning schedule: task type
    'cleaning_departure' => 'Departure clean',
    'cleaning_stayover' => 'Stayover service',
    'cleaning_deep' => 'Deep clean',
    'cleaning_arrival_prep' => 'Arrival prep',
    // cleaning schedule: task status
    'cleaning_status_pending' => 'Scheduled',
    'cleaning_status_in_progress' => 'In progress',
    'cleaning_status_clean' => 'Cleaned',
    'cleaning_status_inspected' => 'Inspected',
    // unit kind
    'kind_room' => 'Room',
    'kind_apartment' => 'Apartment',
    'kind_villa' => 'Villa',
    'kind_dorm_bed' => 'Dorm bed',
    // unit condition pill
    'condition_out_of_order' => 'Out of order',
    'condition_out_of_service' => 'Out of service',
    // reservation status in the Stays table
    'status_enquiry' => 'Enquiry',
    'status_tentative' => 'Tentative',
    'status_confirmed' => 'Confirmed',
    'status_checked_in' => 'Checked in',
    'status_checked_out' => 'Checked out',
    'status_cancelled' => 'Cancelled',
    'status_no_show' => 'No-show',
    // which agreement sets the commission
    'terms_portfolio' => 'Portfolio-wide terms',
    'terms_unit' => 'Terms for this unit',
];
