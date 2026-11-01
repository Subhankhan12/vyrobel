<?php

// I18N-01 — tr translation of lang/en/php/actions/reservations/reverse_check_in.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => 'Konaklama “Onaylandı” durumuna döner ve oda aynı geceler için ayrılmış kalır.',
    'history_kept' => 'Check-in işleminin kendisi rezervasyon geçmişinde kalır. Bu işlem gerçekleşti ve kayıt da bunu gösterir — geri alma işlemi yanına eklenir.',
    'charges_kept' => '{1} Check-in yapıldıktan sonra işlenen :count kalem (toplam :amount) folyoda kalır. Check-in işlemini geri almak harcanan parayı geri getirmez — bu kalemler de hatalıysa folyodan kaldırın.|[0,*] Check-in yapıldıktan sonra işlenen :count kalem (toplam :amount) folyoda kalır. Check-in işlemini geri almak harcanan parayı geri getirmez — bu kalemler de hatalıysa folyodan kaldırın.',
    'online_check_in_kept' => 'Misafirin kendi online check-in işlemi tamamlanmış olarak kalır. Misafir bunu gerçekten doldurdu ve bu işlem misafirden tekrar doldurmasını istemez.',
    'already_reversed' => 'Bu check-in az önce zaten geri alındı — konaklama yeniden “Onaylandı” durumunda.',
    'refused_not_checked_in' => 'Yalnızca check-in yapılmış bir konaklamanın check-in işlemi geri alınabilir; bu konaklamanın durumu: :status.',
];
