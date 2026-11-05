<?php

// I18N-01 — zh_CN translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => '紧急开关',
    'subtitle' => '无需部署即可停止新注册，或停止通过某个第三方登录方式登录。每次切换都会连同原因记入审计日志。',
    'explainer' => '所有开关默认均为<strong>开启</strong>，除非有人在此关闭——部署后也是如此。它们都不会影响此控制台。关闭某个第三方登录方式的<strong>登录</strong>，会让唯一登录途径为该方式的所有人都无法登录，因此这些开关会显示受影响的账号数并要求二次确认。',
    'state_on' => '已开启',
    'state_off' => '已关闭',
    'last_changed_by' => '最近更改：:time UTC，操作者：:name。',
    'last_changed' => '最近更改：:time UTC。',
    'never_changed' => '从未更改——默认开启。',
    'no_account_relies' => '目前没有账号仅依赖此第三方登录方式——每个账号都有密码或其他登录途径。',
    'would_lock_out' => '{1}关闭后将导致 <strong>:count</strong> 个账号无法登录——这些账号没有密码，此第三方登录方式是其唯一登录途径。|[0,*]关闭后将导致 <strong>:count</strong> 个账号无法登录——这些账号没有密码，此第三方登录方式是其唯一登录途径。',
    'reason' => '原因',
    'why_turn_off' => '为什么要关闭此开关？',
    'why_turn_on' => '为什么要开启此开关？',
    'confirm_lockout_zero' => '我了解：从现在起到重新开启之前，所有仅通过此方式登录的人都将无法登录。',
    'confirm_lockout' => '{1}我了解：在重新开启之前，这将导致 :count 个账号无法登录。|[0,*]我了解：在重新开启之前，这将导致 :count 个账号无法登录。',
    'turn_off' => '关闭',
    'turn_on' => '开启',
];
