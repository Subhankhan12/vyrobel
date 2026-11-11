<?php

// I18N-01 — et translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => 'Majutusüksust ei saa anda (omanik: :owner) — kutse on veel vastu võtmata või omanik on arhiveeritud. Majutusüksust saab anda ainult aktiivsele omanikule.',
    'unit_already_owned' => 'Majutusüksusel „:unit“ on juba omanik (:owner), seega ei saa seda siin kellelegi teisele anda. Anda saab ainult majutusüksust, millel pole omanikku.',
    'unit_owned_by_another' => 'teine omanik',
    'date_invalid' => 'Valige kehtiv kuupäev, millest alates majutusüksus antakse.',
    'date_in_past' => 'Valige tänane või hilisem päev. Möödunud päev muudaks juba arvutatud tulu.',
    'date_too_far' => 'Valige päev järgmise :days päeva jooksul.',
    'no_agreement' => 'Omanikul :owner pole halduslepingut, mis hõlmaks majutusüksust „:unit“ kuupäeval :date. Sõlmige omanikuga :owner esmalt haldusleping, mis hõlmab seda majutusüksust alates :date, ja andke talle seejärel majutusüksus.',
    'stay_across_date' => 'Majutusüksuses „:unit“ viibib külaline alates :from kuni :to, mis hõlmab kuupäeva :date. Valige kuupäev :to või hilisem, et ühtegi viibimist ei jagataks teie ja omaniku :owner vahel.',
    'future_stays_with_another_owner' => 'Majutusüksusel „:unit“ on viibimisi alates :date, mille tulu on juba arvutatud teisele omanikule (:owners). Need jäävad neile, seega ei saa majutusüksust sellest päevast alates anda.',
];
