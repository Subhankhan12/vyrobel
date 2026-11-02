<?php

// I18N-01 — ro translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'Această conexiune nu mai există.',
    'unlink_refused_only_way_in' => 'Refuzat — aceasta este singura modalitate prin care această persoană se poate conecta (fără parolă, fără alt furnizor). Eliminarea ei i-ar bloca definitiv accesul.',
    'unlinked' => 'Furnizorul :provider a fost eliminat — confirmat în baza de date: conexiunea nu mai există.',
    'unlink_not_landed' => 'Eliminarea nu s-a aplicat — conexiunea există încă.',
    'already_verified' => 'Deja verificat.',
    'marked_verified' => 'Marcat ca verificat — confirmat în baza de date: :stored UTC.',
    'verify_not_landed' => 'Scrierea nu s-a aplicat — email_verified_at este încă gol.',
    'already_verified_nothing_to_resend' => 'Deja verificat — nu este nimic de retrimis.',
    'verification_discarded' => 'NELIVRAT — e-mailurile sunt setate pe „:mailer”, deci e-mailul de verificare a fost aruncat. Nimeni nu a primit nimic. Confirmați identitatea persoanei în alt mod, apoi folosiți „Marcare e-mail ca verificat”.',
    'verification_handed' => 'E-mailul de verificare a fost transmis sistemului de e-mail (:mailer).',
    'no_reset_link' => 'Nu s-a creat niciun link de resetare: :status',
    'reset_discarded' => 'NELIVRAT — s-a creat un link de resetare, dar e-mailurile sunt setate pe „:mailer”, deci e-mailul a fost aruncat. Nimeni nu a primit nimic.',
    'reset_handed' => 'E-mailul de resetare a parolei a fost transmis sistemului de e-mail (:mailer).',
    'verification_not_sent_undeliverable' => 'NETRIMIS — această adresă nu poate primi niciodată e-mailuri (:reason), deci e-mailul de verificare nu a fost transmis sistemului de e-mail. Nimeni nu a primit nimic.',
    'reset_not_sent_undeliverable' => 'NETRIMIS — această adresă nu poate primi niciodată e-mailuri (:reason), deci linkul de resetare nu a fost transmis sistemului de e-mail. Nimeni nu a primit nimic.',
];
