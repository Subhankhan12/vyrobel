<?php

// resources/views/pdfs/operator-schedule.blade.php — the team schedule (weekly rota) PDF.

return [
    'eyebrow' => 'Team schedule',
    'week_of' => 'Week of :week',
    'doc_title' => 'Weekly rota',
    'doc_sub' => ':property · week of :week · generated :generated',
    'published' => 'Published :date',
    'published_by' => 'Published :date by :name',
    'not_published' => 'Not yet published — this week has not been sent to the team.',
    'team' => 'Team',
    'shifts' => 'Shifts',
    'hours' => 'Hours',
    // the count of open (unassigned) shifts
    'open' => 'Open',
    'conflicts' => 'Conflicts',
    'no_staff' => 'No staff on this roster for the selected week.',
    'team_member' => 'Team member',
    'cell_conflict' => ':shift / Leave!',
    'cell_leave' => 'Leave',
    'legend' => '<strong>Reading this rota:</strong> a cell shows the shift name and its times · <strong>Leave</strong> = approved time off · <strong>—</strong> = not rostered · <strong>“/ Leave!”</strong> = a shift on an approved-leave day. Every state is written out, so this prints correctly in black and white.',
    'shift_hours' => ':early :early_hours · :day :day_hours · :late :late_hours · shift times shown per cell.',
    'conflict_count' => '{1} <strong style="color:#B0322A">:count conflict</strong> — a shift on an approved-leave day.|[0,*] <strong style="color:#B0322A">:count conflicts</strong> — a shift on an approved-leave day.',
    'open_shifts' => 'Open shifts',
    'all_assigned' => 'Every shift on this rota is assigned — no open slots this week.',
    'any_department' => 'Any department',
    'time_off' => 'Time off',
    'foot_generated' => ':property · generated :generated',
    'foot_week' => 'Week schedule · :week',
    'snapshot_note' => 'This rota is a snapshot of the roster as it stood on :generated — any later change will not appear on this printout. Shift-swap requests go through the Vyrobel app or the front desk.',
];
