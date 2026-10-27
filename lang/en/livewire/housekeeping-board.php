<?php

// resources/views/livewire/housekeeping-board.blade.php — the operator's Housekeeping board (/app/housekeeping).

return [
    'page_title' => 'Housekeeping',
    'page_subtitle' => 'Cleaning across your rooms, by day',
    'view_only_notice' => 'You can see the cleaning, but not update it. Changing a room’s cleaning status needs housekeeping access — a Property Owner or Group Admin can grant it.',
    'undo' => 'Undo',
    'no_roster_notice' => 'No housekeepers on the roster yet, so rooms can’t be assigned.',
    // the link stays inside the sentence so each language places it where its grammar needs it
    'no_roster_grant_link' => 'Give a team member the Housekeeping role in <a class="vy-gl-link" href=":url">Roles & access</a>.',
    'no_roster_ask' => 'Someone who manages your team can give a team member the Housekeeping role.',
    // the housekeeping stages (tally chips and the room detail's stage pipeline)
    'status_pending' => 'Pending',
    'status_in_progress' => 'In progress',
    'status_clean' => 'Clean',
    'status_inspected' => 'Inspected',
    'status_out_of_order' => 'Out of order',
    'search_label' => 'Search rooms',
    'search_placeholder' => 'Search room, type or housekeeper',
    'clear_search' => 'Clear search',
    'view_switch_label' => 'View',
    'view_list' => 'List',
    'view_board' => 'Board',
    // :number is how many rooms have no housekeeper yet
    'assign_all_button' => 'Assign all · :number',
    'housekeeper' => 'Housekeeper',
    'everyone' => 'Everyone',
    'unassigned' => 'Unassigned',
    'crew' => 'Crew',
    'rooms_open' => '{1} :count room open|[0,*] :count rooms open',
    'no_housekeepers' => 'No housekeepers on the roster yet — add staff with the Housekeeping role.',
    // :number is how many rooms are out of order
    'out_of_order_count' => 'Out of order · :number',
    'schedule_link' => 'Schedule →',
    'lane_empty' => 'Nothing here',
    'col_room' => 'Room',
    'col_day' => 'Day',
    'col_stage' => 'Stage',
    // the column with the hour a room must be ready by
    'col_ready' => 'Ready',
    'col_next_step' => 'Next step',
    'turnover' => 'Turnover',
    'vip' => 'VIP',
    'assign' => 'Assign',
    // the tag on a room that is inspected and ready for a guest
    'ready_tag' => 'Ready',
    // minutes spent / minutes expected, e.g. "12m / 30m"
    'elapsed_of_expected' => ':minutesm / :expectedm',
    // :time is the property's check-in time, e.g. "by 15:00"
    'ready_by_time' => 'by :time',
    'no_rooms_match' => 'No rooms match this view.',
    'assign_housekeeper' => 'Assign housekeeper',
    'suggested' => 'Suggested',
    'open_load' => '[0,*] :count open',
    'clean_next_priority' => 'Clean next · priority',
    'assign_hint' => 'It shows under their name on this board. Nothing is sent to their phone.',
    'assign_all_title' => 'Assign all unassigned',
    'assign_all_sub' => 'Shared by load — whoever has the fewest open rooms gets the next one, rooms with a guest waiting first.',
    // :housekeepers is the phrase from housekeepers_count below, e.g. "2 housekeepers"
    'assign_all_lead' => '{1} :count unassigned room will be shared across :housekeepers.|[0,*] :count unassigned rooms will be shared across :housekeepers.',
    'housekeepers_count' => '{1} :count housekeeper|[0,*] :count housekeepers',
    'assign_all_hint' => 'Each room shows under its housekeeper’s name on this board.',
    'assign_rooms_button' => '{1} Assign :count room|[0,*] Assign :count rooms',
    'task_detail' => 'Task detail',
    'elapsed' => 'Elapsed',
    'ready_by' => 'Ready by',
    'reassign_housekeeper' => 'Reassign housekeeper',
    'photos' => 'Photos',
    'proof_photo' => 'Proof photo',
    'remove_photo_confirm' => 'Remove this photo? The image file is deleted — this cannot be undone.',
    'remove_photo' => 'Remove photo',
    'add_photo_hint' => 'Add a photo of the finished room.',
    'take_or_upload_photo' => 'Take or upload a photo',
    'guest_ready' => 'Guest-ready',
];
