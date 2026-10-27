<?php

// App\Notifications\Owner\AccountSecurityNotification — the always-on "payout details changed" alert.

return [
    'context_added' => 'A new payout bank account was added. If this wasn’t you, contact us.',
    'context_default_changed' => 'Your default payout account was changed. If this wasn’t you, contact us.',
    'context_removed' => 'A payout bank account was removed. If this wasn’t you, contact us.',
    'context_updated' => 'Your payout details were updated. If this wasn’t you, contact us.',
    'title' => 'Payout details changed',
];
