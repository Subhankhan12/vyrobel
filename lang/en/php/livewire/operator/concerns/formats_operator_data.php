<?php

// app/Livewire/Operator/Concerns/FormatsOperatorData.php — the shared operator labels (statuses, kinds, channels), keyed by their codes.

return [
    // the topbar chip when no staff user is signed in
    'viewer_fallback' => 'Operator',
    // reservation status, keyed by the reservations.status code
    'reservation_confirmed' => 'Confirmed',
    'reservation_checked_in' => 'Checked in',
    'reservation_checked_out' => 'Checked out',
    'reservation_tentative' => 'Tentative',
    'reservation_enquiry' => 'Enquiry',
    'reservation_no_show' => 'No-show',
    'reservation_cancelled' => 'Cancelled',
    // housekeeping task status, keyed by the housekeeping_tasks.status code
    'hk_status_pending' => 'Pending',
    'hk_status_in_progress' => 'In progress',
    'hk_status_clean' => 'Clean',
    'hk_status_inspected' => 'Inspected',
    // housekeeping task type, keyed by the housekeeping_tasks.type code
    'hk_type_departure' => 'Departure clean',
    'hk_type_stayover' => 'Stayover',
    'hk_type_deep' => 'Deep clean',
    'hk_type_arrival_prep' => 'Arrival prep',
    // housekeeping task priority
    'priority_high' => 'High',
    'priority_low' => 'Low',
    'priority_normal' => 'Normal',
    // unit kind, keyed by the units.unit_kind code
    'unit_kind_room' => 'Room',
    'unit_kind_apartment' => 'Apartment',
    'unit_kind_villa' => 'Villa',
    'unit_kind_dorm_bed' => 'Dorm bed',
    // unit cleanliness, keyed by the units.housekeeping_status code
    'unit_hk_clean' => 'Clean',
    'unit_hk_dirty' => 'Dirty',
    'unit_hk_inspected' => 'Inspected',
    // unit condition, keyed by the units.condition_status code
    'condition_in_service' => 'In service',
    'condition_out_of_order' => 'Out of order',
    'condition_out_of_service' => 'Out of service',
    // maintenance work order status, keyed by the maintenance_tickets.status code
    'maintenance_open' => 'Open',
    'maintenance_in_progress' => 'In progress',
    'maintenance_resolved' => 'Resolved',
    'maintenance_cancelled' => 'Cancelled',
    // maintenance priority
    'maintenance_priority_urgent' => 'Urgent',
    'maintenance_priority_high' => 'High',
    'maintenance_priority_normal' => 'Normal',
    'maintenance_priority_low' => 'Low',
    // the SLA clock, keyed by its state (":hoursh" is the number of hours followed by "h")
    'sla_breached' => 'SLA breached · :hoursh over',
    'sla_due_soon' => 'Due in :hoursh',
    'sla_on_track' => ':hoursh to target',
    'sla_no_target' => 'No SLA target set',
    // maintenance category, keyed by the maintenance_tickets.category code
    'category_plumbing' => 'Plumbing',
    'category_electrical' => 'Electrical',
    'category_appliance' => 'Appliance',
    'category_structural' => 'Structural',
    'category_other' => 'Other',
    // folio line type, keyed by the folio_items.type code
    'folio_room' => 'Room',
    'folio_tax' => 'Tax',
    'folio_fee' => 'Fee',
    'folio_add_on' => 'Add-on',
    'folio_product' => 'Product',
    'folio_discount' => 'Discount',
    'folio_manual' => 'Charge',
    // inbox channel (WhatsApp, Booking.com and Airbnb are brand names and stay as they are)
    'channel_direct' => 'Direct',
    'channel_email' => 'Email',
    // payout status, keyed by the payouts.status code
    'payout_pending' => 'Pending',
    'payout_paid' => 'Paid',
    'payout_failed' => 'Failed',
    'payout_reversed' => 'Reversed',
];
