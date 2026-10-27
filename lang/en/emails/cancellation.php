<?php

// resources/views/emails/cancellation.blade.php — the guest cancellation email.

return [
    'title' => 'Your booking has been cancelled',
    'preheader' => 'Your booking :reference at :property has been cancelled.',
    'eyebrow' => 'Booking cancelled',
    'nights' => '{1} :count night|[0,*] :count nights',
    'reason_html' => 'Reason: <em style="color:#2B2320;">:reason</em>',
    'refund_issued' => 'Refund issued',
    'refund_to' => 'To :method · arrives in :eta',
    'no_refund' => 'No refund due',
    'no_refund_body' => 'This booking was non-refundable. If any payment is due back to you, we’ll confirm it separately.',
    'return_offer' => 'When plans firm up, we’d love to host you. Book direct with us next time for the best rate.',
    'help_html' => 'Something not right with your cancellation? Just <a href=":url" style="color:#C2410C; text-decoration:underline;">message us</a> — the :property team is here to help.',
];
