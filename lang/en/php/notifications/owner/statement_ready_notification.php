<?php

// App\Notifications\Owner\StatementReadyNotification — the owner's monthly statement was finalized.

return [
    'title' => 'Your statement is ready',
    'context' => ':period · net payable :amount',
];
