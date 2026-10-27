<?php

// app/Support/Reviews/OwnerReviewSummary.php — the owner Reviews per-unit fallback name and trend chip.

return [
    'unit_fallback' => 'Unit',
    'trend_steady' => '— steady',
    // :delta is the rating change, one decimal (e.g. "0.3")
    'trend_up' => '▲ +:delta',
    'trend_down' => '▼ −:delta',
];
