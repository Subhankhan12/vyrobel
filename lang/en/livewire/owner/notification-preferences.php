<?php

// resources/views/livewire/owner/notification-preferences.blade.php — the owner's notification preferences (email/SMS grid, quiet hours).

return [
    'breadcrumb_settings' => 'Settings',
    'title' => 'Notifications',
    'subtitle' => 'Choose what we tell you and where. Statements are always kept in your portal regardless.',
    'where_we_reach_you' => 'Where we reach you',
    // the email channel (a card label and a column header)
    'channel_email' => 'Email',
    'email_channel_aria' => 'Email channel',
    'sms_not_set_up_tooltip' => 'SMS delivery isn\'t set up yet',
    // the SMS channel name, and the small badge beside it
    'channel_sms' => 'SMS',
    'not_set_up_badge' => '· not set up',
    'no_phone' => 'No number on file yet',
    'sms_inactive_note' => 'SMS delivery isn’t active yet — we’ll use these preferences once it’s set up.',
    'what_you_hear_about' => 'What you hear about',
    'always_on' => 'Always on',
    // the email toggle for one event, read by screen readers (:event is the event's name)
    'email_cell_aria' => ':event · Email',
    'sms_cell_tooltip' => 'Not active until SMS is set up',
    'sms_cell_sr' => ':event — SMS not set up',
    'guarantee' => 'We never send marketing without asking, and urgent account or payout alerts always come through even if a channel is off.',
    'quiet_hours' => 'Quiet hours',
    'quiet_hours_note' => 'Hold routine emails during these hours — you’ll still see everything in-app, and urgent alerts always come through.',
    // the two time fields of quiet hours: "From [22:00] to [07:00]"
    'quiet_from' => 'From',
    'quiet_to' => 'to',
    'unsaved_changes' => 'Unsaved changes',
    'all_saved' => 'All preferences saved',
    'toast_title' => 'Preferences saved',
    'toast_body' => 'Your notification preferences are live',
    'dismiss' => 'Dismiss',
];
