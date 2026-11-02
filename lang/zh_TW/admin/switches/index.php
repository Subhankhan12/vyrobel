<?php

// I18N-01 — zh_TW translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => '緊急開關',
    'subtitle' => '無需部署即可停止新註冊，或停止透過某個第三方登入。每次切換都會連同原因記入稽核紀錄。',
    'explainer' => '除非有人在這裡關閉，否則每個開關都是<strong>開啟</strong>狀態——部署後也一樣。這些開關都不會影響此主控台。關閉某個第三方的<strong>登入</strong>，會讓僅能透過該第三方登入的人全部無法登入，因此這些開關會顯示受影響的帳號數，並要求確認兩次。',
    'state_on' => '開啟',
    'state_off' => '關閉',
    'last_changed_by' => '由 :name 於 :time UTC 最後變更。',
    'last_changed' => '最後變更於 :time UTC。',
    'never_changed' => '從未變更——預設為開啟。',
    'no_account_relies' => '目前沒有帳號僅依賴此第三方登入——每個帳號都另有密碼或其他登入方式。',
    'would_lock_out' => '{1}關閉後將使 <strong>:count</strong> 個帳號無法登入——這些帳號沒有密碼，且此第三方是唯一的登入方式。|[0,*]關閉後將使 <strong>:count</strong> 個帳號無法登入——這些帳號沒有密碼，且此第三方是唯一的登入方式。',
    'reason' => '原因',
    'why_turn_off' => '為什麼要關閉此開關？',
    'why_turn_on' => '為什麼要開啟此開關？',
    'confirm_lockout_zero' => '我了解從現在起直到重新開啟前，僅透過此方式登入的人都將無法登入。',
    'confirm_lockout' => '{1}我了解這會使 :count 個帳號無法登入，直到重新開啟為止。|[0,*]我了解這會使 :count 個帳號無法登入，直到重新開啟為止。',
    'turn_off' => '關閉',
    'turn_on' => '開啟',
];
