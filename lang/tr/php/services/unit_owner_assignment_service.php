<?php

// I18N-01 — tr translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => ':owner için birim verilemez — daveti henüz kabul etmedi veya kaldırıldı. Yalnızca aktif bir mülk sahibine birim verilebilir.',
    'unit_already_owned' => '“:unit” birimi zaten :owner adına kayıtlı, bu nedenle burada başka birine verilemez. Yalnızca sahibi olmayan bir birim verilebilir.',
    'unit_owned_by_another' => 'başka bir mülk sahibi',
    'date_invalid' => 'Birimin verileceği geçerli bir tarih seçin.',
    'date_in_past' => 'Bugünü veya daha sonraki bir günü seçin. Geçmiş bir gün, zaten hesaplanmış kazançları değiştirir.',
    'date_too_far' => 'Önümüzdeki :days gün içinde bir gün seçin.',
    'no_agreement' => ':owner için :date tarihinde “:unit” birimini kapsayan bir sözleşme yok. Önce :owner için bu birimi :date tarihinden itibaren kapsayan bir sözleşme oluşturun, sonra birimi verin.',
    'stay_across_date' => '“:unit” biriminde :from ile :to arasında bir misafir konaklıyor; bu konaklama :date tarihini kapsıyor. :to tarihini veya daha sonraki bir günü seçin; böylece sizinle :owner arasında paylaşılan bir konaklama olmaz.',
    'future_stays_with_another_owner' => '“:unit” biriminin :date tarihinde veya sonrasında, başka bir mülk sahibi (:owners) için kazancı zaten hesaplanmış konaklamaları var. Bunlar o mülk sahibinde kalır; bu yüzden birim o günden itibaren verilemez.',
];
