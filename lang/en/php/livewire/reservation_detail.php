<?php

// app/Livewire/ReservationDetail.php — the operator Reservation detail's class (texts it builds for the view).

return [
    // the folio's billed-to line when the company account has no name
    'a_company_account' => 'a company account',
    // the unit line's status (reservation_units.status)
    'unit_status_active' => 'Active',
    'unit_status_cancelled' => 'Cancelled',
    'unit_status_moved' => 'Moved',
    // the add-charge type picker
    'charge_type_service' => 'Service',
    'charge_type_product' => 'Product',
    'charge_type_fee' => 'Fee',
    'charge_type_other' => 'Other',
    // the terminal (cancelled / no-show) banner
    'marked_no_show_at' => 'Marked no-show at',
    'cancelled_at' => 'Cancelled at',
    'released_no_show' => 'Every held night was released when this booking was marked a no-show — the unit is back on the calendar and can be resold.',
    'released_cancelled' => 'Every held night was released when this booking was cancelled — the unit is back on the calendar and can be resold.',
    'fee_no_show' => 'No-show fee · waiver',
    'fee_cancellation' => 'Cancellation fee',
    // the activity timeline: a status change; :from and :to are status names
    'transition' => ':from → :to',
    'system' => 'System',
    'a_team_member' => 'A team member',
    // the activity timeline: money actions, by audit action code
    'action_payment_recorded' => 'Payment recorded',
    'action_payment_settled' => 'Payment settled',
    'action_payment_refunded' => 'Refund issued',
    // notes & requests
    'note_from_guest' => 'From the guest',
    'note_internal' => 'Internal team note',
    // the cancellation policy summary; :count is the number of days
    'free_until_days' => '{1} free until :count day before arrival|[0,*] free until :count days before arrival',
    'free_until_arrival' => 'free until the arrival day',
    // folio ledger lines from payments (payments.type)
    'type_payment' => 'Payment',
    'type_capture' => 'Capture',
    'type_refund' => 'Refund',
    'type_deposit_hold' => 'Deposit hold',
    'type_void' => 'Void',
    'type_chargeback' => 'Chargeback',
    'received' => 'Received',
    // :method is how the money came in (Cash, Bank transfer, Card)
    'received_via' => 'Received · :method',
    'partly_refunded' => 'Partly refunded',
    'reverses_payment' => 'Reverses a payment',
    'pre_authorized' => 'Pre-authorized',
    'not_counted' => 'Not counted in the balance',
    // a ledger line's flag (payments.status, and a held deposit)
    'flag_hold' => 'Hold',
    'flag_pending' => 'Pending',
    'flag_succeeded' => 'Succeeded',
    'flag_failed' => 'Failed',
    'flag_refunded' => 'Refunded',
    'flag_authorized' => 'Authorized',
    'flag_captured' => 'Captured',
    'flag_voided' => 'Voided',
    // D-HOLD-STUCK (2026-11-11): a card hold that was released
    'hold_released_desc' => 'Released — the card was freed, nothing was charged',
    'flag_released' => 'Released',
];
