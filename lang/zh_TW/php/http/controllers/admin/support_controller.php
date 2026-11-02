<?php

// I18N-01 — zh_TW translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => '該連結已不存在。',
    'unlink_refused_only_way_in' => '已拒絕——這是此人唯一的登入方式（沒有密碼，也沒有其他第三方登入）。解除連結將使其永久無法登入。',
    'unlinked' => '已解除 :provider 的連結——已讀回確認：該連結已不存在。',
    'unlink_not_landed' => '解除連結未成功——該連結仍然存在。',
    'already_verified' => '已驗證。',
    'marked_verified' => '已標記為已驗證——已從資料庫讀回確認：:stored UTC。',
    'verify_not_landed' => '寫入未成功——email_verified_at 仍為空白。',
    'already_verified_nothing_to_resend' => '已驗證——無需重新寄送。',
    'verification_discarded' => '未送達——郵件設定為「:mailer」，驗證信已捨棄，沒有人收到任何內容。請以其他方式確認對方身分，再使用「標記電子郵件已驗證」。',
    'verification_handed' => '驗證信已交由郵件程式（:mailer）寄送。',
    'no_reset_link' => '未建立重設連結：:status',
    'reset_discarded' => '未送達——已建立重設連結，但郵件設定為「:mailer」，該電子郵件已捨棄，沒有人收到任何內容。',
    'reset_handed' => '密碼重設信已交由郵件程式（:mailer）寄送。',
    'verification_not_sent_undeliverable' => '未寄出——此電子郵件地址永遠無法收到郵件（:reason），因此驗證信未交由郵件程式寄送，沒有人收到任何內容。',
    'reset_not_sent_undeliverable' => '未寄出——此電子郵件地址永遠無法收到郵件（:reason），因此重設連結未交由郵件程式寄送，沒有人收到任何內容。',
];
