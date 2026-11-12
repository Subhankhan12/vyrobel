<?php

// I18N-01 — pl translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => 'Nie można przypisać jednostki właścicielowi (:owner) – zaproszenie nie zostało jeszcze przyjęte albo właściciel został zarchiwizowany. Jednostkę można przypisać tylko aktywnemu właścicielowi.',
    'unit_on_pending_invitation' => 'Jednostka „:unit” jest wymieniona w zaproszeniu właściciela, które nie zostało jeszcze przyjęte. Po jego przyjęciu stanie się jednostką tego właściciela – można też najpierw anulować zaproszenie, aby przypisać jednostkę komuś innemu.',
    'unit_already_owned' => 'Jednostka „:unit” ma już właściciela (:owner), więc nie można jej tu przypisać komuś innemu. Przypisać można tylko jednostkę bez właściciela.',
    'unit_owned_by_another' => 'inny właściciel',
    'date_invalid' => 'Wybierz prawidłową datę, od której jednostka ma zostać przypisana.',
    'date_in_past' => 'Wybierz dzisiejszy lub późniejszy dzień. Dzień, który już minął, zmieniłby dochód, który został już wyliczony.',
    'date_too_far' => 'Wybierz dzień w ciągu najbliższych :days dni.',
    'no_agreement' => 'Właściciel :owner nie ma umowy obejmującej jednostkę „:unit” w dniu :date. Najpierw należy dodać dla właściciela :owner umowę obejmującą tę jednostkę od :date, a następnie przypisać jednostkę.',
    'stay_across_date' => 'Pobyt gościa w jednostce „:unit” trwa od :from do :to, więc obejmuje dzień :date. Wybierz :to lub późniejszy dzień, aby żaden pobyt nie był podzielony między Państwa a właściciela :owner.',
    'future_stays_with_another_owner' => 'W jednostce „:unit” są pobyty zaczynające się w dniu :date lub później, które zostały już wyliczone dla innego właściciela (:owners). Te pobyty nie mogą zmienić właściciela, więc jednostki nie można przypisać od tego dnia.',
];
