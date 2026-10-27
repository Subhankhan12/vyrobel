<?php

// resources/views/admin/customers/partials/lifecycle-confirm.blade.php — the confirmations a lifecycle form carries.

return [
    'your_password' => 'Your password',
    'acknowledge_guests_label' => 'I have seen the guests this affects',
    // :count is the guests in house; the server checks only acknowledge_guests=1, never this text
    'acknowledge_guests' => '{1} I have seen that this hotel has :count guest stay in house and :upcoming upcoming. Their reservations stay exactly as they are.|[0,*] I have seen that this hotel has :count guest stays in house and :upcoming upcoming. Their reservations stay exactly as they are.',
    // :name is the organisation's own name (data) — the person types it exactly; the server compares against the stored name
    'protected_type_name' => 'Protected — type <strong class="ad-confirm-name">:name</strong> to confirm',
    'confirm_name_label' => 'Type the organisation name to confirm',
];
