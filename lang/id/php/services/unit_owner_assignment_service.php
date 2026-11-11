<?php

// I18N-01 — id translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => ':owner tidak dapat diberi unit — belum menerima undangan, atau sudah diarsipkan. Hanya pemilik yang aktif yang dapat diberi unit.',
    'unit_already_owned' => '“:unit” sudah menjadi milik :owner, sehingga tidak dapat diberikan kepada orang lain di sini. Hanya unit yang belum memiliki pemilik yang dapat diberikan.',
    'unit_owned_by_another' => 'pemilik lain',
    'date_invalid' => 'Pilih tanggal yang valid sebagai awal pemberian unit.',
    'date_in_past' => 'Pilih hari ini atau hari berikutnya. Tanggal yang sudah lewat akan mengubah penghasilan yang sudah dihitung.',
    'date_too_far' => 'Pilih tanggal dalam :days hari ke depan.',
    'no_agreement' => ':owner belum memiliki perjanjian yang mencakup “:unit” pada :date. Buatkan perjanjian untuk :owner yang mencakup unit ini mulai :date terlebih dahulu, lalu berikan unit kepadanya.',
    'stay_across_date' => 'Ada tamu yang menginap di “:unit” dari :from hingga :to, yang melewati :date. Pilih :to atau hari sesudahnya, agar tidak ada masa inap yang dibagi antara Anda dan :owner.',
    'future_stays_with_another_owner' => '“:unit” memiliki masa inap pada atau setelah :date yang sudah dihitung untuk pemilik lain (:owners). Masa inap tersebut tetap menjadi milik mereka, sehingga unit tidak dapat diberikan mulai hari itu.',
];
