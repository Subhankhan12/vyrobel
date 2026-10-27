<?php

// app/Http/Controllers/Admin/SwitchController.php — the platform switches (sign-up and sign-in providers). :switch is the switch's name.

return [
    'confirm_no_lockout' => 'Tick the box to confirm — no account relies on this provider alone right now, but anyone who does by the time it is off will be locked out.',
    'confirm_lockout' => '{1} Confirm that you understand this locks out :count account whose only way in is this provider.|[0,*] Confirm that you understand this locks out :count accounts whose only way in is this provider.',
    'now_on' => ':switch is now ON. It takes effect on the next request.',
    'now_off' => ':switch is now OFF. It takes effect on the next request.',
];
