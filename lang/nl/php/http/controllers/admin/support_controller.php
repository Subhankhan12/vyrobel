<?php

// I18N-01 — nl translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'Die koppeling bestaat niet meer.',
    'unlink_refused_only_way_in' => 'Geweigerd — dit is de enige manier waarop deze persoon kan inloggen (geen wachtwoord, geen andere provider). Ontkoppelen zou deze persoon voorgoed buitensluiten.',
    'unlinked' => ':provider ontkoppeld — teruggelezen: de koppeling is weg.',
    'unlink_not_landed' => 'Het ontkoppelen is niet doorgevoerd — de koppeling bestaat nog.',
    'already_verified' => 'Al geverifieerd.',
    'marked_verified' => 'Als geverifieerd gemarkeerd — teruggelezen uit de database: :stored UTC.',
    'verify_not_landed' => 'De wijziging is niet doorgevoerd — email_verified_at is nog leeg.',
    'already_verified_nothing_to_resend' => 'Al geverifieerd — er valt niets opnieuw te versturen.',
    'verification_discarded' => 'NIET BEZORGD — e-mail staat op “:mailer”, dus de verificatie-e-mail is weggegooid. Niemand heeft iets ontvangen. Bevestig op een andere manier wie deze persoon is en gebruik daarna “E-mailadres als geverifieerd markeren”.',
    'verification_handed' => 'Verificatie-e-mail overgedragen aan de mailer (:mailer).',
    'no_reset_link' => 'Er is geen resetlink aangemaakt: :status',
    'reset_discarded' => 'NIET BEZORGD — er is een resetlink aangemaakt, maar e-mail staat op “:mailer”, dus de e-mail is weggegooid. Niemand heeft iets ontvangen.',
    'reset_handed' => 'E-mail voor wachtwoordreset overgedragen aan de mailer (:mailer).',
    'verification_not_sent_undeliverable' => 'NIET VERSTUURD — dit adres kan nooit e-mail ontvangen (:reason), dus de verificatie-e-mail is niet aan de mailer overgedragen. Niemand heeft iets ontvangen.',
    'reset_not_sent_undeliverable' => 'NIET VERSTUURD — dit adres kan nooit e-mail ontvangen (:reason), dus de resetlink is niet aan de mailer overgedragen. Niemand heeft iets ontvangen.',
];
