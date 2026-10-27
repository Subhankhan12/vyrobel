<?php

// resources/views/billing/feature-locked.blade.php — "not in your plan" when a plan does not grant a feature.

return [
    'page_title' => 'Not in your plan · Vyrobel',
    'feature_channels' => 'Channel manager',
    'feature_analytics' => 'Insights & reports',
    'feature_team' => 'Extra team members',
    'heading' => ':feature isn\'t in your plan',
    // :feature is the feature's name in lower case
    'body' => 'Your current plan doesn\'t include :feature. Upgrade your plan to unlock it — your data and settings stay exactly as they are.',
    'view_plans' => 'View plans & upgrade',
];
