<?php

// resources/views/admin/dashboard.blade.php — the admin console's Health board.

return [
    'title' => 'Health',
    'subtitle' => 'Is anything failing quietly? Every card is measured on this page load — grey means it could not look.',
    'count_red' => ':number problem',
    'count_amber' => ':number attention',
    'count_grey' => ':number could not measure',
    'count_green' => ':number ok',
];
