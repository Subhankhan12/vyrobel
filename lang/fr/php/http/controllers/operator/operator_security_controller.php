<?php

// I18N-01 — fr translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Saisissez une adresse IP ou un bloc CIDR valide (ex. 203.0.113.4 ou 203.0.113.0/24).',
    'ip_already_listed' => ':ip figure déjà dans les adresses IP autorisées.',
    'allowlist_enabled_auto_added' => 'Liste des adresses IP autorisées activée. Nous avons aussi ajouté votre adresse actuelle (:ip) pour que vous ne bloquiez pas votre propre accès.',
    'ip_added' => 'Adresse :ip ajoutée aux adresses IP autorisées.',
    'remove_would_lock_out' => 'Supprimer :ip vous bloquerait l’accès — c’est la seule règle qui permet à votre adresse (:current) d’accéder au back-office. Ajoutez d’abord une règle pour votre adresse, ou supprimez les autres.',
    'ip_removed_allowlist_off' => 'Adresse :ip supprimée — la liste des adresses IP autorisées est désactivée : toute personne disposant d’identifiants de connexion valides peut accéder au back-office.',
    'ip_removed' => 'Adresse :ip supprimée des adresses IP autorisées.',
    'session_already_ended' => 'Cette session est déjà terminée.',
    'signed_out_of_device' => 'Déconnexion effectuée sur :device.',
    'trusted_device_revoked' => 'Appareil de confiance révoqué.',
    'csv_title' => 'Export du compte Vyrobel',
    'csv_name' => 'Nom',
    'csv_email' => 'Adresse e-mail',
    'csv_locale' => 'Langue',
    'csv_roles' => 'Rôles',
    'csv_activity' => 'Activité',
    'csv_when' => 'Date',
    'csv_action' => 'Action',
    'range_this_year' => 'Cette année',
    'range_custom' => 'Période personnalisée',
    'range_all_time' => 'Depuis le début',
];
