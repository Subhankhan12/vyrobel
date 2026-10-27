<?php

// app/Support/Platform/PlatformSwitches.php — the platform kill switches: each one's name and what it controls.

return [
    'signup_label' => 'New sign-ups (all sources)',
    'signup_controls' => 'Whether anyone can create a new Vyrobel account and organisation — by email, Google or X.',
    'signup_google_label' => 'Sign-up with Google',
    'signup_google_controls' => 'Whether a new account can be created by signing in with Google.',
    'signup_twitter_label' => 'Sign-up with X',
    'signup_twitter_controls' => 'Whether a new account can be created by signing in with X.',
    'signin_google_label' => 'Sign-in with Google',
    'signin_google_controls' => 'Whether an existing account can sign in with Google.',
    'signin_twitter_label' => 'Sign-in with X',
    'signin_twitter_controls' => 'Whether an existing account can sign in with X.',
];
