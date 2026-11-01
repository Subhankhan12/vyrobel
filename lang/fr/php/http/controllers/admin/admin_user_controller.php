<?php

// I18N-01 — fr translation of lang/en/php/http/controllers/admin/admin_user_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_pending_reissued' => 'Seule une invitation en attente peut être réémise.',
    'last_owner_role' => 'C’est le dernier « Administrateur principal » actif. Attribuez d’abord ce rôle à une autre personne.',
    'role_changed' => ':name a désormais le rôle « :role ».',
    'cannot_revoke_self' => 'Vous ne pouvez pas révoquer votre propre accès. Demandez à un autre « Administrateur principal ».',
    'last_owner_revoke' => 'C’est le dernier « Administrateur principal » actif : son accès ne peut pas être révoqué.',
    'access_revoked' => '{1} Accès révoqué pour :name ; :count session terminée.|[0,*] Accès révoqué pour :name ; :count sessions terminées.',
    'only_revoked_restored' => 'Seul un administrateur révoqué peut être rétabli.',
    'access_restored' => 'Accès rétabli pour :name.',
    'ended_own_other_sessions' => '{1} :count de vos autres sessions a été terminée — celle-ci reste connectée.|[0,*] :count de vos autres sessions ont été terminées — celle-ci reste connectée.',
    'ended_sessions_for' => '{1} :count session terminée pour :name.|[0,*] :count sessions terminées pour :name.',
];
