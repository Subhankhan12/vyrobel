<?php

// resources/views/emails/operator-guest-message.blade.php — the operator's free-text message to a guest.

return [
    'title' => 'A message from your host',
    'preheader' => 'You have a new message from your host.',
    'eyebrow' => 'A message for you',
    'greeting' => 'Hi :name,',
    'reply_hint_property' => 'Just reply to this email and we’ll see it — :property.',
    'reply_hint' => 'Just reply to this email and we’ll see it.',
    'marketing_footer_html' => 'You\'re receiving this because you opted in to hear from us. <a href=":url" style="color:#6C645F; text-decoration:underline;">Unsubscribe</a> at any time — you\'ll still get emails about your bookings.',
];
