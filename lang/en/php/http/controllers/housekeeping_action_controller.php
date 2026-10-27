<?php

// app/Http/Controllers/HousekeepingActionController.php — the housekeeping board's actions (status, assign, photos).

return [
    // :status is one of the is_* words, :target one of the marked_* words
    'cannot_mark' => 'Unit :unit is :status — it can’t be marked :target from there.',
    'marked' => 'Unit :unit marked :target.',
    'assigned' => 'Assigned :name to unit :unit.',
    'unassigned' => 'Unit :unit unassigned.',
    'rooms_unassigned' => '{1} :count room unassigned.|[0,*] :count rooms unassigned.',
    'nothing_to_undo' => 'Nothing to undo.',
    'no_housekeepers' => 'No housekeepers on the roster to assign to.',
    'rooms_assigned' => '{1} :count room assigned · queues balanced.|[0,*] :count rooms assigned · queues balanced.',
    'nothing_to_assign' => 'Nothing to assign — every room already has a housekeeper.',
    'photo_added' => 'Photo added.',
    'photo_removed' => 'Photo removed.',
    'workspace_paused' => 'Your workspace is paused (read-only) — housekeeping changes are off until you resume.',
    // the status a task is being marked
    'marked_in_progress' => 'In progress',
    'marked_clean' => 'Clean',
    'marked_inspected' => 'Inspected',
    // the status a task is in now
    'is_pending' => 'pending',
    'is_in_progress' => 'in progress',
    'is_clean' => 'clean',
    'is_inspected' => 'inspected',
];
