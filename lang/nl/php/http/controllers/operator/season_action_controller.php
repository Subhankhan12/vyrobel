<?php

// I18N-01 — nl translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => 'Kies een tariefplan.',
    'name_required' => 'Geef het seizoen een naam.',
    'name_too_long' => 'De naam van het seizoen mag maximaal 80 tekens lang zijn.',
    'choose_first_night' => 'Kies de eerste nacht.',
    'choose_last_night' => 'Kies de laatste nacht.',
    'last_before_first' => 'De laatste nacht kan niet vóór de eerste nacht liggen.',
    'price_required' => 'Voer een prijs per nacht in.',
    'read_only' => 'Uw organisatie is op dit moment alleen-lezen — tariefwijzigingen zijn gepauzeerd.',
    'price_out_of_range' => 'Voer een prijs per nacht in die groter is dan nul en niet hoger dan 1.000.000.',
    'season_saved' => 'Seizoen opgeslagen. Bekijk eerst wat er verandert en pas het daarna toe om die nachten weg te schrijven.',
    'already_applied' => 'Dit seizoen is al toegepast en kan daarom niet worden bewerkt — de prijzen per nacht die het heeft weggeschreven, hebben vervangen wat er stond, en de vorige prijzen zijn nergens vastgelegd. Maak in plaats daarvan een nieuw seizoen voor die nachten aan.',
    'season_updated' => 'Seizoen bijgewerkt. Bekijk eerst wat er verandert en pas het daarna toe om die nachten weg te schrijven.',
    'deleted_after_apply' => 'Seizoen “:season” verwijderd. ⚠️ De prijzen per nacht die het al heeft toegepast, blijven ongewijzigd — een seizoen verwijderen zet de vervangen prijzen niet terug. Stel die nachten zelf in als ze niet kloppen.',
    'deleted_not_applied' => 'Seizoen “:season” verwijderd. Het was nog niet toegepast, dus er zijn geen tarieven gewijzigd.',
    'applied' => ':season toegepast — :nights herschreven, verdeeld over :units.',
    'nights_count' => '{1} :count nacht|[0,*] :count nachten',
    'units_count' => '{1} :count eenheid|[0,*] :count eenheden',
    'overlapping_reapplied' => '{1} :count overlappend seizoen is opnieuw toegepast in volgorde van voorrang.|[0,*] :count overlappende seizoenen zijn opnieuw toegepast in volgorde van voorrang.',
    'pick_unit_to_continue' => 'Kies een eenheid om verder te gaan.',
    'stay_nights_unpriced' => '{1} :count nacht van het verblijf heeft nog geen prijs.|[0,*] :count nachten van het verblijf hebben nog geen prijs.',
];
