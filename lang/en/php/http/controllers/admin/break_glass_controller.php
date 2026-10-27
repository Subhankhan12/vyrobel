<?php

// app/Http/Controllers/Admin/BreakGlassController.php — opening and ending a support session inside an organisation.

return [
    'too_many_codes' => 'Too many codes tried. Wait a minute and try again.',
    'code_invalid' => 'That code is not valid. Enter the current code from your authenticator app.',
    'sign_out_org_first' => "Sign out of the organisation account in this browser first — a support session must never mix with a real user's session.",
    'already_open' => 'You already have a support session open. End it first.',
    'org_missing' => 'That organisation does not exist, or is archived.',
    'no_user_to_view_as' => 'This organisation has no active, verified back-office user to view as — so there is nothing to open. (Support can mark one verified first, audited.)',
    'session_ended' => 'Support session ended. The organisation has been told in-app.',
    'no_session_open' => 'No support session was open.',
];
