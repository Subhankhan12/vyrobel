<?php

// app/Http/Controllers/Admin/CustomerController.php — the console's organisation directory: delete, suspend, reactivate,
// archive, restore, assign a plan, mark internal, edit details, create. :name is the organisation's name as stored.

return [
    // DEC-7 — extend a trial / grant or end a complimentary plan
    'trial_extended' => 'Trial extended — it now ends on :date.',
    'extend_not_on_trial' => 'Only an organisation on a free trial can have its trial extended.',
    'extend_is_complimentary' => 'This organisation is on a complimentary plan, which has its own end date — grant it again with a new date to change it.',
    'extend_over_ceiling' => 'A trial can run at most 90 days from today, and this would end on :date. Choose fewer days.',
    'complimentary_granted' => 'Complimentary plan granted — full access until :date, then the paywall.',
    'complimentary_billed' => 'This organisation is billed through Stripe. A complimentary plan is only for one that is not paying.',
    'complimentary_no_subscription' => 'This organisation has no subscription record to attach a complimentary plan to.',
    'complimentary_ended' => 'The complimentary plan has ended — the organisation now sees the paywall.',
    'complimentary_not_on' => 'This organisation is not on a complimentary plan.',
    'delete_refused_protected' => 'Refused — this organisation is protected and can never be deleted from the console.',
    'delete_type_name' => 'Type the organisation’s exact name to confirm the deletion.',
    'delete_suspend_first' => 'Suspend it first — only a suspended or archived organisation can be deleted.',
    // :reason is the deletion service's own sentence
    'nothing_deleted_because' => 'Nothing was deleted. :reason',
    'deleted_unconfirmed' => '“:name” was deleted, but the console could not confirm what was removed — see the error log.',
    'delete_rolled_back' => 'Nothing was deleted — the deletion failed part-way and was rolled back. The cause is in the error log.',
    'ledger_write_failed' => '⚠️ The result could not be written to the console ledger (the request was) — the details are in the error log.',
    // :records, :tables, :accounts and :files are the four phrases below (records_count … files_count)
    'deleted_summary' => 'Deleted “:name” permanently — :records in :tables, :accounts and :files removed.',
    'records_count' => '{1} :count record|[0,*] :count records',
    'tables_count' => '{1} :count table|[0,*] :count tables',
    'accounts_count' => '{1} :count account|[0,*] :count accounts',
    'files_count' => '{1} :count file|[0,*] :count files',
    'users_kept' => '{1} :count person who also belongs elsewhere kept their account.|[0,*] :count people who also belong elsewhere kept their accounts.',
    'files_missing' => '{1} :count file had already gone from storage.|[0,*] :count files had already gone from storage.',
    'files_shared' => '{1} :count file shared with another organisation stayed.|[0,*] :count files shared with another organisation stayed.',
    'files_failed' => '{1} ⚠️ :count file could not be removed — their paths are in the ledger and the error log.|[0,*] ⚠️ :count files could not be removed — their paths are in the ledger and the error log.',
    'stripe_still_holds' => '{1} ⚠️ Stripe still holds :count record for it — customers, subscriptions or connected accounts; their ids are in the ledger. Close them in Stripe.|[0,*] ⚠️ Stripe still holds :count records for it — customers, subscriptions or connected accounts; their ids are in the ledger. Close them in Stripe.',
    'deletion_record_kept' => 'The console’s own record of the deletion is kept.',
    'already_suspended' => 'This organisation is already suspended.',
    'suspended' => 'Suspended. Its staff and owners are refused and the storefront is closed. No reservation, folio or payment was touched.',
    'restore_first' => 'Restore it from the archive first.',
    'not_suspended' => 'This organisation is not suspended.',
    // :status is the organisation's status code, shown as stored (active, trialing, cancelled)
    'reactivated' => 'Reactivated — its staff and owners can sign in again, and the storefront follows its status (:status).',
    'already_archived' => 'This organisation is already archived.',
    'archive_suspend_first' => 'Suspend it first — only a suspended organisation can be archived.',
    'archived' => 'Archived — hidden from the directory, every record kept. It stays suspended; restore it to bring it back.',
    'not_archived' => 'This organisation is not archived.',
    'restored' => 'Restored from the archive. It is still suspended — reactivate it when access should return.',
    'already_on_plan' => 'This organisation is already on that plan — nothing was changed. Choose a different plan to assign.',
    'plan_assigned' => "Plan assigned. The customer's features update immediately; billing (Stripe) is a separate step.",
    'marked_internal' => 'Marked internal — excluded from platform statistics.',
    'marked_customer' => 'Marked as a customer — counted in platform statistics.',
    'refused_baseline' => 'Refused — this is the protected baseline organisation. No console action may change it.',
    'protected_type_name' => 'This organisation is protected. Type its exact name to confirm.',
    // :count = guest stays in house, :upcoming = stays still to arrive
    'acknowledge_guests' => '{1} This organisation has :count guest stay in house and :upcoming upcoming. Confirm you have seen that.|[0,*] This organisation has :count guest stays in house and :upcoming upcoming. Confirm you have seen that.',
    'nothing_changed' => 'Nothing changed — every value was already as entered.',
    // the organisation's name field, in the list of fields saved
    'field_name' => 'name',
    // :fields is a comma-separated list of the field names that were saved
    'details_saved' => 'Saved — read back from the database: :fields.',
    'vat_not_sent_to_stripe' => '⚠️ The new VAT number could not be sent to Stripe just now — it is saved here; save again to retry.',
    // :fields is a comma-separated list of column codes
    'write_not_landed' => 'The write did not land for: :fields.',
    'create_mail_discarded' => 'Mail is not being delivered (mail is “:mailer”), so the founder could never receive the set-password link. Nothing was created.',
    'create_address_undeliverable' => 'This address can never receive mail (:reason), so the founder could never receive the set-password link. Nothing was created.',
    'create_email_taken' => 'An account already uses this email address. Nothing was created.',
    'created_link_sent' => '{1} Created. The founder’s set-password link was handed to the mailer — it is single-use and expires in :count day.|[0,*] Created. The founder’s set-password link was handed to the mailer — it is single-use and expires in :count days.',
    'created_link_failed' => 'Created — but the set-password email could not be sent. Use Support → “Send password reset” for the founder once mail is working.',
];
