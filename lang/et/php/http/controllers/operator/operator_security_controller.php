<?php

// I18N-01 — et translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => 'Sisestage kehtiv IP-aadress või CIDR-vahemik (nt 203.0.113.4 või 203.0.113.0/24).',
    'ip_already_listed' => 'IP-aadress :ip on juba lubatud aadresside loendis.',
    'allowlist_enabled_auto_added' => 'Lubatud IP-aadresside loend on sisse lülitatud. Lisasime sinna ka teie praeguse aadressi (:ip), et te end välja ei lukustaks.',
    'ip_added' => 'Aadress :ip on lisatud lubatud aadresside loendisse.',
    'remove_would_lock_out' => 'Aadressi :ip eemaldamine lukustaks teid välja — see on ainus reegel, mis lubab teie aadressil (:current) töötajate keskkonda pääseda. Lisage esmalt reegel oma aadressile või eemaldage teised reeglid.',
    'ip_removed_allowlist_off' => 'Aadress :ip on eemaldatud — loend on välja lülitatud: töötajate keskkonda pääseb iga kehtiva sisselogimisega kasutaja.',
    'ip_removed' => 'Aadress :ip on lubatud aadresside loendist eemaldatud.',
    'session_already_ended' => 'See seanss on juba lõppenud.',
    'signed_out_of_device' => 'Seadmest :device on välja logitud.',
    'trusted_device_revoked' => 'Seadme usaldus on ära võetud.',
    'csv_title' => 'Vyrobeli konto eksport',
    'csv_name' => 'Nimi',
    'csv_email' => 'E-post',
    'csv_locale' => 'Keel',
    'csv_roles' => 'Rollid',
    'csv_activity' => 'Tegevus',
    'csv_when' => 'Millal',
    'csv_action' => 'Toiming',
    'range_this_year' => 'See aasta',
    'range_custom' => 'Kohandatud periood',
    'range_all_time' => 'Kogu aeg',
];
