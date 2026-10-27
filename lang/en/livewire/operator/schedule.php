<?php

// resources/views/livewire/operator/schedule.blade.php — Team schedule (/app/schedule): the roster week grid, shifts, time off.

return [
    'title' => 'Team schedule',
    'no_property' => 'No property',
    'no_property_body' => 'Add a property to start building a team roster.',
    // week navigation
    'last_week' => '‹ Last week',
    'this_week' => 'This week',
    'next_week' => 'Next week ›',
    // :count staffers have a shift on a day they are on approved leave
    'conflicts' => '{1} :count on leave & shifted|[0,*] :count on leave & shifted',
    // the Week / Day view toggle
    'view_week' => 'Week',
    'view_day' => 'Day',
    'pdf_title' => 'Download this week as a PDF',
    'pdf_link' => 'PDF',
    'republish' => 'Re-publish',
    'publish_week' => 'Publish week',
    'published_title' => 'The team was notified',
    // :when is a relative time ("2 hours ago")
    'published_at' => 'Published :when',
    // the link stays inside the sentence
    'empty_team' => 'No staff on your team yet — invite your team in <a href=":url" style="color:var(--vy-accent)">Roles & access</a>.',
    // :department is a team name (Front desk, Housekeeping, Manager)
    'empty_department' => 'No staff in :department for this week.',
    // day view
    'working_count' => '{1} :count working|[0,*] :count working',
    'group_people' => '{1} :count person|[0,*] :count people',
    'on_leave_conflict_title' => 'On approved leave — clear the shift',
    'no_one_scheduled' => 'No one scheduled',
    // :number is how many staff are not working that day
    'not_working' => 'Not working · :number',
    'everyone_working' => 'Everyone is working.',
    'switch_to_week' => 'Switch to <a href=":url" style="color:var(--vy-accent)">Week view</a> to add or move shifts.',
    // week grid
    'drag_hint' => 'Drag a shift to move it — or drop it on another person\'s day to swap.',
    'drag_hint_manage' => 'Drag a shift to move it — or drop it on another person\'s day to swap. Click a cell to add or change a shift.',
    'add_shift_button' => '+ Add shift',
    'team_column' => 'Team',
    'open_day_view' => 'Open day view',
    'drag_title' => 'Drag to move · click to change',
    'on_leave_flag' => '⚠ on leave',
    'approved_leave' => 'Approved leave',
    'on_leave' => 'On leave',
    'add_a_shift' => 'Add a shift',
    // :early_hours, :day_hours and :late_hours are each shift's time range ("07:00–15:00")
    'shift_legend' => 'Early :early_hours · Day :day_hours · Late :late_hours. On-leave days block shifts; a shift already set that later clashes with approved leave is flagged in red. Roster is per property.',
    // the add / change shift dialog
    'modal_add_shift' => 'Add shift',
    'modal_change_shift' => 'Change shift',
    'team_member' => 'Team member',
    'choose' => 'Choose…',
    // the dialog's date field
    'day_field' => 'Day',
    'shift_field' => 'Shift',
    'off_hint' => '“Off” clears the shift. A working shift can’t land on an approved-leave day.',
    'save_shift' => 'Save shift',
    // coverage rail
    'coverage_heading' => 'Coverage this week',
    // :filled of :total days have someone from that team working
    'coverage_days' => '{1} :filled/:total days|[0,*] :filled/:total days',
    'no_teams' => 'No teams rostered yet.',
    'coverage_hint' => 'Days this week with at least one person from each team working.',
    'team_on_rota' => 'Team on rota',
    'shifts_this_week' => 'Shifts this week',
    'rostered_hours' => 'Rostered hours',
    // ":hoursh" is the number of hours followed by "h"
    'hours_value' => ':hoursh',
    // open shifts
    // :number is how many open shifts there are
    'open_shifts' => 'Open shifts · :number',
    'add_open_shift' => 'Add open shift',
    // the shift-type options when posting an open shift
    'shift_early' => 'Early',
    'shift_day' => 'Day',
    'shift_late' => 'Late',
    'department_optional' => 'Department (optional)',
    'post_open_shift' => 'Post open shift',
    'assign_to' => 'Assign to',
    'assign_placeholder' => 'Assign…',
    'assign' => 'Assign',
    'remove_open_shift_confirm' => 'Remove this open shift?',
    'no_open_shifts' => 'No open shifts — every slot is covered.',
    // time off
    'request_time_off' => 'Request time off',
    'request_time_off_hint' => 'Your own request goes to a manager to approve. Approved leave shows on the roster and blocks shifts.',
    'from' => 'From',
    'to' => 'To',
    'reason_optional_label' => 'Reason <span style="color:var(--vy-faint)">(optional)</span>',
    'reason_example' => 'Annual leave',
    'submit_request' => 'Submit request',
    'pending_time_off' => 'Pending time-off',
    'awaiting_another_manager' => 'Awaiting another manager',
    'approve' => 'Approve',
    'decline' => 'Decline',
    'no_pending' => 'No pending requests.',
    'add_leave_heading' => 'Add leave for a team member',
    'choose_team_member' => 'Choose a team member…',
    'reason_optional' => 'Reason (optional)',
    'add_leave' => 'Add leave',
];
