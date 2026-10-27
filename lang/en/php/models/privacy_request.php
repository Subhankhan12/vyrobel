<?php

// app/Models/PrivacyRequest.php — a privacy request's kind, subject, channel and status, keyed by their codes (read by the admin console).

return [
    'kind_export' => 'Export my data',
    'kind_delete' => 'Delete my data',
    'subject_account_holder' => 'A Vyrobel account holder',
    'subject_relayed_guest' => 'A hotel’s guest (relayed — the hotel is the controller)',
    'channel_email' => 'Email',
    'channel_letter' => 'Letter',
    'channel_phone' => 'Phone',
    'channel_hotel' => 'Relayed by the hotel',
    'status_received' => 'Received',
    'status_in_progress' => 'In progress',
    'status_completed' => 'Completed',
    'status_refused' => 'Refused',
];
