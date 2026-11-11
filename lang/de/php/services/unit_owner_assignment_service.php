<?php

// I18N-01 — de translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => 'Es kann keine Einheit zugewiesen werden (Eigentümer: :owner) — die Einladung wurde noch nicht angenommen, oder der Eigentümer wurde entfernt. Nur ein aktiver Eigentümer kann eine Einheit erhalten.',
    'unit_already_owned' => '„:unit“ hat bereits einen Eigentümer (:owner) und kann hier nicht jemand anderem zugewiesen werden. Nur eine Einheit ohne Eigentümer kann zugewiesen werden.',
    'unit_owned_by_another' => 'jemand anders',
    'date_invalid' => 'Wählen Sie ein gültiges Datum, ab dem die Einheit zugewiesen werden soll.',
    'date_in_past' => 'Wählen Sie heute oder einen späteren Tag. Ein bereits vergangener Tag würde Erlöse ändern, die schon berechnet sind.',
    'date_too_far' => 'Wählen Sie einen Tag innerhalb der nächsten :days Tage.',
    'no_agreement' => 'Für :owner gibt es keinen Vertrag, der „:unit“ am :date umfasst. Legen Sie zuerst einen Vertrag für :owner an, der diese Einheit ab :date umfasst, und weisen Sie die Einheit danach zu.',
    'stay_across_date' => 'Ein Gast wohnt in „:unit“ (:from bis :to) — der :date liegt mitten in diesem Aufenthalt. Wählen Sie :to oder einen späteren Tag, damit kein Aufenthalt zwischen Ihnen und :owner geteilt wird.',
    'future_stays_with_another_owner' => 'Für „:unit“ gibt es Aufenthalte am oder nach dem :date, die bereits für andere Eigentümer berechnet wurden (:owners). Diese bleiben dort, daher kann die Einheit nicht ab diesem Tag zugewiesen werden.',
];
