<?php

// resources/views/livewire/reservation-detail.blade.php — the operator Reservation detail (/app/reservations/{id}).

return [
    // the terminal (cancelled / no-show) banner
    'reason' => 'Reason',
    'inventory_released' => 'Inventory released',
    'soon' => 'Soon',
    'no_fee_calculated' => 'No fee was calculated or charged — cancellation fees need a policy engine that isn’t built.',
    // the stay summary card
    'guest' => 'Guest',
    'view_profile' => 'View profile ›',
    'source' => 'Source',
    'check_in_out' => 'Check-in → out',
    'nights_guests' => 'Nights · guests',
    // :nights and :guests are the two counted phrases below, already worded
    'nights_and_guests' => ':nights · :guests',
    'nights_count' => '{1} :count night|[0,*] :count nights',
    'guests_count' => '{1} :count guest|[0,*] :count guests',
    // the units card
    'units' => 'Units',
    // a unit named by its code (the units card and the move picker)
    'unit_code' => 'Unit :code',
    'unassigned' => 'Unassigned',
    // the activity timeline
    'activity' => 'Activity',
    // the folio
    'folio' => 'Folio',
    // :account is the company account's name
    'billed_to' => 'Billed to <strong>:account</strong> — the balance is still owed, by them rather than the guest.',
    'unbill_confirm' => 'Return this folio to the guest? The balance goes back to them — nothing is refunded or charged.',
    'unbill_button' => 'Return to the guest’s folio',
    'no_folio' => 'No folio opened yet — record a payment or post a charge to open one.',
    'refund_button' => 'Refund',
    'no_folio_lines' => 'No folio lines yet.',
    'charges' => 'Charges',
    'paid' => 'Paid',
    'balance_due' => 'Balance due',
    'reconciled' => 'Reconciled to the folio ledger',
    // under the balance due; :amount is the folio's total charges
    'of_total' => 'of :amount total',
    'due' => 'Due',
    'settled' => 'Settled',
    // the lifecycle card
    'lifecycle' => 'Lifecycle',
    'confirm_hold_confirm' => 'Confirm this held booking? The nights are already held — this turns the hold into a confirmed stay.',
    'confirm_hold_button' => 'Confirm hold',
    'confirm_hold_note' => 'The nights stay held throughout — confirming changes what the booking means, not what it occupies.',
    'no_show_confirm' => 'Mark this reservation as a no-show? The held nights are released back to the calendar.',
    'no_show_button' => 'Mark no-show',
    'no_show_note' => 'Frees the held nights. No reason is recorded — the service stores none for a no-show.',
    'cancel_confirm' => 'Cancel this reservation? The held nights are released back to the calendar. This cannot be undone.',
    'cancel_reason_label' => 'Cancel · reason (optional)',
    'cancel_reason_placeholder' => 'Why is it being cancelled?',
    // the button that cancels the reservation (not a dismiss)
    'cancel_button' => 'Cancel',
    'cancel_note' => 'Cancelling is final — there is no reinstate, and the freed nights can be resold immediately.',
    'reverse_check_in' => 'Reverse check-in',
    'reverse_check_in_note' => 'Checked in by mistake? This puts the stay back to confirmed, where it can be cancelled. You will see what it does not undo before it happens.',
    'reverse_check_out' => 'Reverse check-out',
    'reverse_check_out_note' => 'Checked out by mistake? This puts the stay back to checked in. Some things cannot be undone — the next page lists them.',
    // notes & requests
    'notes_requests' => 'Notes & requests',
    'notes_captured' => 'Captured when the booking was made or at online check-in.',
    // cancellation policy
    'cancellation_policy' => 'Cancellation policy',
    'policy_note' => 'The policy on file for this property. Nothing has been calculated against this booking.',
    // record a payment
    'record_payment' => 'Record a payment',
    'method' => 'Method',
    'method_cash' => 'Cash',
    'method_bank_transfer' => 'Bank transfer',
    'method_card' => 'Card · via Stripe (test-mode)',
    // :currency is the reservation's currency code
    'amount_currency' => 'Amount (:currency)',
    'record_button' => 'Record',
    'recording' => 'Recording…',
    'payment_note' => 'Cash & bank transfer record now (offline / manual). Card is charged via Stripe (test-mode) — available at the live-key flip.',
    // more actions
    'more_actions' => 'More actions',
    // :code is the unit's code
    'move_unit' => 'Move unit :code',
    // :room_type is the room type's name (the line has no unit yet)
    'move_room_type' => 'Move :room_type',
    'move_to_unit' => 'Move to unit',
    'move_room_button' => 'Move room',
    'moving' => 'Moving…',
    'first_night_new_unit' => 'First night in the new unit',
    'first_night_help' => 'Nights before this date stay with the old unit; this night and after move with the guest.',
    'no_free_units_title' => 'No free same-type units for these dates',
    'no_free_units' => 'No free units',
    'add_charge' => 'Add a charge',
    'charge_type' => 'Charge type',
    'description' => 'Description',
    'adding' => 'Adding…',
    'void' => 'Void',
    'modify_dates_guests' => 'Modify dates · guests',
    'partial_refund_title' => 'Refund a settled payment in full or in part',
    'partial_refund' => 'Partial / custom refund',
    'export_pdf_title' => 'Download the folio as a PDF',
    'export_pdf' => 'Export folio (PDF)',
    'export_csv_print' => 'Export CSV · print',
    'room_moves_note' => 'Room moves route through the guarded AssignmentService (locked, no overbooking) and persist immediately.',
];
