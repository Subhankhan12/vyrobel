<?php

// resources/views/livewire/operator/reservation-refund.blade.php — the operator back office's refund composer.

return [
    'page_title' => 'Refund a guest',
    'back_to_reservation' => 'Back to reservation',
    'guest' => 'Guest',
    'stay' => 'Stay',
    'refund_a_payment' => 'Refund a payment',
    'only_settled' => 'Only settled payments can be refunded.',
    // the consequence callout above every refund button
    'cannot_reverse_title' => 'It can’t be reversed',
    'cannot_reverse_body' => 'A refund reverses a payment, not the charges — so the folio shows the guest owing again. Refunds cannot be reversed once issued.',
    // one payment's figures
    'original' => 'Original',
    'already_refunded' => 'Already refunded',
    'refund_in_progress' => 'Refund in progress',
    'still_refundable' => 'Still refundable',
    // the full-remaining refund: the browser confirm and the button; :amount is the remaining refundable money
    'refund_remaining_confirm' => 'Refund :amount — the whole remaining amount of this payment? A refund cannot be reversed.',
    'refunding' => 'Refunding…',
    'refund_remaining' => 'Refund the remaining :amount',
    // the partial refund form; :currency is the payment's currency code
    'partial_confirm' => 'Issue this partial refund? A refund cannot be reversed.',
    'refund_part' => 'Or refund part of it (:currency)',
    'reason' => 'Reason',
    'reason_note' => '· recorded on the folio & in reports',
    'choose_reason' => 'Choose a reason…',
    'reason_duplicate' => 'Duplicate charge',
    'reason_overcharge' => 'Overcharge',
    'reason_service_issue' => 'Service issue',
    'reason_cancellation' => 'Cancellation',
    'reason_goodwill' => 'Goodwill',
    'reason_other' => 'Other',
    'add_note' => 'Add a note (optional)',
    'refund_to' => 'Refund to',
    // :gateway is the payment's gateway name ("Stripe"); the span keeps its muted colour
    'original_method' => 'Original method · <span style="color: var(--vy-muted);">:gateway</span>',
    'manual_bank_transfer' => 'Manual bank transfer',
    'manual_bank_transfer_note' => '· recorded, no gateway',
    // who bears the refund (owner-attributed stays only)
    'whose_cost' => 'Whose cost is this?',
    'borne_owner' => 'The owner',
    'borne_owner_note' => '· deducted from their share of this booking',
    'borne_operator' => 'Ours',
    'borne_operator_note' => '· we absorb it; the owner is not charged',
    'review_issue' => 'Review & issue refund',
    'up_to_refundable' => 'Up to :amount is still refundable against this payment. Anything above that is refused, not trimmed to fit.',
    'fully_refunded' => 'This payment is fully refunded — there is nothing left to refund against it.',
    'no_settled_payment' => 'No settled payment on this folio yet. Only settled payments can be refunded.',
    // the right rail: the folio's totals
    'this_folio' => 'This folio',
    'charges' => 'Charges',
    'paid' => 'Paid',
    'balance_due' => 'Balance due',
    'reconciled' => 'Reconciled to the folio ledger',
    // the right rail: what a refund does
    'what_happens' => 'What happens',
    'reversing_entry_title' => 'A reversing entry is appended',
    'reversing_entry_body' => 'The refund is posted as its own new entry on the ledger. The original payment is never edited or removed.',
    'owner_statement_title' => 'The owner statement adjusts',
    'owner_statement_body' => 'For an owner-attributed stay, the owner\'s net share of the refund is posted to their ledger and lands on their next statement close.',
    'guest_emailed' => 'The guest is emailed a refund confirmation automatically when the refund succeeds. Card refunds run in test mode until go-live — no real money moves yet.',
    // the right rail: what is not built
    'not_yet_available' => 'Not yet available',
    'refund_by_charge' => 'Refund by individual charge',
    'soon' => 'Soon',
    'per_charge_note' => 'Refunds attach to a payment, not to individual charges — a per-charge credit note is a separate concept.',
];
