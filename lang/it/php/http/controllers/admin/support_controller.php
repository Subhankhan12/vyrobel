<?php

// I18N-01 — it translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'Questo collegamento non esiste più.',
    'unlink_refused_only_way_in' => 'Operazione rifiutata: questo è l’unico modo in cui la persona può accedere (nessuna password, nessun altro provider). Scollegarlo le impedirebbe definitivamente l’accesso.',
    'unlinked' => 'Provider :provider scollegato. Verifica: il collegamento non esiste più.',
    'unlink_not_landed' => 'Lo scollegamento non è andato a buon fine: il collegamento è ancora presente.',
    'already_verified' => 'E-mail già verificata.',
    'marked_verified' => 'E-mail segnata come verificata. Verifica sul database: :stored UTC.',
    'verify_not_landed' => 'La scrittura non è andata a buon fine: email_verified_at è ancora vuoto.',
    'already_verified_nothing_to_resend' => 'E-mail già verificata: non c’è nulla da reinviare.',
    'verification_discarded' => 'NON RECAPITATA — la posta è impostata su «:mailer», quindi l’e-mail di verifica è stata scartata. Nessuno ha ricevuto nulla. Confermare l’identità della persona in un altro modo, poi usare «Segna e-mail come verificata».',
    'verification_handed' => 'E-mail di verifica consegnata al sistema di invio (:mailer).',
    'no_reset_link' => 'Nessun link di reimpostazione creato: :status',
    'reset_discarded' => 'NON RECAPITATA — è stato creato un link di reimpostazione, ma la posta è impostata su «:mailer», quindi l’e-mail è stata scartata. Nessuno ha ricevuto nulla.',
    'reset_handed' => 'E-mail di reimpostazione della password consegnata al sistema di invio (:mailer).',
    'verification_not_sent_undeliverable' => 'NON INVIATA — questo indirizzo non può mai ricevere e-mail (:reason), quindi l’e-mail di verifica non è stata consegnata al sistema di invio. Nessuno ha ricevuto nulla.',
    'reset_not_sent_undeliverable' => 'NON INVIATO — questo indirizzo non può mai ricevere e-mail (:reason), quindi il link di reimpostazione non è stato consegnato al sistema di invio. Nessuno ha ricevuto nulla.',
];
