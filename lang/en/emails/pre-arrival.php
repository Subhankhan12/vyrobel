<?php

// resources/views/emails/pre-arrival.blade.php — the guest pre-arrival email (heading/intro/button come from the template).

return [
    'page_title' => 'Your stay is ready',
    'preheader' => 'Your self check-in for :unit — door code, directions and wifi inside.',
    'eyebrow' => 'You’re nearly here',
    'door_code_label' => 'Your door code',
    'works_from' => 'Works from :date · same code all stay',
    'access_later' => 'Your host will share your access details before you arrive.',
    'open_guide' => 'Open directions & check-in guide ›',
    'getting_there' => 'Getting there',
    'open_in_maps' => 'Open in Maps',
    'wifi' => 'WiFi',
    'wifi_network_and_password' => 'Network <strong>:ssid</strong> · password <strong>:password</strong>',
    'wifi_network' => 'Network <strong>:ssid</strong>',
    'wifi_password_only' => '· password <strong>:password</strong>',
    'check_in_out' => 'Check-in & out',
    // one sentence per case: a time is printed only when the property published it
    'times_in_out' => 'In from :in_time :in_date · out by :out_time :out_date.',
    'times_in_only' => 'In from :in_time :in_date · out on :out_date.',
    'times_out_only' => 'In on :in_date · out by :out_time :out_date.',
    'times_none' => 'In on :in_date · out on :out_date.',
    'times_unconfirmed' => 'The property will confirm your check-in and check-out times.',
    'arriving_early' => 'Arriving early? <a href=":url" style="color:#C2410C; text-decoration:underline;">Message us</a>.',
    'balance_due_line' => '<strong style="color:#2B2320;">:balance</strong> balance due at check-in — pay now to skip the desk.',
    'message_us_line' => 'Anything you need before you arrive? Just <a href=":url" style="color:#C2410C; text-decoration:underline;">message us</a> — we’re happy to help.',
    'keep_safe' => 'Keep this email safe — your door code is inside. Reservation <strong style="color:#2B2320;">:reference</strong>.',
    'view_booking' => 'View your booking',
    'online_checkin' => 'Online check-in',
];
