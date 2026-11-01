<?php

// I18N-01 — fr translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => 'Interrupteurs d’urgence',
    'subtitle' => 'Arrêtez les nouvelles inscriptions, ou la connexion via un fournisseur, sans déploiement. Chaque bascule est auditée avec un motif.',
    'explainer' => 'Chaque interrupteur est <strong>activé</strong> tant que personne ne le désactive ici — y compris juste après un déploiement. Aucun ne peut affecter cette console. Désactiver la <strong>connexion</strong> via un fournisseur bloque toute personne dont c’est le seul moyen d’accès ; ces interrupteurs affichent donc le nombre de comptes concernés et demandent une double confirmation.',
    'state_on' => 'Activé',
    'state_off' => 'Désactivé',
    'last_changed_by' => 'Dernière modification le :time UTC par :name.',
    'last_changed' => 'Dernière modification le :time UTC.',
    'never_changed' => 'Jamais modifié — activé par défaut.',
    'no_account_relies' => 'Aucun compte ne dépend uniquement de ce fournisseur pour l’instant — chacun a aussi un mot de passe ou un autre moyen d’accès.',
    'would_lock_out' => '{1} Le désactiver bloquerait <strong>:count</strong> compte — sans mot de passe, ce fournisseur est son seul moyen d’accès.|[0,*] Le désactiver bloquerait <strong>:count</strong> comptes — sans mot de passe, ce fournisseur est leur seul moyen d’accès.',
    'reason' => 'Motif',
    'why_turn_off' => 'Pourquoi le désactivez-vous ?',
    'why_turn_on' => 'Pourquoi l’activez-vous ?',
    'confirm_lockout_zero' => 'Je comprends que toute personne qui se connecte uniquement de cette façon sera bloquée à partir de maintenant et jusqu’à ce que l’interrupteur soit réactivé.',
    'confirm_lockout' => '{1} Je comprends que cela bloque :count compte jusqu’à ce que l’interrupteur soit réactivé.|[0,*] Je comprends que cela bloque :count comptes jusqu’à ce que l’interrupteur soit réactivé.',
    'turn_off' => 'Désactiver',
    'turn_on' => 'Activer',
];
