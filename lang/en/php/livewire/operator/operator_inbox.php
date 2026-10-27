<?php

// app/Livewire/Operator/OperatorInbox.php — the operator Inbox (/app/inbox): its template chips, filter chips, delivery states and fallbacks.

return [
    // the New-message dialog's template chips
    'template_pre_arrival' => 'Pre-arrival details',
    'template_payment_reminder' => 'Payment reminder',
    'template_review_request' => 'Thanks & review',
    // the Guests | Team toggle's first side (with owners once the workspace has any)
    'guests_and_owners' => 'Guests & owners',
    'guests' => 'Guests',
    // the rail preview of a conversation with no message in it
    'no_messages_yet' => 'No messages yet',
    // the first channel tab
    'all_channels' => 'All channels',
    // the active-filter chips (each clears itself)
    'chip_unread' => 'Unread',
    'chip_arriving' => 'Arriving today',
    'chip_mine' => 'Assigned to me',
    'chip_closed' => 'Closed',
    // the author of an internal note whose user is gone
    'staff_fallback' => 'Staff',
    // the author of an outgoing message with no user and no sender label
    'operator_fallback' => 'Operator',
    // a sent message's state, under its bubble ("Saved": a Direct message is kept in Vyrobel, never delivered anywhere)
    'delivery_saved' => 'Saved',
    'delivery_delivered' => 'Delivered',
    'delivery_sent' => 'Sent',
    'delivery_queued' => 'Sending…',
    'delivery_failed' => 'Failed',
    // the channel a guest wrote on, e.g. "via Booking.com"
    'via_channel' => 'via :channel',
    // the linked reservation's stay, e.g. "3 Oct – 7 Oct"
    'reservation_dates' => ':check_in – :check_out',
    // a guest with neither first nor last name
    'guest_fallback' => 'Guest',
    // the day divider in a conversation (today is common.today)
    'yesterday' => 'Yesterday',
];
