<?php

// app/Http/Controllers/Owner/OwnerSecurityController.php — owner security writes (flash messages).

return [
    'signed_out_others' => '{1} Signed out of :count other session.|[0,*] Signed out of :count other sessions.',
    'no_other_sessions' => 'You have no other active sessions.',
    'account_paused' => 'Your account is paused — you’re signed out and won’t be able to sign in until it’s reactivated. Your statements and payout history are kept. Contact your management team to reactivate.',
    'device_removed' => 'That device was removed — it’ll need a 2FA code next time.',
];
