<?php

// app/Livewire/MaintenanceList.php — the operator Maintenance board: filter chips, the sort description, the activity timeline.

return [
    // the filter chips, keyed by the filter code
    'chip_all' => 'All',
    'chip_active' => 'Active',
    'chip_open' => 'Not started',
    'chip_inprog' => 'In progress',
    'chip_urgent' => 'Urgent',
    'chip_parts' => 'Awaiting parts',
    'chip_ooo' => 'Out of order',
    'chip_done' => 'Resolved',
    // how the table is sorted (read after "Work orders," and in the footer)
    'sorted_priority_asc' => 'most urgent first',
    'sorted_priority_desc' => 'least urgent first',
    'sorted_due_asc' => 'soonest target first',
    'sorted_due_desc' => 'latest target first',
    'sorted_reported_desc' => 'newest first',
    'sorted_reported_asc' => 'oldest first',
    'sorted_unit_asc' => 'by unit, A–Z',
    'sorted_unit_desc' => 'by unit, Z–A',
    // the drawer's activity timeline, keyed by the audit action code
    'activity_opened' => 'Opened',
    'activity_assigned' => 'Assigned',
    'activity_started' => 'Started',
    'activity_updated' => 'Updated',
    'activity_parts_updated' => 'Parts updated',
    'activity_cost_corrected' => 'Cost corrected',
    'activity_note_added' => 'Note added',
    'activity_resolved' => 'Resolved',
    'activity_cancelled' => 'Cancelled',
];
