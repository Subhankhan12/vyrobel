<?php

// I18N-01 — et translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => 'Sisestage komisjonitasu protsentides.',
    'commission_range' => 'Komisjonitasu peab olema arv vahemikus 0–100.',
    'commission_unchanged' => 'Midagi ei muutunud — :rate on omaniku :name komisjonitasu juba alates :date.',
    'commission_from_today' => 'Omaniku :name komisjonitasu on alates tänasest :rate — kehtivat haldusleppingut ei olnud.',
    'commission_waiting_cancelled' => 'Omaniku :name komisjonitasu jääb samaks: :rate — kuupäevaks :date ootel olnud muudatust ei tehta.',
    'commission_from_date' => 'Omaniku :name komisjonitasu on :rate alates :date — kehtib alates sellest hetkest arvutatavatele broneeringutele. Juba arvutatud broneeringute komisjonitasu ei muutu.',
    'payouts_paused' => 'Omaniku :name väljamaksed on peatatud — talle ei saadeta ühtegi väljamakset, kuni jätkate.',
    'payouts_resumed' => 'Omaniku :name väljamaksed jätkuvad.',
    'period_start_required' => 'Valige perioodi esimene päev.',
    'period_end_required' => 'Valige perioodi viimane päev.',
    'period_end_after_start' => 'Periood peab lõppema alguspäeval või hiljem.',
    'period_not_closed' => 'Seda perioodi ei saanud sulgeda: :reason Valige periood, mis algab pärast viimast lukustatud perioodi.',
    'period_closed' => 'Periood on suletud — väljavõte nr :number on lõplikuks muudetud, väljamakstav summa on :amount. Külalistelt veel laekumata tulu jääb sellest väljavõttest välja ja ilmub hilisemale väljavõttele.',
    'note_required' => 'Kirjutage esmalt märkus.',
    'note_max' => 'Märkus võib olla kuni 2 000 märki pikk.',
    'note_added' => 'Märkus on lisatud.',
    'remove_password_required' => 'Selle omaniku arhiveerimiseks sisestage oma parool.',
    'password_wrong' => 'See parool ei ole õige — sisestage parool, millega sisse logite.',
    'confirm_name_required' => 'Kinnitamiseks tippige omaniku nimi.',
    'confirm_name_mismatch' => 'Tippitud nimi ei ühti — omanikku ei arhiveeritud.',
    'owner_removed' => 'Omanik :name on arhiveeritud — majutusüksused on omanikust lahti seotud, kõik väljavõtted ja väljamaksed säilitatakse raamatupidamise jaoks.',
    'workspace_paused_until' => 'Organisatsioon on peatatud — omanike muudatused on ainult vaatamiseks kuni :date.',
    'workspace_paused_for_now' => 'Organisatsioon on peatatud — omanike muudatused on praegu ainult vaatamiseks.',
];
