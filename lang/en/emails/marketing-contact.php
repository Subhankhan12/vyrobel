<?php

// resources/views/emails/marketing-contact.blade.php — the internal contact-form notification.

return [
    'eyebrow' => 'Contact form',
    'reply_to' => 'Reply to',
    'units' => 'Units',
    'topic' => 'Topic',
    'received' => 'Received',
    'received_at' => ':date UTC',
    'stored_as_html' => 'Stored as <code>:id</code> in <code>marketing_contact_messages</code>. This message is a notification — the row is the record, and it is safe whether or not this email arrived.',
];
