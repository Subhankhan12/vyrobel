<?php

// app/Http/Controllers/Admin/PrivacyController.php — the privacy desk: record a data-subject request and move it on.

return [
    'no_account_holder' => 'No account holder has that email address. (A hotel guest’s request is relayed — choose that instead.)',
    'choose_organisation' => 'Choose the organisation the guest’s request is about.',
    // :date is the due date, e.g. 27 Nov 2026
    'request_recorded' => 'Request recorded — due by :date.',
    'already_closed' => 'That request is already closed.',
    'marked_in_progress' => 'Request marked as in progress.',
    'marked_completed' => 'Request marked as completed.',
    'marked_refused' => 'Request marked as refused.',
];
