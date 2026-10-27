<?php

// resources/views/guest/saved.blade.php — the guest's Saved page: wishlist (saved rooms) and waitlist.

return [
    'page_title' => 'Saved',
    'heading' => 'Saved',
    'subheading' => 'Rooms you love, and waitlists for the dates you want.',
    'wishlist_heading' => 'Wishlist',
    'wishlist_empty' => 'Nothing saved yet — tap “Save” on a room while browsing to keep it here.',
    'waitlist_heading' => 'Waitlist',
    // a tag after the room name: "Sea-view double · expired"
    'expired' => 'expired',
    // tags under a waitlist entry, after its dates: "12–15 May · notify me of similar · keep until 30 Apr"
    'notify_similar_tag' => 'notify me of similar',
    'keep_until' => 'keep until :date',
    'reconfirm_button' => 'Re-confirm',
    'leave_button' => 'Leave',
    'waitlist_empty' => 'You’re not on any waitlist. Join one below for sold-out dates.',
    'join_heading' => 'Join the waitlist',
    'room_label' => 'Room to wait for',
    'choose_room' => 'Choose a room…',
    'flexibility_label' => 'Date flexibility',
    'mode_dates' => 'Exact dates',
    'mode_month' => 'Anytime in a month',
    'check_in_label' => 'Check-in date',
    'check_out_label' => 'Check-out date',
    'flexible_month_label' => 'Flexible month',
    'notify_similar_label' => 'Notify me about similar rooms too',
    'join_button' => 'Join waitlist',
    'join_note' => 'If a spot opens we’ll be in touch. Automatic alerts and auto-holding a room are coming soon.',
];
