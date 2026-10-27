<?php

// app/Support/Security/SessionTimeout.php — the session timeout choices, in words.

return [
    'hours' => '{1} :count hour|[0,*] :count hours',
    'minutes' => '{1} :count minute|[0,*] :count minutes',
    // :default is the store's own lifetime in words ("2 hours")
    'off_default' => 'Off — :default (the default)',
];
