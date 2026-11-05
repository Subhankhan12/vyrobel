<?php

// I18N-01 — zh_CN translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => '该关联已不存在。',
    'unlink_refused_only_way_in' => '已拒绝——这是此人唯一的登录方式（没有密码，也没有其他第三方登录方式）。解除关联会使其永久无法登录。',
    'unlinked' => '已解除 :provider 关联——读回确认：该关联已不存在。',
    'unlink_not_landed' => '解除关联未生效——该关联仍然存在。',
    'already_verified' => '此前已验证。',
    'marked_verified' => '已标记为已验证——从数据库读回：:stored UTC。',
    'verify_not_landed' => '写入未生效——email_verified_at 仍为空。',
    'already_verified_nothing_to_resend' => '此前已验证——无需重新发送。',
    'verification_discarded' => '未投递——邮件发送方式为“:mailer”，因此验证邮件已被丢弃。没有任何人收到邮件。请通过其他方式确认其身份，然后使用“标记邮箱已验证”。',
    'verification_handed' => '验证邮件已交给邮件发送程序（:mailer）。',
    'no_reset_link' => '未创建重置链接：:status',
    'reset_discarded' => '未投递——已创建重置链接，但邮件发送方式为“:mailer”，因此邮件已被丢弃。没有任何人收到邮件。',
    'reset_handed' => '密码重置邮件已交给邮件发送程序（:mailer）。',
    'verification_not_sent_undeliverable' => '未发送——此地址永远无法接收邮件（:reason），因此验证邮件未交给邮件发送程序。没有任何人收到邮件。',
    'reset_not_sent_undeliverable' => '未发送——此地址永远无法接收邮件（:reason），因此重置链接未交给邮件发送程序。没有任何人收到邮件。',
];
