<?php

// I18N-01 — ko translation of lang/en/php/support/privacy/sub_processors.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'agreement_none' => '등록된 계약 없음',
    'state_switched_on' => '활성 — 켜져 있음',
    'state_not_active' => '비활성',
    'state_configured' => '구성됨',
    'hostinger_hosting' => [
        'name' => 'Hostinger(호스팅)',
        'purpose' => '애플리케이션과 데이터베이스를 실행합니다.',
        'data' => '제품이 저장하는 모든 데이터 — 모든 계정, 호텔, 고객 기록.',
        'state' => '활성 — 운영 서버',
    ],
    'hostinger_cdn' => [
        'name' => 'Hostinger CDN',
        'purpose' => '호스트 앞단에서 공개 사이트를 전달하고 캐시합니다.',
        'data' => '통과하는 모든 요청: IP 주소, URL, 전송 중인 쿠키.',
        'state' => '활성 — 모든 응답에서 확인됨',
    ],
    'google' => [
        'name' => 'Google(로그인)',
        'purpose' => 'Google 계정으로 로그인과 회원가입을 처리합니다.',
        'data' => '사용자의 Google 신원 정보: 계정 ID, 이메일, 이름.',
    ],
    'x' => [
        'name' => 'X(로그인)',
        'purpose' => 'X 계정으로 로그인과 회원가입을 처리합니다.',
        'data' => '사용자의 X 신원 정보: 계정 ID, 핸들, 이름.',
    ],
    'hostinger_email' => [
        'name' => 'Hostinger(이메일)',
        'purpose' => 'vyrobel.com 메일함에서 모든 자동 발송 이메일을 발송합니다.',
        'data' => '수신자 주소와 메시지 내용 — 고객 이름, 예약 번호, 날짜.',
        'state_active' => '활성 — 현재 메일러',
        'state_inactive' => '비활성 — 메일 설정: “:mailer”',
    ],
    'stripe' => [
        'name' => 'Stripe(결제 및 청구)',
        'purpose' => '구독 청구, 고객 카드 결제, 소유주 지급(Connect).',
        'data' => '청구 연락처, 금액, 예약 번호; 소유주 지급의 경우 KYC용 신원 정보.',
        'state_fake' => '비활성 — 가짜 클라이언트가 바인딩되어 있음',
    ],
    'anthropic' => [
        'name' => 'Anthropic(AI 어시스턴트)',
        'purpose' => 'AI 어시스턴트에서 질문에 답합니다.',
        'data' => '질문 내용, 그리고 질문과 함께 전송되는 컨텍스트.',
        'state_off' => '비활성 — 가짜 클라이언트 또는 키 없음',
    ],
    'channex' => [
        'name' => 'Channex(채널 매니저)',
        'purpose' => '요금, 잔여 객실, 예약을 OTA와 동기화합니다.',
        'data' => '채널과 주고받는 예약 및 고객 정보.',
        'state_off' => '연결되지 않음',
    ],
];
