<?php

// resources/views/emails/winback.blade.php — the guest winback / re-engagement email.

return [
    'page_title' => 'We’d love to welcome you back',
    'preheader' => 'It’s been a while — we’d love to welcome you back.',
    'eyebrow' => 'We’ve missed you',
    'heading' => 'We’d love to welcome you back',
    // :place is " at <property>" or empty, built by WinbackMail (php/mail/winback_mail.at_property)
    'body' => 'Hi :name, it’s been a while since your last stay:place. Whenever you’re planning your next trip, we’d be delighted to host you again.',
    'reply_any_time' => 'Reply any time and we’ll help you find the perfect dates.',
    'unsubscribe_note' => 'You\'re receiving this because you opted in to hear from us. <a href=":url" style="color:#6C645F; text-decoration:underline;">Unsubscribe</a> at any time — you\'ll still get emails about your bookings.',
];
