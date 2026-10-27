<?php

// resources/views/admin/break-glass/index.blade.php — opening, ending and listing break-glass support sessions.

return [
    'title' => 'Break-glass',
    'subtitle' => 'A read-only, time-boxed look inside one organisation — reason, 2FA, and the organisation is told.',
    'back_label' => 'Support',
    'open_heading' => 'Your session is open',
    // the pre-conversion English printed "page" for 1 and "pages" otherwise
    'open_summary' => '{1} :organization · ends at :time UTC · :count page viewed.|[0,*] :organization · ends at :time UTC · :count pages viewed.',
    'go_to_back_office' => 'Go to their back office',
    'end_now' => 'End session now',
    'start_heading' => 'Open a session',
    'rule_read_only' => '<strong>Read-only.</strong> Every change is refused — forms, buttons, anything that saves.',
    // the pre-conversion English printed "minutes" for every number, 1 included
    'rule_minutes' => '{1} <strong>:count minutes</strong>, then it ends by itself.|[0,*] <strong>:count minutes</strong>, then it ends by itself.',
    'rule_senior_user' => 'You see what their most senior verified back-office user sees — guests included. Look only at what the reason needs.',
    'rule_recorded' => 'Every page you open is recorded, and their team is told afterwards: your name, the time, and this reason.',
    'organisation_label' => 'Organisation',
    'choose_option' => 'Choose…',
    'code_label' => 'Your 2FA code (now)',
    'reason_label' => 'Reason — they will read this',
    'reason_placeholder' => 'e.g. Ticket #123 — the owner reports a missing booking',
    'open_button' => 'Open read-only session',
    'recent_heading' => 'Recent sessions',
    'empty_state' => 'No break-glass session has ever been opened.',
    'col_admin' => 'Admin',
    'col_organisation' => 'Organisation',
    'col_reason' => 'Reason',
    'col_started' => 'Started',
    'col_ended' => 'Ended',
    'col_pages' => 'Pages',
    'col_told' => 'Told',
    'ended_open' => 'open',
    'ended_due' => 'due — not yet swept',
    'told_yes' => 'Yes',
    'told_not_yet' => 'Not yet',
];
