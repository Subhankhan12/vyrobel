<?php

// I18N-01 — nl translation of lang/en/php/http/controllers/operator/payout_run_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'engine_disabled' => 'De uitbetalingsengine is uitgeschakeld (PAYOUT_ENGINE_ENABLED). Schakel die in voordat u een uitbetaling uitvoert.',
    'nothing_to_run' => 'Niets uit te voeren — geen enkele eigenaar hier heeft een afrekening om uit te betalen of een ingehouden uitbetaling:paused_note.',
    'run_complete' => 'Uitbetalingsrun voltooid (modus: :mode) — aangemaakt: :created · betaald: :paid · ingehouden: :held:paused_note.',
    'create_failed' => 'De uitbetaling kon niet worden aangemaakt — :reason',
    'already_has_payout' => 'Die afrekening heeft al een uitbetaling — er is niets aangemaakt.',
    'one_payout_created' => '1 uitbetaling aangemaakt.',
    'pay_failed' => 'Betalen mislukt — :reason',
    'one_payout_paid' => '1 uitbetaling als betaald gemarkeerd.',
    'payout_failed_reason' => 'De uitbetaling is mislukt — :reason',
    'payout_failed_no_reason' => 'De uitbetaling is mislukt — de uitbetalingsroute gaf geen reden.',
    'create_all_failed' => 'De uitbetalingen konden niet worden aangemaakt — :reason',
    'no_payout_created_paused' => 'Geen uitbetaling aangemaakt:paused_note — hervat de uitbetalingen op de pagina van de eigenaar.',
    'nothing_ready' => 'Niets klaar voor betaling — geen enkele definitieve afrekening heeft een positief netto uit te betalen bedrag:paused_note.',
    'no_new_payouts' => 'Geen nieuwe uitbetalingen — elke afrekening die klaar is, heeft er al een:paused_note.',
    'payouts_created' => '{1} :count uitbetaling aangemaakt:paused_note.|[0,*] :count uitbetalingen aangemaakt:paused_note.',
    'some_not_created' => ':message Sommige konden niet worden aangemaakt — :reason',
    'nothing_paid_paused' => 'Niets betaald:paused_note — hervat de uitbetalingen op de pagina van de eigenaar.',
    'no_pending_payouts' => 'Geen openstaande uitbetalingen om te betalen.',
    'paid_only' => '{1} :count uitbetaling als betaald gemarkeerd:paused_note.|[0,*] :count uitbetalingen als betaald gemarkeerd:paused_note.',
    'failed_only' => '[0,*] :count mislukt op de uitbetalingsroute:paused_note.',
    'paid_and_failed' => '{1} :count uitbetaling als betaald gemarkeerd · :failed mislukt op de uitbetalingsroute:paused_note.|[0,*] :count uitbetalingen als betaald gemarkeerd · :failed mislukt op de uitbetalingsroute:paused_note.',
    'some_not_attempted' => ':message Sommige konden niet worden geprobeerd — :reason',
    'nothing_payable_negative' => 'Niets uit te betalen — het nettobedrag van deze afrekening is negatief, dus het saldo wordt doorgeschoven en verrekend met de volgende afrekening van de eigenaar.',
    'nothing_payable_zero' => 'Niets uit te betalen — het nettobedrag van deze afrekening is nul, dus er is niets verschuldigd.',
    'workspace_paused_until' => 'Organisatie gepauzeerd — uitbetalingen zijn tot :date alleen-lezen.',
    'workspace_paused_for_now' => 'Organisatie gepauzeerd — uitbetalingen zijn voorlopig alleen-lezen.',
    'paused_note' => ' · :skipped',
    'paused_owners_skipped' => '{1} :count gepauzeerde eigenaar overgeslagen|[0,*] :count gepauzeerde eigenaren overgeslagen',
];
