<?php

// resources/views/livewire/operator/partials/guest-erase-ledger.blade.php — what erasing a guest removes and what it keeps.

return [
    'erased_heading' => 'Erased',
    'erased_name_contact' => 'Name and contact details',
    'erased_city_preferences' => 'City, nationality and preferences',
    'erased_documents_messages' => 'ID documents, notes and messages',
    // team chat is staff talking ABOUT a guest — only what a search can be sure of is removed (the full name, the email, the phone number)
    'erased_team_chat' => 'Their name, email and phone number where team chat mentions them',
    'kept_heading' => 'Kept, without a name',
    'kept_bookings' => 'Bookings and stays',
    'kept_invoices' => 'Invoices, payments and balances',
];
