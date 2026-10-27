<?php

// App\Support\Unit\InventoryDeletion — why a unit or a room type cannot be deleted: each thing on record as a phrase,
// listed inside the Units page's and the delete refusal's sentence ("it has :blockers on record").

return [
    // what is on record, with how many
    'bookings' => '{1} :count booking|[0,*] :count bookings',
    'room_moves' => '{1} :count room move|[0,*] :count room moves',
    'cleaning_tasks' => '{1} :count cleaning task|[0,*] :count cleaning tasks',
    'work_orders' => '{1} :count work order|[0,*] :count work orders',
    'status_changes' => '{1} :count status change|[0,*] :count status changes',
    'blocks' => '{1} :count block|[0,*] :count blocks',
    'owner_agreements' => '{1} :count owner agreement|[0,*] :count owner agreements',
    'owner_statement_lines' => '{1} :count owner statement line|[0,*] :count owner statement lines',
    'owner_expenses' => '{1} :count owner expense|[0,*] :count owner expenses',
    'reviews' => '{1} :count review|[0,*] :count reviews',
    'owner_requests' => '{1} :count owner request|[0,*] :count owner requests',
    'notes' => '{1} :count note|[0,*] :count notes',
    'documents' => '{1} :count document|[0,*] :count documents',
    'files' => '{1} :count file|[0,*] :count files',
    'units' => '{1} :count unit|[0,*] :count units',
    'rate_proposals' => '{1} :count rate proposal|[0,*] :count rate proposals',
    'overbooking_records' => '{1} :count overbooking record|[0,*] :count overbooking records',
    'guest_waitlist_entries' => '{1} :count guest waitlist entry|[0,*] :count guest waitlist entries',
    'guest_wishlist_saves' => '{1} :count guest wishlist save|[0,*] :count guest wishlist saves',
    'derived_room_types' => '{1} :count room type built on it|[0,*] :count room types built on it',
    'derived_rate_plans' => '{1} :count rate plan elsewhere priced from it|[0,*] :count rate plans elsewhere priced from it',
    'corporate_default_rates' => '{1} :count corporate account’s default rate|[0,*] :count corporate accounts’ default rate',
    'channel_connections' => '{1} :count channel connection|[0,*] :count channel connections',
    // a unit that belongs to an owner, or is promised to one by an invitation not yet accepted
    'an_owner' => 'an owner',
    'pending_owner_invitation' => 'a pending owner invitation',
    // the last two items of the list: "2 bookings, 1 note and an owner"
    'list_and' => ':items and :last',
    // the kind a room type's units were added as, in the plural ("they were added as rooms")
    'kind_rooms' => 'rooms',
    'kind_apartments' => 'apartments',
    'kind_villas' => 'villas',
    'kind_dorm_beds' => 'dorm beds',
];
