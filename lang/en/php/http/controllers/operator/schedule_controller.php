<?php

// app/Http/Controllers/Operator/ScheduleController.php — the team schedule's writes and the roster PDF.

return [
    'shift_updated' => 'Shift updated.',
    'shift_moved' => 'Shift moved.',
    'time_off_requested' => 'Time-off request submitted.',
    'leave_added' => 'Leave added.',
    'time_off_approved' => 'Time off approved.',
    'time_off_declined' => 'Time off declined.',
    'week_published' => 'Week published — the team has been notified.',
    'open_shift_added' => 'Open shift added.',
    'open_shift_assigned' => 'Open shift assigned.',
    'open_shift_removed' => 'Open shift removed.',
    // the roster PDF's time-off list: the kind when no reason was given, and the request's status
    'pdf_leave_kind' => 'Leave',
    'pdf_status_approved' => 'Approved',
    'pdf_status_pending' => 'Pending',
    'read_only' => 'Your workspace is read-only right now — the roster is paused.',
];
