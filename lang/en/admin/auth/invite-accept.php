<?php

// resources/views/admin/auth/invite-accept.blade.php — a new platform admin accepting their invitation and choosing a password.

return [
    'title' => 'Accept invitation',
    'heading' => 'Set your password',
    // :role is the role's own label (already translated where it is built), or "an admin" below
    'invited_as' => 'You have been invited to the Vyrobel platform console as :role.',
    'an_admin' => 'an admin',
    'your_email_aria' => 'Your email',
    'set_password_button' => 'Set password',
    'next_step_note' => 'Next you sign in, and set up two-factor authentication before the console opens. That step cannot be skipped.',
];
