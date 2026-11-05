<?php

// I18N-01 — de translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => 'Geben Sie die Provision in Prozent ein.',
    'commission_range' => 'Die Provision muss eine Zahl zwischen 0 und 100 sein.',
    'commission_unchanged' => 'Nichts geändert — die Provision von :name beträgt ab :date bereits :rate.',
    'commission_from_today' => 'Die Provision von :name beträgt ab heute :rate — bisher galt kein Vertrag.',
    'commission_waiting_cancelled' => ':name bleibt bei :rate — die für :date geplante Änderung entfällt.',
    'commission_from_date' => 'Die Provision von :name beträgt ab :date :rate — für alle ab jetzt berechneten Buchungen. Bereits berechnete Buchungen behalten ihren Satz.',
    'payouts_paused' => 'Auszahlungen an :name pausiert — bis Sie sie fortsetzen, erhält der Eigentümer keine Auszahlung.',
    'payouts_resumed' => 'Auszahlungen an :name fortgesetzt.',
    'period_start_required' => 'Wählen Sie den ersten Tag des Zeitraums.',
    'period_end_required' => 'Wählen Sie den letzten Tag des Zeitraums.',
    'period_end_after_start' => 'Der Zeitraum darf nicht vor seinem ersten Tag enden.',
    'period_not_closed' => 'Dieser Zeitraum konnte nicht abgeschlossen werden: :reason Wählen Sie einen Zeitraum, der nach dem zuletzt abgeschlossenen beginnt.',
    'period_sealed' => 'Eine abgeschlossene oder gesperrte Abrechnung kann nicht neu erzeugt werden.',
    'period_not_closed_other' => 'Die Abrechnung für diesen Zeitraum konnte nicht erstellt werden.',
    'period_closed' => 'Zeitraum abgeschlossen — Abrechnung #:number mit einem Auszahlungsbetrag von :amount abgeschlossen. Noch nicht von Gästen kassierte Umsätze bleiben vorerst unberücksichtigt und erscheinen auf einer späteren Abrechnung.',
    'note_required' => 'Schreiben Sie zuerst die Notiz.',
    'note_max' => 'Eine Notiz darf bis zu 2.000 Zeichen lang sein.',
    'note_added' => 'Notiz hinzugefügt.',
    'remove_password_required' => 'Geben Sie Ihr Passwort ein, um diesen Eigentümer zu entfernen.',
    'password_wrong' => 'Das Passwort ist nicht korrekt — geben Sie das Passwort ein, mit dem Sie sich anmelden.',
    'confirm_name_required' => 'Geben Sie zur Bestätigung den Namen des Eigentümers ein.',
    'confirm_name_mismatch' => 'Der eingegebene Name stimmt nicht überein — der Eigentümer wurde nicht entfernt.',
    'owner_removed' => ':name entfernt — Einheiten nicht mehr zugewiesen, alle Abrechnungen & Auszahlungen bleiben für die Buchhaltung erhalten.',
    'workspace_paused_until' => 'Organisation pausiert — bei Eigentümern besteht bis :date nur Lesezugriff.',
    'workspace_paused_for_now' => 'Organisation pausiert — bei Eigentümern besteht vorerst nur Lesezugriff.',
];
