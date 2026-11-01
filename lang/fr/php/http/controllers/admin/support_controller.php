<?php

// I18N-01 — fr translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'Cette connexion n’existe plus.',
    'unlink_refused_only_way_in' => 'Refusé — c’est le seul moyen de connexion de cette personne (pas de mot de passe, aucun autre fournisseur). Le dissocier la bloquerait définitivement.',
    'unlinked' => ':provider dissocié — vérification : la connexion a disparu.',
    'unlink_not_landed' => 'La dissociation n’a pas abouti — la connexion est toujours là.',
    'already_verified' => 'Déjà confirmé.',
    'marked_verified' => 'Marqué comme confirmé — relu dans la base de données : :stored UTC.',
    'verify_not_landed' => 'L’écriture n’a pas abouti — email_verified_at est toujours vide.',
    'already_verified_nothing_to_resend' => 'Déjà confirmé — rien à renvoyer.',
    'verification_discarded' => 'NON DISTRIBUÉ — envoi des e-mails : « :mailer », l’e-mail de confirmation a donc été supprimé. Personne n’a rien reçu. Vérifiez l’identité de la personne autrement, puis utilisez « Marquer l’e-mail comme confirmé ».',
    'verification_handed' => 'E-mail de confirmation transmis au service d’envoi (:mailer).',
    'no_reset_link' => 'Aucun lien de réinitialisation n’a été créé : :status',
    'reset_discarded' => 'NON DISTRIBUÉ — un lien de réinitialisation a été créé, mais l’e-mail a été supprimé (envoi des e-mails : « :mailer »). Personne n’a rien reçu.',
    'reset_handed' => 'E-mail de réinitialisation du mot de passe transmis au service d’envoi (:mailer).',
    'verification_not_sent_undeliverable' => 'NON ENVOYÉ — cette adresse ne peut jamais recevoir d’e-mails (:reason), l’e-mail de confirmation n’a donc pas été transmis au service d’envoi. Personne n’a rien reçu.',
    'reset_not_sent_undeliverable' => 'NON ENVOYÉ — cette adresse ne peut jamais recevoir d’e-mails (:reason), le lien de réinitialisation n’a donc pas été transmis au service d’envoi. Personne n’a rien reçu.',
];
