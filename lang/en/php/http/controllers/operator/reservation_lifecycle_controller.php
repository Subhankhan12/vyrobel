<?php

// app/Http/Controllers/Operator/ReservationLifecycleController.php — confirm a hold, cancel, mark a no-show.

return [
    // :status is the booking's status code as stored (e.g. confirmed, checked_in)
    'only_tentative_confirmable' => 'Only a tentative hold can be confirmed — this booking is :status.',
    'hold_confirmed' => 'Hold confirmed — the booking is now a confirmed stay.',
    'cannot_cancel_closed' => 'That reservation can’t be cancelled — it’s already closed.',
    'reason_too_long' => 'That cancellation reason is too long — 255 characters maximum.',
    'cancel_rejected' => 'That reservation couldn’t be cancelled — the booking service rejected it. Nothing was changed.',
    'cancelled_refunded' => 'Reservation cancelled + :amount refunded (within the free-cancellation window). The held nights are back on the calendar.',
    'cancelled' => 'Reservation cancelled. The held nights are back on the calendar.',
    'cannot_no_show' => 'That reservation can’t be marked a no-show — only a confirmed booking can be.',
    'no_show_rejected' => 'That reservation couldn’t be marked a no-show — the booking service rejected it. Nothing was changed.',
    'marked_no_show' => 'Marked as a no-show. The held nights are back on the calendar.',
];
