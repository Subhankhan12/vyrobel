<?php

// I18N-01 — nl translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => 'Aan :owner kan geen eenheid worden toegewezen — de uitnodiging is nog niet geaccepteerd, of de eigenaar is weggehaald. Alleen aan een actieve eigenaar kan een eenheid worden toegewezen.',
    'unit_already_owned' => '“:unit” hoort al bij :owner, dus de eenheid kan hier niet aan iemand anders worden toegewezen. Alleen een eenheid zonder eigenaar kan worden toegewezen.',
    'unit_owned_by_another' => 'een andere eigenaar',
    'date_invalid' => 'Kies een geldige datum waarop de toewijzing ingaat.',
    'date_in_past' => 'Kies vandaag of een latere dag. Een dag die al voorbij is, zou opbrengsten wijzigen die al zijn berekend.',
    'date_too_far' => 'Kies een dag binnen de komende :days dagen.',
    'no_agreement' => ':owner heeft op :date geen overeenkomst die “:unit” omvat. Geef :owner eerst een overeenkomst die deze eenheid vanaf :date omvat en wijs de eenheid daarna toe.',
    'stay_across_date' => 'Een gast verblijft in “:unit” van :from tot :to; :date valt midden in dat verblijf. Kies :to of een latere dag, zodat er geen verblijf tussen u en :owner wordt gesplitst.',
    'future_stays_with_another_owner' => 'Voor “:unit” zijn er verblijven op of na :date die al voor een andere eigenaar zijn berekend (:owners). Die blijven bij hen, dus de eenheid kan niet vanaf die dag worden toegewezen.',
];
