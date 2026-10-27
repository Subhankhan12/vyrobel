<?php

// resources/views/livewire/operator/dashboard.blade.php — the operator back office's Dashboard (/app/dashboard).

return [
    // the page heading: a time-of-day greeting with the staff member's first name
    'greeting_morning' => 'Good morning, :name',
    'greeting_afternoon' => 'Good afternoon, :name',
    'greeting_evening' => 'Good evening, :name',
    // used in place of the first name when the account has none
    'greeting_no_name' => 'there',
    // the page subtitle: the property (or "All properties") and the day shown
    'subtitle' => ':property · :date',
    'no_property_yet' => 'No property yet',
    'previous_day' => 'Previous day',
    'next_day' => 'Next day',
    'no_property_empty' => 'No property yet — add a property to see its dashboard.',
    // the KPI tiles
    'kpi_arrivals_today' => 'Arrivals today',
    'kpi_departures_today' => 'Departures today',
    'kpi_occupancy_tonight' => 'Occupancy tonight',
    'rooms_sold_of' => ':sold of :count rooms sold',
    'kpi_revenue_today' => 'Revenue today',
    // under the revenue figure: money taken today, not revenue booked
    'revenue_collected' => 'collected',
    'kpi_open_work_orders' => 'Open work orders',
    'urgent_count' => ':count urgent',
    // Today's tasks card
    'tasks_title' => 'Today’s tasks',
    'all_caught_up' => 'All caught up',
    'group_by' => 'Group by',
    'group_by_priority' => 'Priority',
    'group_by_category' => 'Category',
    // "ticked" is the person's own note, never "done": the work is still open
    'ticked_all' => 'You’ve ticked off all :count — the work itself is still open below',
    'ticked_of' => ':ticked of :count ticked off',
    'priority_urgent' => 'Urgent',
    'priority_high' => 'High',
    'priority_normal' => 'Normal',
    // :task is the task's own label
    'untick_aria' => 'Un-tick “:task” on your own list',
    'tick_aria' => 'Tick off “:task” on your own list',
    // screen-reader text beside a row's priority dot, e.g. "Urgent priority"
    'priority_sr' => ':priority priority',
    'all_caught_up_empty' => 'All caught up — nothing needs attention right now.',
    // Arrivals card
    'arrivals_title' => 'Arrivals · today',
    'view_all' => 'View all →',
    // one arrival's detail line: reference · N nights · the unit (or "Unassigned")
    'arrival_detail' => '{1} :reference · :count night · :unit|[0,*] :reference · :count nights · :unit',
    'unassigned' => 'Unassigned',
    // the guest's own estimated arrival, printed exactly as they typed it
    'expected_arrival' => 'Arrival · :time',
    'open_link' => 'Open →',
    'no_arrivals' => 'No arrivals for this day.',
    // Occupancy chart
    'occupancy_week_title' => 'Occupancy · next 7 days',
    'this_week' => 'This week',
    // Tonight panel
    'tonight' => 'Tonight',
    'occupancy' => 'occupancy',
    'rooms_sold' => 'Rooms sold',
    'adr' => 'ADR',
    // Housekeeping card
    'housekeeping' => 'Housekeeping',
    'hk_dirty' => 'Dirty',
    'hk_in_progress' => 'In progress',
    'hk_clean' => 'Clean',
    'hk_inspected' => 'Inspected',
    'to_clean' => ':count to clean',
    'done_of' => ':done/:count done',
    'open_board' => 'Open board →',
    // Maintenance card
    'maintenance' => 'Maintenance',
    'mt_open' => 'Open',
    'mt_urgent' => 'Urgent',
    'mt_awaiting_parts' => 'Awaiting parts',
    'mt_out_of_order' => 'Out of order',
    'units_out_of_order' => '{1} :count unit out of order|[0,*] :count units out of order',
    // :amount is the nightly rate of the out-of-order units
    'on_hold_per_night' => ':amount / night on hold',
    // Channels card
    'channels' => 'Channels',
    'synced' => 'Synced',
    // :when is how long ago the last sync ran, e.g. "Synced · 5 minutes ago"
    'synced_ago' => 'Synced · :when',
    'not_connected' => 'Not connected',
    'channel_sync_note' => 'Channel sync activates with your OTA connection.',
];
