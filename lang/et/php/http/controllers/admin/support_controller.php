<?php

// I18N-01 — et translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => 'Seda ühendust enam ei ole.',
    'unlink_refused_only_way_in' => 'Keelatud — see on ainus viis, kuidas see isik saab sisse logida (parooli ega muud teenusepakkujat pole). Lahtiseostamine lukustaks ta jäädavalt välja.',
    'unlinked' => 'Teenusepakkuja :provider on lahti seotud — tagasi loetud: ühendust enam ei ole.',
    'unlink_not_landed' => 'Lahtiseostamine ei jõudnud andmebaasi — ühendus on endiselt olemas.',
    'already_verified' => 'Juba kinnitatud.',
    'marked_verified' => 'Märgitud kinnitatuks — andmebaasist tagasi loetud: :stored UTC.',
    'verify_not_landed' => 'Kirjutamine ei jõudnud andmebaasi — email_verified_at on endiselt tühi.',
    'already_verified_nothing_to_resend' => 'Juba kinnitatud — pole midagi uuesti saata.',
    'verification_discarded' => 'EI EDASTATUD — e-posti saatja on „:mailer“, seega visati kinnituskiri ära. Keegi ei saanud midagi. Kinnitage tema isik muul viisil ja kasutage seejärel toimingut „Märgi e-post kinnitatuks“.',
    'verification_handed' => 'Kinnituskiri anti e-posti saatjale üle (:mailer).',
    'no_reset_link' => 'Lähtestamislinki ei loodud: :status',
    'reset_discarded' => 'EI EDASTATUD — lähtestamislink loodi, kuid e-posti saatja on „:mailer“, seega visati e-kiri ära. Keegi ei saanud midagi.',
    'reset_handed' => 'Parooli lähtestamise e-kiri anti e-posti saatjale üle (:mailer).',
    'verification_not_sent_undeliverable' => 'EI SAADETUD — see aadress ei saa kunagi e-kirju vastu võtta (:reason), seega ei antud kinnituskirja e-posti saatjale üle. Keegi ei saanud midagi.',
    'reset_not_sent_undeliverable' => 'EI SAADETUD — see aadress ei saa kunagi e-kirju vastu võtta (:reason), seega ei antud lähtestamislinki e-posti saatjale üle. Keegi ei saanud midagi.',
];
