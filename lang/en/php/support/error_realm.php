<?php

// app/Support/ErrorRealm.php — the error pages' back link, 403 body and "still stuck?" line, per realm.

return [
    'back_to_console' => 'Back to the console',
    'back_to_trips' => 'Back to your trips',
    'access_bookings' => 'Access your bookings',
    'back_to_portal' => 'Back to your portal',
    'back_to_vyrobel' => 'Back to Vyrobel',
    'back_to_dashboard' => 'Back to the dashboard',
    'back_to_safety' => 'Back to safety',
    'forbidden_guest' => 'This page belongs to a different booking, or it isn’t part of your account. If you reached it from a link we sent you, the link may have expired.',
    'forbidden_owner' => 'Your owner account doesn’t include this area. If you need it, ask the primary owner on your account to grant the capability.',
    'forbidden_not_owner' => 'The owner portal is for property owners, and your account isn’t linked to one. If that’s wrong, ask whoever manages your Vyrobel account to restore your access.',
    'forbidden_public' => 'This page isn’t available without signing in. If you followed a link we sent you, it may have expired or already been used — sign in and try again, or ask whoever sent it for a new one.',
    'forbidden_app' => 'Your role doesn’t include this area. If you need it, ask an administrator on your team to grant the permission.',
    'support_guest' => 'If this keeps happening, reply to your booking confirmation email and the property will help.',
    'support_owner' => 'If this keeps happening, contact the primary owner on your account, or your property manager.',
    'support_not_owner' => 'If this keeps happening, contact whoever set up your Vyrobel account.',
    'support_app' => 'If this keeps happening, contact your Vyrobel administrator.',
];
