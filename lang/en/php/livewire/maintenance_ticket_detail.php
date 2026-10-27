<?php

// app/Livewire/MaintenanceTicketDetail.php — the Work order detail: labels built for the page, keyed by their codes.

return [
    // the composer's kinds of update, keyed by the note kind code
    'kind_note' => 'Note',
    'kind_progress' => 'Progress',
    'kind_part' => 'Part ordered',
    // elapsed time and logged hours, short forms
    'elapsed_minutes' => ':minutesm',
    'elapsed_hours' => ':hoursh :minutesm',
    'elapsed_days' => ':daysd :hoursh',
    'hours_short' => ':hoursh',
    // the activity timeline, keyed by the audit action code
    'timeline_opened' => 'Reported',
    'timeline_assigned_to' => 'Assigned to :name',
    'a_technician' => 'a technician',
    'timeline_unassigned' => 'Unassigned',
    'timeline_started' => 'Work started',
    'timeline_resolved' => 'Resolved',
    'timeline_cancelled' => 'Cancelled',
    'timeline_parts_updated' => 'Parts updated',
    'timeline_cost_corrected' => 'Cost corrected',
    'timeline_updated' => 'Details edited',
    // timeline notes, keyed by the note kind code
    'note_progress' => 'Progress update',
    'note_part' => 'Part ordered',
    'note_added' => 'Note added',
    'a_team_member' => 'A team member',
    'system' => 'System',
    'photo_alt' => 'Maintenance photo',
    'the_owner' => 'the owner',
    // the Reassign picker's workload line
    'load_none' => 'No open work orders',
    'load_open' => ':open open',
    'load_open_urgent' => ':open open · :urgent urgent',
    // the out-of-order impact headline
    'impact_arrivals' => '{1} :count arrival tonight needs a room|[0,*] :count arrivals tonight need a room',
    'impact_bookings' => '{1} :count booking needs to be moved|[0,*] :count bookings need to be moved',
    'impact_none' => 'No bookings affected',
];
