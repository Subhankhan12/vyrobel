<?php

// resources/views/admin/switches/index.blade.php — the platform admin console's kill switches.

return [
    'title' => 'Kill switches',
    'subtitle' => 'Stop new sign-ups, or sign-in through one provider, without a deploy. Every flip is audited with a reason.',
    'explainer' => 'Every switch is <strong>on</strong> unless someone turns it off here — including right after a deploy. None of them can affect this console. Turning off <strong>sign-in</strong> with a provider locks out anyone whose only way in is that provider, so those switches show the count and ask twice.',
    'state_on' => 'On',
    'state_off' => 'Off',
    // :time is the moment in UTC (Y-m-d H:i:s), :name the admin who changed it
    'last_changed_by' => 'Last changed :time UTC by :name.',
    'last_changed' => 'Last changed :time UTC.',
    'never_changed' => 'Never changed — on by default.',
    'no_account_relies' => 'No account relies on this provider alone right now — every one also has a password or another way in.',
    'would_lock_out' => '{1} Turning this off would lock out <strong>:count</strong> account — no password, and this provider is their only way in.|[0,*] Turning this off would lock out <strong>:count</strong> accounts — no password, and this provider is their only way in.',
    'reason' => 'Reason',
    'why_turn_off' => 'Why are you turning this off?',
    'why_turn_on' => 'Why are you turning this on?',
    'confirm_lockout_zero' => 'I understand anyone who signs in only this way from now until it is back on will be locked out.',
    'confirm_lockout' => '{1} I understand this locks out :count account until it is turned back on.|[0,*] I understand this locks out :count accounts until it is turned back on.',
    'turn_off' => 'Turn off',
    'turn_on' => 'Turn on',
];
