<?php

// app/Http/Controllers/Auth/AccountSecurityController.php — the notices the Security page shows after a change (both realms).

return [
    'login_approval_unavailable' => 'Login approval can’t be turned on yet: email isn’t being sent from Vyrobel, so the approval link would never reach you and a new device couldn’t sign in.',
    'login_approval_on' => 'Login approval is on — a new device waits until you approve it.',
    'login_approval_off' => 'Login approval is off.',
    'approval_no_longer_waiting' => 'That sign-in is no longer waiting — it was already decided, or it expired.',
    'approval_approved' => 'Approved — that device can finish signing in now.',
    'approval_denied' => 'Denied — that sign-in can’t continue. If it wasn’t you, change your password.',
    'login_alerts_on' => 'Login alerts are on — a sign-in from a new device appears in your bell.',
    'login_alerts_off' => 'Login alerts are off.',
    // :duration is SessionTimeout's own label (e.g. "2 hours")
    'session_timeout_off' => 'Session timeout is off — you stay signed in for :duration without activity, as before.',
    'session_timeout_on' => 'You’ll be signed out after :duration without activity.',
];
