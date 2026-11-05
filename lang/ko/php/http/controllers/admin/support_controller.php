<?php

// I18N-01 — ko translation of lang/en/php/http/controllers/admin/support_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'connection_gone' => '해당 연결이 더 이상 존재하지 않습니다.',
    'unlink_refused_only_way_in' => '거부됨 — 이 사람이 로그인할 수 있는 유일한 수단입니다(비밀번호 없음, 다른 로그인 서비스 없음). 연결을 해제하면 영구적으로 로그인할 수 없게 됩니다.',
    'unlinked' => ':provider 연결이 해제되었습니다 — 다시 읽어 확인: 연결이 없어졌습니다.',
    'unlink_not_landed' => '연결 해제가 반영되지 않았습니다 — 연결이 아직 남아 있습니다.',
    'already_verified' => '이미 인증되었습니다.',
    'marked_verified' => '인증됨으로 표시했습니다 — 데이터베이스에서 다시 읽은 값: :stored UTC.',
    'verify_not_landed' => '기록이 반영되지 않았습니다 — email_verified_at이 아직 비어 있습니다.',
    'already_verified_nothing_to_resend' => '이미 인증되었습니다 — 재발송할 것이 없습니다.',
    'verification_discarded' => '전달되지 않음 — 메일 설정이 “:mailer”이므로 인증 이메일이 폐기되었습니다. 아무도 받지 못했습니다. 다른 방법으로 본인 여부를 확인한 다음 “이메일 인증 완료 처리”를 사용하세요.',
    'verification_handed' => '인증 이메일을 메일러(:mailer)로 넘겼습니다.',
    'no_reset_link' => '재설정 링크가 생성되지 않았습니다: :status',
    'reset_discarded' => '전달되지 않음 — 재설정 링크는 생성되었지만 메일 설정이 “:mailer”이므로 이메일이 폐기되었습니다. 아무도 받지 못했습니다.',
    'reset_handed' => '비밀번호 재설정 이메일을 메일러(:mailer)로 넘겼습니다.',
    'verification_not_sent_undeliverable' => '발송되지 않음 — 이 주소는 메일을 받을 수 없으므로(:reason) 인증 이메일을 메일러로 넘기지 않았습니다. 아무도 받지 못했습니다.',
    'reset_not_sent_undeliverable' => '발송되지 않음 — 이 주소는 메일을 받을 수 없으므로(:reason) 재설정 링크를 메일러로 넘기지 않았습니다. 아무도 받지 못했습니다.',
];
