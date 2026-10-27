<?php

// app/Mail/OwnerInviteMail.php — the invite email: the role-aware copy the Mailable builds for the shared owner-invite view.

return [
    'subject' => 'You’re invited to Vyrobel',
    // the footer lines when the role's copy sets none
    'footer_note' => 'Sent automatically when an operator invites someone to their organization.',
    'ignore_note' => 'If you didn’t expect this, you can safely ignore this email — no account is created until you set a password.',

    // founder — a workspace set up by Vyrobel in the platform console (:org is the organisation's name)
    // the account label is printed inside the view's sentence "activate your Vyrobel …"
    'founder_account' => 'account',
    'founder_eyebrow' => 'Your new workspace',
    'founder_headline' => ':org is ready on Vyrobel',
    'founder_intro' => 'A Vyrobel workspace has been set up for :org, with you as its founder. Set your password to sign in — using this link also confirms your email address.',
    'founder_section_title' => 'Your first steps',
    'founder_rooms_title' => 'Rooms & units',
    'founder_rooms_desc' => 'Add a room type and a unit to your first property',
    'founder_rates_title' => 'Rates',
    'founder_rates_desc' => 'Create a rate plan with prices',
    'founder_bookings_title' => 'Bookings',
    'founder_bookings_desc' => 'Take bookings on your storefront and at the front desk',
    // printed after the sender's name in the view's "You’re receiving this because …"
    'founder_role_reason' => 'set up a workspace for :org with you as its founder.',
    'founder_footer_note' => 'Sent when Vyrobel creates a workspace for someone.',
    'founder_ignore_note' => 'If you didn’t expect this, you can ignore this email — a workspace was set up for this address, but nobody can sign in to it until a password is set with this link.',

    // manager — a Hotel Manager invited to the operator app
    'manager_account' => 'Hotel Manager account',
    'manager_eyebrow' => 'Property manager invitation',
    'manager_headline' => 'Your Hotel Manager access on :org',
    'manager_intro' => ':org has invited you to join their team on Vyrobel as a property manager — the operator app for day-to-day operations. Set your password to get started.',
    'manager_section_title' => 'In the operator app',
    'manager_calendar_title' => 'Calendar & front desk',
    'manager_calendar_desc' => 'Arrivals, departures and the booking calendar',
    'manager_housekeeping_title' => 'Housekeeping & maintenance',
    'manager_housekeeping_desc' => 'Turnovers, tasks and work orders',
    'manager_reservations_title' => 'Reservations & guests',
    'manager_reservations_desc' => 'Bookings, folios and guest messaging',
    'manager_role_reason' => 'invited you as a property manager.',

    // accountant
    'accountant_account' => 'Accountant account',
    'accountant_eyebrow' => 'Accountant access invitation',
    'accountant_headline' => 'Your Accountant access on :org',
    'accountant_intro' => ':org has given you accountant access to their properties on Vyrobel — statements, tax documents and payout records, all ready to export. Set your password to get started.',
    'accountant_section_title' => 'What you can access',
    'accountant_statements_title' => 'Statements & ledgers',
    'accountant_statements_desc' => 'Every booking, fee and payout — itemised and exportable',
    'accountant_tax_title' => 'Tax documents',
    'accountant_tax_desc' => 'VAT invoices, annual summaries and withholding',
    'accountant_payouts_title' => 'Payout reconciliation',
    'accountant_payouts_desc' => 'Match payouts to statements across every unit',
    'accountant_role_reason' => 'invited you as an accountant.',

    // owner (and any other role)
    'owner_account' => 'owner account',
    'owner_eyebrow' => 'Owner portal invitation',
    'owner_headline' => 'Your owner portal on :org',
    'owner_intro' => ':org has invited you to your owner portal, where you can see your statements, payouts and unit performance — all in one place. Set your password to get started.',
    'owner_section_title' => 'In your portal',
    'owner_statements_title' => 'Monthly statements',
    'owner_statements_desc' => 'Every booking, fee and payout, itemised',
    'owner_payouts_title' => 'Payouts & schedule',
    'owner_payouts_desc' => 'Track what’s on the way and when it lands',
    'owner_performance_title' => 'Unit performance',
    'owner_performance_desc' => 'Occupancy, rates and reviews per unit',
    'owner_role_reason' => 'invited you as a property owner.',
];
