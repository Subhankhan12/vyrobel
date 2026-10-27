<?php

// resources/views/emails/booking-confirmation.blade.php — the guest booking confirmation email.

return [
    'title' => 'Your booking is confirmed',
    'preheader' => 'Your stay at :property is confirmed — :check_in to :check_out.',
    'eyebrow' => 'Booking confirmed',
    'check_in' => 'Check-in',
    'check_out' => 'Check-out',
    'nights_html' => '{1} <strong style="color:#2B2320;">:count</strong> night|[0,*] <strong style="color:#2B2320;">:count</strong> nights',
    'reference_html' => 'Ref <strong style="color:#2B2320; font-variant-numeric:tabular-nums;">:reference</strong>',
    'total' => 'Total',
    'paid_with_html' => 'Paid with your original payment method · <a href=":url" style="color:#C2410C; font-weight:500; text-decoration:none;">View receipt ›</a>',
    'arrival_button' => 'See arrival info & house guide',
    'before_you_arrive' => 'Before you arrive',
    'checkin_online_html' => '<strong>Check in online</strong> — two minutes now means keys straight away · <a href=":url" style="color:#C2410C; font-weight:500; text-decoration:none;">start check-in ›</a>',
    'door_code_html' => '<strong>Door code & directions</strong> — <span style="color:#5F5853;">we’ll email these 48h before check-in</span>',
    'questions_html' => 'Questions before you arrive? <a href=":url" style="color:#C2410C; font-weight:600; text-decoration:none;">Message us ›</a>',
    'signature' => 'The :property team',
    'free_cancel_until' => 'free cancellation until :date',
    'add_to_calendar' => 'Add to calendar:',
];
