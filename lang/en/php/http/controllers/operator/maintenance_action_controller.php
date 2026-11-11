<?php

// app/Http/Controllers/Operator/MaintenanceActionController.php — Maintenance: open, assign, edit, start, resolve and cancel work orders; parts, updates, photos; bulk actions and the CSV export.

return [
    // :title is the ticket's own title, :unit the unit's code
    'ticket_opened' => 'Work order “:title” opened on unit :unit.',
    'not_assignable_staff' => 'That user is not assignable staff in this organization.',
    'cannot_reassign_closed' => 'That work order can’t be reassigned — it’s already closed.',
    'assignment_updated' => 'Work order assignment updated.',
    'work_order_closed' => 'That work order is closed — its details can’t be changed.',
    'work_order_updated' => 'Work order updated.',
    // CSV column headings of the bulk export
    'col_title' => 'Title',
    'col_unit' => 'Unit',
    'col_category' => 'Category',
    'col_priority' => 'Priority',
    'col_status' => 'Status',
    'col_assignee' => 'Assignee',
    'col_reported' => 'Reported',
    'col_cost' => 'Cost',
    // bulk results; :skipped is how many were left alone because they were already closed
    'bulk_assigned_skipped' => '{1} Assigned :count work order · :skipped skipped (already closed)|[0,*] Assigned :count work orders · :skipped skipped (already closed)',
    'bulk_assigned' => '{1} Assigned :count work order.|[0,*] Assigned :count work orders.',
    // D-MT-COPY: "No one" is an unassign
    'bulk_unassigned_skipped' => '{1} Unassigned :count work order · :skipped skipped (already closed)|[0,*] Unassigned :count work orders · :skipped skipped (already closed)',
    'bulk_unassigned' => '{1} Unassigned :count work order.|[0,*] Unassigned :count work orders.',
    'bulk_resolved_skipped' => '{1} Resolved :count work order · :skipped skipped (already closed)|[0,*] Resolved :count work orders · :skipped skipped (already closed)',
    'bulk_resolved' => '{1} Resolved :count work order.|[0,*] Resolved :count work orders.',
    'cannot_start' => 'That work order can’t be started — it’s no longer open.',
    'work_started' => 'Work started on this work order.',
    'cannot_resolve_closed' => 'That work order can’t be resolved — it’s already closed.',
    'ticket_resolved' => 'Work order resolved.',
    'cannot_cancel_closed' => 'That work order can’t be cancelled — it’s already closed.',
    'ticket_cancelled' => 'Work order cancelled.',
    'parts_closed' => 'That work order is closed — parts can’t be updated.',
    'awaiting_parts' => 'Marked as awaiting parts.',
    'parts_arrived' => 'Parts marked as arrived.',
    'update_posted_waiting_part' => 'Update posted — marked as waiting on the part.',
    'update_posted' => 'Update posted.',
    'photo_added' => 'Photo added.',
    'photo_removed' => 'Photo removed.',
    'workspace_paused' => 'Your workspace is paused (read-only) — maintenance changes are off until you resume.',
];
