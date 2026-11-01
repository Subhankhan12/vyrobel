<?php

// I18N-01 — fr translation of lang/en/php/http/controllers/auth/account_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'login_approval_unavailable' => 'L’autorisation de connexion ne peut pas encore être activée : Vyrobel n’envoie pas d’e-mails pour le moment, le lien d’autorisation ne vous parviendrait donc jamais et un nouvel appareil ne pourrait pas se connecter.',
    'login_approval_on' => 'L’autorisation de connexion est activée — un nouvel appareil attend que vous l’autorisiez.',
    'login_approval_off' => 'L’autorisation de connexion est désactivée.',
    'approval_no_longer_waiting' => 'Cette connexion n’est plus en attente — elle a déjà été traitée ou elle a expiré.',
    'approval_approved' => 'Connexion autorisée — cet appareil peut maintenant terminer la connexion.',
    'approval_denied' => 'Connexion refusée — elle ne peut pas aboutir. Si ce n’était pas vous, modifiez votre mot de passe.',
    'login_alerts_on' => 'Les alertes de connexion sont activées — une connexion depuis un nouvel appareil apparaît dans vos notifications.',
    'login_alerts_off' => 'Les alertes de connexion sont désactivées.',
    'session_timeout_off' => 'L’expiration de session est désactivée — votre session reste ouverte pendant :duration sans activité, comme auparavant.',
    'session_timeout_on' => 'Votre session sera fermée après :duration sans activité.',
];
