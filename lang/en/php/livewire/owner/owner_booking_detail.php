<?php

// App\Livewire\Owner\OwnerBookingDetail — the owner's one-booking page: check-in/out times, guest tag, header sub-line, channel, timeline and payout status.

return [
    'from_time' => 'from :time',
    'by_time' => 'by :time',
    'repeat_guest' => '{1} Repeat guest · :count stay|[0,*] Repeat guest · :count stays',
    'new_guest' => 'New guest',
    'guests_count' => '{1} :count guest|[0,*] :count guests',
    'nights_count' => '{1} :count night|[0,*] :count nights',
    // the distribution channel, derived from the booking source
    'channel_direct' => 'Vyrobel direct',
    'channel_walk_in' => 'Walk-in',
    'channel_ota' => 'OTA channel',
    // the booking timeline: event labels, then what is shown under them
    'event_booking_received' => 'Booking received',
    'event_cancelled' => 'Cancelled',
    'event_payment_received' => 'Payment received',
    'when_paid_in_full' => 'Paid in full',
    'event_part_payment_received' => 'Part-payment received',
    'when_balance_due' => 'Balance due',
    'event_payment' => 'Payment',
    'when_not_yet_received' => 'Not yet received',
    'event_checked_in' => 'Checked in',
    'event_check_in' => 'Check-in',
    'event_checked_out' => 'Checked out',
    'event_payout_released' => 'Payout released',
    'event_payout' => 'Payout',
    'when_next_payout' => 'With your next payout',
    // the payout status pill and the note under it
    'payout_not_attributed' => 'Not yet attributed',
    'payout_not_attributed_note' => 'This booking has not been attributed to your ledger yet.',
    'payout_scheduled' => 'Scheduled',
    'payout_scheduled_note' => 'Included in your next statement, then released with that payout.',
    'payout_pending' => 'Payout pending',
    'payout_pending_note' => 'On a finalized statement — releases with that payout.',
    'payout_paid' => 'Paid',
    'payout_paid_note' => 'Released with a completed payout.',
];
