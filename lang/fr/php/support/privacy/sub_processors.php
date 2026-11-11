<?php

// I18N-01 — fr translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => 'Aucun contrat enregistré',
    'state_switched_on' => 'Actif — activé',
    'state_not_active' => 'Inactif',
    'state_configured' => 'Configuré',
    'hostinger_hosting' => [
        'name' => 'Hostinger (hébergement)',
        'purpose' => 'Fait fonctionner l’application et sa base de données.',
        'data' => 'Tout ce que le produit enregistre — chaque compte, chaque établissement et chaque fiche client.',
        'state' => 'Actif — hébergeur de production',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => 'Diffuse et met en cache le site public en amont de l’hébergeur.',
        'data' => 'Toutes les requêtes qui y transitent : adresses IP, URL, cookies en transit.',
        'state' => 'Actif — constaté sur chaque réponse',
    ],
    'google' => [
        'name' => 'Google (connexion)',
        'purpose' => 'Connexion et inscription avec un compte Google.',
        'data' => 'L’identité Google de la personne : identifiant du compte, adresse e-mail, nom.',
    ],
    'x' => [
        'name' => 'X (connexion)',
        'purpose' => 'Connexion et inscription avec un compte X.',
        'data' => 'L’identité X de la personne : identifiant du compte, nom d’utilisateur, nom.',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger (e-mail)',
        'purpose' => 'Distribue tous les e-mails transactionnels, depuis la boîte mail vyrobel.com.',
        'data' => 'Adresses des destinataires et contenu des messages — noms des clients, références de réservation, dates.',
        'state_active' => 'Actif — service d’envoi',
        'state_inactive' => 'Inactif — envoi des e-mails : « :mailer »',
    ],
    'stripe' => [
        'name' => 'Stripe (paiements et facturation)',
        'purpose' => 'Facturation des abonnements, paiements par carte des clients, versements aux propriétaires (Connect).',
        'data' => 'Contacts de facturation, montants, références de réservation ; pour les versements aux propriétaires, données d’identité pour le KYC.',
        'state_fake' => 'Inactif — un client factice est utilisé',
    ],
    'anthropic' => [
        'name' => 'Anthropic (assistant IA)',
        'purpose' => 'Répond aux questions posées à l’assistant IA.',
        'data' => 'La question posée et le contexte envoyé avec elle.',
        'state_off' => 'Inactif — client factice ou pas de clé',
    ],
    'channex' => [
        'name' => 'Channex (channel manager)',
        'purpose' => 'Synchronise les tarifs, les disponibilités et les réservations avec les OTA.',
        'data' => 'Informations sur les réservations et les clients échangées avec les canaux.',
        'state_off' => 'Non connecté',
    ],
];
