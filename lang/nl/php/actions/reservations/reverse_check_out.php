<?php

// I18N-01 — nl translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Het verblijf gaat terug naar ingecheckt.',
    'pending_tasks_removed' => '{1} :count schoonmaaktaak na vertrek wordt verwijderd — nog niet gestart.|[0,*] :count schoonmaaktaken na vertrek worden verwijderd — nog niet gestart.',
    'started_tasks_kept' => '{1} :count schoonmaaktaak is al gestart en blijft ongemoeid — werk annuleren waaraan iemand al is begonnen, is een beslissing voor housekeeping en geen onderdeel van het terugdraaien.|[0,*] :count schoonmaaktaken zijn al gestart en blijven ongemoeid — werk annuleren waaraan iemand al is begonnen, is een beslissing voor housekeeping en geen onderdeel van het terugdraaien.',
    'unit_stays_dirty' => 'De eenheid blijft als vuil gemarkeerd. De gast is terug in de kamer, dus “schoon” klopt misschien niet.',
    'notifications_stay_delivered' => 'Meldingen die al bij medewerkers en de gast zijn afgeleverd, blijven afgeleverd.',
    'review_unknown' => 'We weten niet of er voor dit verblijf een beoordelingsverzoek is verstuurd, omdat het is uitgecheckt voordat dit product die verzoeken begon vast te leggen. Mogelijk is het verstuurd, mogelijk niet.',
    'review_sent_on' => 'Op :date is een beoordelingsverzoek naar de gast gemaild. Dit kan niet worden teruggehaald.',
    'review_sent' => 'Er is een beoordelingsverzoek naar de gast gemaild. Dit kan niet worden teruggehaald.',
    'review_suppressed_by_operator' => 'Er is geen beoordelingsverzoek verstuurd — bij deze accommodatie staat de e-mail met het beoordelingsverzoek uit.',
    'review_suppressed_by_guest' => 'Er is geen beoordelingsverzoek verstuurd — de gast heeft gevraagd geen e-mails over beoordelingen te ontvangen.',
    'review_not_sent' => 'Er is geen beoordelingsverzoek verstuurd — er was geen contactadres, of het versturen is mislukt.',
    'refused_not_checked_out' => 'Alleen bij een uitgecheckt verblijf kan de check-out worden teruggedraaid; dit verblijf heeft status “:status”.',
];
