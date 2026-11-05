<?php

// I18N-01 — fr translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => 'Saisissez la commission, en pourcentage.',
    'commission_range' => 'La commission doit être un nombre compris entre 0 et 100.',
    'commission_unchanged' => 'Rien n’a changé — la commission pour :name est déjà de :rate à partir du :date.',
    'commission_from_today' => 'La commission pour :name est de :rate à partir d’aujourd’hui — aucun contrat n’était en vigueur.',
    'commission_waiting_cancelled' => ':name reste à :rate — la modification prévue pour le :date n’aura pas lieu.',
    'commission_from_date' => 'La commission pour :name est de :rate à partir du :date — pour les réservations calculées à partir de maintenant. Les réservations déjà calculées gardent leur taux.',
    'payouts_paused' => 'Versements suspendus pour :name — aucun versement ne lui sera envoyé avant la reprise.',
    'payouts_resumed' => 'Versements repris pour :name.',
    'period_start_required' => 'Choisissez le premier jour de la période.',
    'period_end_required' => 'Choisissez le dernier jour de la période.',
    'period_end_after_start' => 'La période doit se terminer le jour où elle commence ou après.',
    'period_not_closed' => 'Cette période n’a pas pu être clôturée : :reason Choisissez une période qui commence après la dernière période scellée.',
    'period_sealed' => 'Impossible de régénérer un relevé finalisé ou verrouillé.',
    'period_not_closed_other' => 'Le relevé de cette période n’a pas pu être généré.',
    'period_closed' => 'Période clôturée — relevé n° :number finalisé, net à verser : :amount. Les revenus pas encore encaissés auprès des clients restent non imputés et figureront sur un relevé ultérieur.',
    'note_required' => 'Rédigez d’abord la note.',
    'note_max' => 'Une note peut contenir jusqu’à 2 000 caractères.',
    'note_added' => 'Note ajoutée.',
    'remove_password_required' => 'Saisissez votre mot de passe pour retirer ce propriétaire.',
    'password_wrong' => 'Ce mot de passe est incorrect — saisissez celui que vous utilisez pour vous connecter.',
    'confirm_name_required' => 'Saisissez le nom du propriétaire pour confirmer.',
    'confirm_name_mismatch' => 'Le nom saisi ne correspond pas — le propriétaire n’a pas été retiré.',
    'owner_removed' => 'Propriétaire :name retiré — unités dissociées, tous les relevés et versements conservés pour la comptabilité.',
    'workspace_paused_until' => 'Organisation en pause — les modifications des propriétaires sont en lecture seule jusqu’au :date.',
    'workspace_paused_for_now' => 'Organisation en pause — les modifications des propriétaires sont en lecture seule pour le moment.',
];
