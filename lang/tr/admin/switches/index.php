<?php

// I18N-01 — tr translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => 'Acil kapatma anahtarları',
    'subtitle' => 'Yeni kayıtları veya tek bir sağlayıcıyla oturum açmayı, yeni sürüm yüklemeden durdurun. Her değişiklik bir gerekçeyle denetim günlüğüne kaydedilir.',
    'explainer' => 'Biri burada kapatmadıkça her anahtar <strong>açıktır</strong> — yeni bir sürüm yüklendikten hemen sonra da. Hiçbiri bu konsolu etkileyemez. Bir sağlayıcıyla <strong>oturum açmayı</strong> kapatmak, tek erişim yolu o sağlayıcı olan herkesi dışarıda bırakır; bu yüzden bu anahtarlar etkilenen hesap sayısını gösterir ve iki kez onay ister.',
    'state_on' => 'Açık',
    'state_off' => 'Kapalı',
    'last_changed_by' => 'Son değişiklik: :time UTC, :name tarafından.',
    'last_changed' => 'Son değişiklik: :time UTC.',
    'never_changed' => 'Hiç değiştirilmedi — varsayılan olarak açık.',
    'no_account_relies' => 'Şu anda yalnızca bu sağlayıcıya bağlı bir hesap yok — her hesabın bir şifresi veya başka bir erişim yolu da var.',
    'would_lock_out' => '{1} Bunu kapatmak <strong>:count</strong> hesabı dışarıda bırakır — şifresi yok ve tek erişim yolu bu sağlayıcı.|[0,*] Bunu kapatmak <strong>:count</strong> hesabı dışarıda bırakır — şifreleri yok ve tek erişim yolları bu sağlayıcı.',
    'reason' => 'Gerekçe',
    'why_turn_off' => 'Bunu neden kapatıyorsunuz?',
    'why_turn_on' => 'Bunu neden açıyorsunuz?',
    'confirm_lockout_zero' => 'Yeniden açılana kadar yalnızca bu yolla oturum açan herkesin dışarıda kalacağını anlıyorum.',
    'confirm_lockout' => '{1} Bunun, yeniden açılana kadar :count hesabı dışarıda bırakacağını anlıyorum.|[0,*] Bunun, yeniden açılana kadar :count hesabı dışarıda bırakacağını anlıyorum.',
    'turn_off' => 'Kapat',
    'turn_on' => 'Aç',
];
