<?php

// resources/views/livewire/operator/notification-preferences.blade.php — the operator's own notification preferences.

return [
    'page_title' => 'Notification preferences',
    'subtitle' => 'How Vyrobel reaches you',
    'breadcrumb_label' => 'Breadcrumb',
    'crumb_settings' => 'Settings',
    'crumb_notifications' => 'Notifications',
    // channel names: the three channel cards and the matrix's column headers
    'channel_email' => 'Email',
    'channel_push' => 'Push',
    'channel_sms' => 'SMS',
    'no_email_on_file' => 'No email on file',
    'email_verified_title' => 'Your login email is verified',
    'email_verified' => 'Verified',
    'push_not_set_up_title' => 'Push isn’t set up yet',
    'not_set_up_tag' => 'not set up',
    'push_not_available' => 'Browser & device push isn’t available yet',
    // a channel card's status pill
    'channel_off' => 'Off',
    'sms_not_set_up_title' => 'SMS isn’t set up yet',
    'sms_no_provider' => 'No SMS provider connected yet',
    'notify_me_when' => 'Notify me when',
    'always_on' => 'Always on',
    'email_always_on_sr' => 'Email always on for :event',
    'email_me_when_aria' => 'Email me when: :event',
    'push_not_set_up_sr' => 'Push not set up',
    'sms_not_set_up_sr' => 'SMS not set up',
    'quiet_hours' => 'Quiet hours',
    'quiet_hours_help' => 'Hold routine email between these hours — urgent security alerts always come through; the in-app feed is unaffected.',
    'quiet_from' => 'From',
    'quiet_to' => 'To',
    'digest_heading' => 'Daily digest email',
    // the tag stays inside the sentence so each language places it where its grammar needs it
    'digest_help' => 'A morning summary of the day. <span class="vy-np-soon">not dispatched yet</span> — the preference is saved for when the digest job ships.',
    // the digest's two options
    'digest_off' => 'Off',
    'digest_morning' => 'Morning · 08:00',
];
