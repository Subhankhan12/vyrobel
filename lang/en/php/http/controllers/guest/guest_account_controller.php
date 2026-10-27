<?php

// app/Http/Controllers/Guest/GuestAccountController.php — the guest's account page (profile, preferences, password, deletion).

return [
    'room_fallback' => 'Room',
    'details_saved' => 'Your details have been saved.',
    'preferences_saved' => 'Your preferences have been saved.',
    'password_changed' => 'Your password has been changed.',
    // DELETE is the word the guest must type, exactly — keep it as it is in every language
    'type_delete_to_confirm' => 'Type DELETE to confirm.',
    'deletion_scheduled' => 'Account deletion scheduled for :date. You can undo any time before then.',
    'deletion_cancelled' => 'Your account deletion has been cancelled — welcome back.',
    'waitlist_date_range' => ':from → :to',
    'flexible_month' => 'Flexible · :month',
    'flexible_dates' => 'Flexible dates',
];
