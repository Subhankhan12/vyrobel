<?php

// app/Services/Platform/OrganizationDeletion.php — why an organisation cannot be deleted, in plain words.

return [
    // refusals, in the order they are checked
    'live_money_records' => 'It has live money records (:records). The law may require them to be kept for years, so deleting them is on hold until the legal ruling on how long — it cannot be deleted before then.',
    'uncounted_cascades' => 'The database links records in a way the deletion cannot count exactly (:links), so the confirmation could not say what goes. Nothing can be deleted until that is fixed.',
    'stripe_billing_live' => 'Stripe billing is live for this organisation. Cancel the subscription in Stripe first, or it keeps charging a customer that no longer exists. If it is already cancelled there, Stripe’s report of it has not been applied here — resolve it by hand.',
    'checkout_open_subscription' => 'A Stripe payment page for its subscription was opened :opened and can still be paid until :closes. Try again once it has been paid or has expired.',
    'checkout_open_card' => 'A Stripe payment page for a guest’s card payment was opened :opened and can still be paid until :closes. Try again once it has been paid or has expired.',
    'checkout_unreported_subscription' => 'A live Stripe payment page for its subscription was opened :opened and closed :closed, but no report of whether it was paid has been applied here. Check the page in Stripe’s dashboard; if it was paid, cancel what it started there first. Stripe retries a report it could not deliver for up to 3 days — try again once it is applied. If Stripe already reported it and the report could not be applied, resolve it by hand.',
    'checkout_unreported_card' => 'A live Stripe payment page for a guest’s card payment was opened :opened and closed :closed, but no report of whether it was paid has been applied here. Check the page in Stripe’s dashboard; if it was paid, cancel what it started there first. Stripe retries a report it could not deliver for up to 3 days — try again once it is applied. If Stripe already reported it and the report could not be applied, resolve it by hand.',
    'refund_processing' => 'A card refund is still being processed at Stripe. Wait until Stripe confirms it, then try again.',
    'card_payment_open' => 'A card payment or card hold is still open. Let it settle, or release it, first.',
    // D-HOLD-STUCK: a hold still `authorized` at Stripe. The hotel releases it from the reservation (the "Release hold" button, 2026-11-11) — the way out is named.
    'card_hold_open' => 'A card hold on a guest’s card is still open at Stripe. It has to be closed before this deletion can go ahead: the hotel can release it from the reservation’s payments (“Release hold”), or it can be captured.',
    'dispute_open' => 'A card payment is being disputed at Stripe (since :since). Answer it in Stripe, and try again once Stripe reports the dispute closed. If Stripe’s dashboard already shows it closed, the report never arrived — resolve it by hand.',
    'payouts_in_transit' => '{1} A payout to an owner is in transit at Stripe (since :since).|[0,*] :count payouts to owners are in transit at Stripe (since :since).',
    'payout_transit_stale' => 'That is longer than it should take — resolve it with Stripe by hand first.',
    'payout_transit_wait' => 'Wait until Stripe confirms, then try again.',
    'payouts_owed' => '{1} A payout is still owed to an owner (waiting or on hold). Pay it first — deleting would leave the owners unpaid, with no record of it.|[0,*] :count payouts are still owed to owners (waiting or on hold). Pay them first — deleting would leave the owners unpaid, with no record of it.',
    'payout_run_running' => 'A payout run has been running since :since.',
    'looks_stuck' => 'It looks stuck — resolve it by hand before deleting.',
    'payout_run_wait' => 'Try again when it has finished.',
    'webhook_unprocessed' => 'A :source notification for this organisation, received :received, has not been processed yet.',
    'webhook_wait' => 'Try again in a few minutes.',
    'cross_tenant_references' => 'Another organisation has records that point into this one (:links). Deleting would delete them too.',
    // refusals the deletion itself raises
    'schema_unreadable' => 'The database schema could not be read in full (:tables not found).',
    'already_deleted' => 'This organisation no longer exists — it has already been deleted.',
    'protected' => 'This organisation is protected and can never be deleted.',
    'not_suspended' => 'Suspend it first — only a suspended or archived organisation can be deleted.',
    'record_survived' => 'A record of this organisation survived the deletion (in :tables), so everything was rolled back.',
    'organization_survived' => 'The organisation or one of its people survived the deletion, so everything was rolled back.',
    // the live money records that hold a deletion
    'live_card_payments' => '{1} :count card payment|[0,*] :count card payments',
    'live_refunds' => '{1} :count refund|[0,*] :count refunds',
    'live_payouts' => '{1} :count payout|[0,*] :count payouts',
    'live_stripe_billing' => 'live Stripe billing, with its invoices',
    // a moment in a refusal
    'unknown_time' => 'an unknown time',
    'time_utc' => ':time UTC',
];
