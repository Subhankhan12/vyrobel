<?php

// I18N-01 — it translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Inserire un indirizzo IP o un blocco CIDR valido (ad es. 203.0.113.4 o 203.0.113.0/24).',
    'ip_already_listed' => 'L’indirizzo :ip è già nell’elenco degli IP autorizzati.',
    'allowlist_enabled_auto_added' => 'Elenco IP autorizzati attivato. Abbiamo aggiunto anche il Suo indirizzo attuale (:ip) per evitare di bloccare il Suo stesso accesso.',
    'ip_added' => 'Indirizzo :ip aggiunto all’elenco degli IP autorizzati.',
    'remove_would_lock_out' => 'Rimuovendo l’indirizzo :ip perderebbe l’accesso: è l’unica regola che consente al Suo indirizzo (:current) di raggiungere il back office. Aggiungere prima una regola per il Suo indirizzo, oppure rimuovere le altre.',
    'ip_removed_allowlist_off' => 'Indirizzo :ip rimosso: l’elenco degli IP autorizzati è disattivato e chiunque abbia credenziali di accesso valide può raggiungere il back office.',
    'ip_removed' => 'Indirizzo :ip rimosso dall’elenco degli IP autorizzati.',
    'session_already_ended' => 'Questa sessione è già terminata.',
    'signed_out_of_device' => 'Disconnessione da :device effettuata.',
    'trusted_device_revoked' => 'Dispositivo attendibile revocato.',
    'csv_title' => 'Esportazione account Vyrobel',
    'csv_name' => 'Nome',
    'csv_email' => 'E-mail',
    'csv_locale' => 'Lingua',
    'csv_roles' => 'Ruoli',
    'csv_activity' => 'Attività',
    'csv_when' => 'Data',
    'csv_action' => 'Azione',
    'range_this_year' => 'Quest’anno',
    'range_custom' => 'Intervallo personalizzato',
    'range_all_time' => 'Tutto il periodo',
];
