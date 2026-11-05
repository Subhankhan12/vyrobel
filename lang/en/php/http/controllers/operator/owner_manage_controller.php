<?php

// app/Http/Controllers/Operator/OwnerManageController.php — the operator's owner writes (commission, payouts pause, close a period, notes, remove).

return [
    'commission_required' => 'Enter the commission, in percent.',
    'commission_range' => 'The commission must be a number between 0 and 100.',
    'commission_unchanged' => 'Nothing changed — :rate is already :name’s commission from :date.',
    'commission_from_today' => ':name’s commission is :rate from today — they had no agreement in effect.',
    'commission_waiting_cancelled' => ':name stays on :rate — the change waiting for :date won’t happen.',
    'commission_from_date' => ':name’s commission is :rate from :date — for bookings worked out from now on. Bookings already worked out keep their rate.',
    'payouts_paused' => 'Payouts paused for :name — no payout goes out to them until you resume.',
    'payouts_resumed' => 'Payouts resumed for :name.',
    'period_start_required' => 'Choose the first day of the period.',
    'period_end_required' => 'Choose the last day of the period.',
    'period_end_after_start' => 'The period has to end on or after the day it starts.',
    'period_not_closed' => 'That period could not be closed: :reason Pick a period that starts after the last sealed one.',
    // the reason slotted into the line above (:reason)
    'period_sealed' => 'Cannot regenerate a finalized/locked statement.',
    'period_not_closed_other' => 'The statement for that period could not be built.',
    'period_closed' => 'Period closed — statement #:number finalised at :amount payable. Revenue not yet collected from guests stays unclaimed and will appear on a later statement.',
    'note_required' => 'Write the note first.',
    'note_max' => 'A note can be up to 2,000 characters.',
    'note_added' => 'Note added.',
    'remove_password_required' => 'Enter your password to remove this owner.',
    'password_wrong' => 'That password isn’t right — enter the one you sign in with.',
    'confirm_name_required' => 'Type the owner’s name to confirm.',
    'confirm_name_mismatch' => 'The name you typed doesn’t match — the owner was not removed.',
    'owner_removed' => ':name removed — units unassigned, all statements & payouts kept for accounting.',
    'workspace_paused_until' => 'Workspace paused — owner changes are read-only until :date.',
    'workspace_paused_for_now' => 'Workspace paused — owner changes are read-only for now.',
];
