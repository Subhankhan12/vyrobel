<?php

// app/Livewire/Owner/OwnerTeam.php — the labels the owner Team & access page builds in PHP.

return [
    // a pending invite; :when is a relative time, e.g. "in 6 days"
    'expires' => 'Expires :when',
    'expired' => 'Expired',
    // the account owner when their name is missing, and the actor of invites they sent
    'you' => 'You',
    'unknown_user' => 'Unknown user',
    // the "what each role can see" matrix rows
    'area_financials' => 'Earnings & statements',
    'area_actions' => 'Approvals (rates, expenses)',
    'area_documents' => 'Documents & tax',
    'area_calendar' => 'Calendar & bookings',
    'area_exports' => 'Exports (download PDFs)',
    'area_payout' => 'Payout account & settings',
    // recent activity
    'signed_in' => 'signed in',
    // :email is the invited address, :role the role's name
    'invited_as' => 'invited :email as :role',
    // a member's last-active label; :when is a relative time, e.g. "3 days ago"
    'invite_pending' => 'Invite pending',
    'access_removed' => 'Access removed',
    'never_signed_in' => 'Hasn’t signed in yet',
    'active_today' => 'Active today',
    'last_active' => 'Last active :when',
];
