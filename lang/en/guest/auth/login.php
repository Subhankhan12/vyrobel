<?php

// resources/views/guest/auth/login.blade.php — the guest portal's sign-in page.

return [
    'page_title' => 'Sign in',
    // shown when the guest arrived from a link in a booking email
    'booking_heading' => 'Access your booking',
    'booking_subheading' => 'Sign in below. If you booked without creating an account, set up access first — you’ll need the email address you booked with.',
    'booking_claim_button' => 'Set up access to your booking',
    // shown otherwise
    'heading' => 'Welcome back',
    'subheading' => 'Sign in to manage your stays.',
    'forgot_password' => 'Forgot password?',
    // the small show/hide toggle inside the password field
    'show' => 'Show',
    'hide' => 'Hide',
    'sign_in' => 'Sign in',
    // the link stays inside the sentence so each language places it where its grammar needs it
    'new_here' => 'New here? <a class="vy-link" href=":url">Access your bookings</a>',
];
