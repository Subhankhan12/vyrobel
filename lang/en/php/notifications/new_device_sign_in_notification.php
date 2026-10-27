<?php

// App\Notifications\NewDeviceSignInNotification — the login alert: a new device signed in to the account.

return [
    'title' => 'New sign-in from :device',
    'context_ip' => 'IP :ip · :when. If this wasn’t you, change your password and sign out other devices.',
    'context' => ':when. If this wasn’t you, change your password and sign out other devices.',
];
