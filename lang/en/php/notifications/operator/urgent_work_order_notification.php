<?php

// App\Notifications\Operator\UrgentWorkOrderNotification — the feed row (and its email) when an urgent work order opens.

return [
    'context_plumbing' => 'Plumbing · needs attention now',
    'context_electrical' => 'Electrical · needs attention now',
    'context_appliance' => 'Appliance · needs attention now',
    'context_structural' => 'Structural · needs attention now',
    'context_other' => 'Other · needs attention now',
    'title' => 'Urgent work order · :title',
];
