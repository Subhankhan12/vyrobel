<?php

// app/Http/Controllers/Auth/SocialAuthController.php — "Continue with Google / X": sign in, sign up, connect, disconnect.
// :provider is the provider's brand name (Google, X).

return [
    'provider_paused' => ':provider sign-in is paused right now. Sign in with your password instead.',
    'state_invalid' => 'That sign-in link has expired or did not come from us. Please try again.',
    'cancelled' => 'Sign-in with Google was cancelled.',
    'exchange_failed' => 'We could not complete sign-in with Google. Please try again.',
    'signin_paused' => 'Sign-in with :provider is paused right now. Sign in with your password instead, or try again later.',
    'signup_paused' => 'New sign-ups with :provider are paused right now. Please try again later.',
    'unverified_sign_in' => ':provider has not verified that email address, so we cannot use it to sign in.',
    'email_taken' => 'An account already uses that email address. Sign in with your password, then connect :provider from your security page.',
    'sign_in_failed' => 'We could not complete sign-in with :provider.',
    'account_inactive' => 'That account cannot sign in at the moment.',
    'no_workspace' => 'This account does not have access to any workspace yet.',
    'sign_in_first' => 'Please sign in before connecting a :provider account.',
    'already_connected' => 'That :provider account is already connected.',
    'unverified_link' => ':provider has not verified that email address, so we cannot connect it.',
    'identity_taken' => 'That :provider account is already connected to a different Vyrobel account.',
    'link_failed' => 'We could not connect that :provider account.',
    'connected' => ':provider connected. You can now sign in with it.',
    'connection_gone' => 'That connection no longer exists.',
    'disconnected' => ':provider disconnected.',
];
