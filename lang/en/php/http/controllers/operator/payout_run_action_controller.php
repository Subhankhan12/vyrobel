<?php

// app/Http/Controllers/Operator/PayoutRunActionController.php — the Payout Run's run, create and pay actions.

return [
    'engine_disabled' => 'The payout engine is disabled (PAYOUT_ENGINE_ENABLED). Enable it before running a payout.',
    'nothing_to_run' => 'Nothing to run — no owner here has a payable statement or a held payout:paused_note.',
    'run_complete' => 'Payout run complete (:mode mode) — :created created · :paid paid · :held held:paused_note.',
    // the run's mode as a word people read in the sentence above (test = no real money moves)
    'mode_test' => 'test',
    'mode_live' => 'live',
    'create_failed' => 'Couldn’t create the payout — :reason',
    'already_has_payout' => 'That statement already has a payout — nothing was created.',
    'one_payout_created' => '1 payout created.',
    'pay_failed' => 'Couldn’t pay — :reason',
    'one_payout_paid' => '1 payout marked paid.',
    'payout_failed_reason' => 'The payout failed — :reason',
    'payout_failed_no_reason' => 'The payout failed — the rail gave no reason.',
    'create_all_failed' => 'Couldn’t create payouts — :reason',
    'no_payout_created_paused' => 'No payout created:paused_note — resume them on the owner’s page.',
    'nothing_ready' => 'Nothing is ready to pay — no finalized statement has a positive net payable:paused_note.',
    'no_new_payouts' => 'No new payouts — every ready statement already has one:paused_note.',
    'payouts_created' => '{1} :count payout created:paused_note.|[0,*] :count payouts created:paused_note.',
    'some_not_created' => ':message Some could not be created — :reason',
    'nothing_paid_paused' => 'Nothing paid:paused_note — resume them on the owner’s page.',
    'no_pending_payouts' => 'No pending payouts to pay.',
    'paid_only' => '{1} :count payout marked paid:paused_note.|[0,*] :count payouts marked paid:paused_note.',
    'failed_only' => '[0,*] :count failed on the rail:paused_note.',
    'paid_and_failed' => '{1} :count payout marked paid · :failed failed on the rail:paused_note.|[0,*] :count payouts marked paid · :failed failed on the rail:paused_note.',
    'some_not_attempted' => ':message Some could not be attempted — :reason',
    'nothing_payable_negative' => 'Nothing payable — this statement’s net is negative, so the balance carries forward to offset the owner’s next statement.',
    'nothing_payable_zero' => 'Nothing payable — this statement’s net is zero, so nothing is owed.',
    'workspace_paused_until' => 'Workspace paused — payouts are read-only until :date.',
    'workspace_paused_for_now' => 'Workspace paused — payouts are read-only for now.',
    // a clause added to the end of the sentences above when paused owners were left out (empty otherwise)
    'paused_note' => ' · :skipped',
    'paused_owners_skipped' => '{1} :count paused owner skipped|[0,*] :count paused owners skipped',
];
