<?php

// resources/views/guest/auth/claim.blade.php — the guest's booking-tied sign-up (enter the booking email).

return [
    'page_title' => 'Access your bookings',
    'heading' => 'Access your bookings',
    'subheading' => 'Enter the email from your booking and we’ll send you a secure link to set up your account.',
    'booking_email' => 'Booking email',
    'send_link' => 'Send me a link',
    // the link stays inside the sentence so each language places it where its grammar needs it
    'already_set_up' => 'Already set up? <a class="vy-link" href=":url">Sign in</a>',
];
