<?php

// resources/views/components/pager.blade.php — the realm-neutral pagination control.

return [
    // the default noun after the range ("1–25 of 80 results"); callers usually pass their own
    'results' => 'results',
    'pagination' => 'Pagination',
    'range' => ':first–:last of :total :label',
    'previous_page' => 'Previous page',
    'page_of' => 'Page :current of :last',
    'next_page' => 'Next page',
];
