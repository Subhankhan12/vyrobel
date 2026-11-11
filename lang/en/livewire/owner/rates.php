<?php

// resources/views/livewire/owner/rates.blade.php — the owner Rates & pricing page (proposals, current rates, guardrails, direct edit).

return [
    'title' => 'Rates & pricing',
    'subtitle' => 'Review the pricing your manager proposes and set the limits they work within.',
    // proposed rate changes
    'proposed_title' => 'Proposed rate changes',
    'to_review' => '{1} :count to review|[0,*] :count to review',
    'proposed_intro' => 'Your manager suggests these based on demand. Approve, decline, or send a counter.',
    'effective' => 'Effective :date',
    'per_night' => 'per night',
    // the amount stays inside the sentence, in its own style
    'countered' => 'You countered with <strong class="vy-mono" style="color:var(--vy-accent)">:amount</strong> — awaiting your manager’s response.',
    'approve' => 'Approve',
    'decline' => 'Decline',
    'counter_button' => 'Counter…',
    'your_counter' => 'Your counter · :symbol / night',
    'counter_placeholder' => 'e.g. 450',
    // the "optional" hint stays inside the label, in its own lighter style
    'note_optional' => 'Note <span style="text-transform:none;letter-spacing:0;color:var(--vy-faint)">· optional</span>',
    'why_this_rate' => 'Why this rate',
    'send_counter' => 'Send counter',
    'proposed_empty' => 'No proposed changes right now — you’re all caught up.',
    // current rates
    'current_title' => 'Current rates',
    'current_sub' => 'Live nightly rates across your units',
    'no_room_types' => 'You don’t have any room types to price here.',
    'no_room_types_hint' => 'Rates are set per room type; where your unit shares a room type with another party, your management team sets the rate.',
    'col_villa' => 'Unit',
    'col_base' => 'Base',
    'col_weekend' => 'Weekend',
    'col_peak' => 'Peak',
    'col_occ' => 'Occ.',
    'no_rate_plan' => 'No rate plan',
    // a season that is defined but not yet priced into the calendar; :name is the season's own name
    'season_not_applied' => ':name · not applied yet',
    // pricing guardrails
    'guardrails_title' => 'Pricing guardrails',
    'guardrails_intro' => 'The limits your manager prices within.',
    'pricing_mode' => 'Pricing mode',
    'mode_dynamic' => 'Dynamic',
    'mode_fixed' => 'Fixed',
    // the slider's live value is an Alpine element inside the sentence — keep the markup exactly as is
    'auto_approve_within' => 'Auto-approve changes within <span class="vy-mono" style="color:var(--vy-accent)">±<span x-text="auto"></span>%</span>',
    'auto_approve_label' => 'Auto-approve rate changes within this percentage',
    // read aloud by a screen reader; :pct is the slider's number
    'auto_approve_valuetext' => 'plus or minus :pct percent',
    'auto_approve_none' => 'Every rate your operator proposes needs your sign-off before it goes live.',
    'auto_approve_some' => 'Proposed changes within ±:pct% go live automatically; anything larger waits for your approval.',
    'limits_scope' => 'These limits apply to rates your operator <strong>proposes</strong>. Your operator can still set a rate directly — you’ll see it here and on your calendar.',
    'floor_per_night' => 'Floor / night',
    'floor_min' => 'min',
    'ceiling_per_night' => 'Ceiling / night',
    'ceiling_max' => 'max',
    'save_guardrails' => 'Save guardrails',
    // where you sit
    'where_you_sit' => 'Where you sit',
    'average_rate' => 'Your average rate',
    'market_benchmarking' => 'Market benchmarking — how your rates compare to similar local properties — is coming once we connect a market-rate source. We won’t show a comparison until it’s real.',
    // adjust a rate directly
    'direct_title' => 'Adjust a rate directly',
    'direct_intro' => 'You can also set your own rate directly — it goes live immediately (no sign-off), for every unit in the room type.',
    'room_type' => 'Room type',
    'from' => 'From',
    'to' => 'To',
    'nightly_rate' => 'Nightly rate (:symbol)',
    'rate_placeholder' => 'e.g. 175',
    'weekend_uplift' => 'Weekend uplift (%)',
    'min_stay' => 'Min stay (nights)',
    'min_stay_placeholder' => 'blank',
    // :weekend and :weekday are the live prices (Alpine elements)
    'weekend_preview' => 'Fri & Sat nights become :weekend / night; weekdays stay :weekday.',
    'update_rate' => 'Update rate',
];
