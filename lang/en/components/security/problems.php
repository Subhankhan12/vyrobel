<?php

// resources/views/components/security/problems.blade.php — "Sign-in blocked on your account".

return [
    'heading' => 'Sign-in blocked on your account',
    // :when is a <time> element ("3 hours ago"); :ip is an address, or ip_unknown
    'blocked' => 'Too many wrong passwords from :device (IP <span class="vy-sx-mono">:ip</span>) :when, so signing in was paused.',
    'ip_unknown' => 'unknown',
    'more_refused' => '{1} :count more attempt was refused in the last :days days.|[2,*] :count more attempts were refused in the last :days days.',
    'secure_now' => 'If this wasn’t you, secure your account now.',
    'secure_button' => 'Secure my account',
    'this_was_me' => 'This was me',
];
