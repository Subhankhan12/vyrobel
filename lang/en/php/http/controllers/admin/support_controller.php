<?php

// app/Http/Controllers/Admin/SupportController.php — the support desk: unlink a sign-in provider, mark an email verified,
// resend verification, send a password reset. :mailer is the mail driver's code (e.g. log); :provider is a brand name.

return [
    'connection_gone' => 'That connection no longer exists.',
    'unlink_refused_only_way_in' => 'Refused — this is the only way this person can sign in (no password, no other provider). Unlinking it would lock them out for good.',
    'unlinked' => ':provider unlinked — read back: the connection is gone.',
    'unlink_not_landed' => 'The unlink did not land — the connection is still there.',
    'already_verified' => 'Already verified.',
    // :stored is the stored timestamp, exactly as the database returns it
    'marked_verified' => 'Marked verified — read back from the database: :stored UTC.',
    'verify_not_landed' => 'The write did not land — email_verified_at is still empty.',
    'already_verified_nothing_to_resend' => 'Already verified — nothing to resend.',
    'verification_discarded' => 'NOT DELIVERED — mail is “:mailer”, so the verification email was discarded. Nobody received anything. Confirm who they are another way, then use “Mark email verified”.',
    'verification_handed' => 'Verification email handed to the mailer (:mailer).',
    // :status is the password broker's own message
    'no_reset_link' => 'No reset link was created: :status',
    'reset_discarded' => 'NOT DELIVERED — a reset link was created, but mail is “:mailer”, so the email was discarded. Nobody received anything.',
    'reset_handed' => 'Password reset email handed to the mailer (:mailer).',
    // :reason says why the address can never receive mail (e.g. reserved domain example.com)
    'verification_not_sent_undeliverable' => 'NOT SENT — this address can never receive mail (:reason), so the verification email was not handed to the mailer. Nobody received anything.',
    'reset_not_sent_undeliverable' => 'NOT SENT — this address can never receive mail (:reason), so the reset link was not handed to the mailer. Nobody received anything.',
];
