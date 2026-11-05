<?php

// I18N-01 — et translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => 'Valige hinnaplaan.',
    'name_required' => 'Andke hooajale nimi.',
    'name_too_long' => 'Hooaja nimi võib olla kuni 80 märki pikk.',
    'choose_first_night' => 'Valige esimene öö.',
    'choose_last_night' => 'Valige viimane öö.',
    'last_before_first' => 'Viimane öö ei saa olla enne esimest ööd.',
    'price_required' => 'Sisestage hind öö kohta.',
    'read_only' => 'Teie organisatsioon on praegu ainult vaatamiseks — hinnamuudatused on peatatud.',
    'price_out_of_range' => 'Sisestage hind öö kohta, mis on suurem kui null ja mitte üle 1 000 000.',
    'season_saved' => 'Hooaeg on salvestatud. Vaadake üle, mida see muudaks, ja rakendage see, et need ööd kirja panna.',
    'already_applied' => 'See hooaeg on juba rakendatud, seega seda ei saa muuta — selle kirjutatud öised hinnad on asendanud kõik, mis seal enne oli, ja eelmisi hindu ei salvestatud kuhugi. Koostage nendele öödele hoopis uus hooaeg.',
    'season_updated' => 'Hooaeg on uuendatud. Vaadake üle, mida see muudaks, ja rakendage see, et need ööd kirja panna.',
    'deleted_after_apply' => 'Hooaeg „:season“ on kustutatud. ⚠️ Selle juba rakendatud öised hinnad jäävad muutmata — hooaja kustutamine ei taasta hindu, mille see asendas. Määrake need ööd ise, kui hinnad on valed.',
    'deleted_not_applied' => 'Hooaeg „:season“ on kustutatud. Seda ei olnud rakendatud, seega ühtegi hinda ei muudetud.',
    'applied' => 'Hooaeg :season on rakendatud — hinnad kirjutati üle: :nights, :units.',
    'nights_count' => '{1}:count öö|[0,*]:count ööd',
    'units_count' => '{1}:count majutusüksus|[0,*]:count majutusüksust',
    'overlapping_reapplied' => '{1}:count kattuv hooaeg rakendati uuesti ülimuslikkuse järjekorras.|[0,*]:count kattuvat hooaega rakendati uuesti ülimuslikkuse järjekorras.',
    'pick_unit_to_continue' => 'Jätkamiseks valige majutusüksus.',
    'stay_nights_unpriced' => '{1}Viibimise :count ööl pole veel hinda.|[0,*]Viibimise :count ööl pole veel hinda.',
];
