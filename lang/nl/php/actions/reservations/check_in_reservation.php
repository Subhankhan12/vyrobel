<?php

// I18N-01 — nl translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => 'Een geannuleerde reservering kan niet worden ingecheckt: de reservering is afgesloten en de kamer is mogelijk al opnieuw verkocht. Een terugkerende gast heeft een nieuwe reservering nodig.',
    'refused_no_show' => 'Een reservering met de status no-show kan niet worden ingecheckt: de reservering is afgesloten en de nachten zijn weer vrijgegeven voor verkoop. Wilt u deze gast een kamer geven, registreer dan bij de receptie een walk-in — die controleert de beschikbaarheid opnieuw voordat er wordt geboekt. De no-show en eventuele kosten die al in rekening zijn gebracht, blijven in het gastprofiel staan.',
    'refused_checked_out' => 'Een uitgecheckte reservering kan niet worden ingecheckt: het vertrek is al vastgelegd en verwerkt. Was de check-out een vergissing? Gebruik dan eerst “Check-out terugdraaien” bij de reservering.',
    'refused_tentative' => 'Een reservering in optie kan niet worden ingecheckt: de kamer wordt vastgehouden maar is niet bevestigd, dus het verblijf is deze gast nog niet toegezegd. Klik eerst op “Optie bevestigen” bij deze reservering en check de gast daarna in. De nachten worden al door deze reservering vastgehouden, dus bevestigen neemt verder niets in beslag en kan niet mislukken door gebrek aan beschikbaarheid.',
    'refused_other' => 'Een reservering met de status “:status” kan niet worden ingecheckt.',
];
