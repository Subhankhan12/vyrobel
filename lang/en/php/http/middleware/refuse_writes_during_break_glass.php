<?php

// app/Http/Middleware/RefuseWritesDuringBreakGlass.php — the refusal of a write inside a read-only support session.

return [
    'read_only' => 'This is a read-only Vyrobel support session — nothing can be changed.',
    'title' => 'Read-only support session',
    'read_only_page' => 'This is a read-only Vyrobel support session — nothing can be changed. End the session from the banner to leave it.',
];
