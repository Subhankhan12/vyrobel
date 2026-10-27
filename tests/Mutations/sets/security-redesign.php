<?php

declare(strict_types=1);

// Mutation set security-redesign — both Security pages on the founder's design (2026-10-24): one score derived from real
// settings for the back office and the owner portal (App\Support\Security\SecurityScore), real sign-in problems from the
// sign-in log with "This was me", the owner page's password boxes and per-dialog errors, and login approval refused while
// email is not sent. Run with `php tests/Mutations/run.php --set=security-redesign`. ⚠️ The look is what a browser draws:
// these prove the rules, the words and the server's behaviour; the 🖥 walk proved the layout at 1,440 and 390 px, every
// dialog centred with its focus, two-factor on and off, and the problems card with both of its buttons.

$test = 'tests/Feature/SecurityRedesignTest.php';
$score = 'app/Support/Security/SecurityScore.php';
$overview = 'app/Support/Security/SecurityOverview.php';
$problems = 'app/Support/Security/SignInProblems.php';
$alerts = 'app/Http/Controllers/Auth/SecurityAlertController.php';
$ownerCtl = 'app/Http/Controllers/Auth/AccountSecurityController.php';   // Part 3: the toggle moved into the shared controller
$operatorView = 'resources/views/livewire/operator/operator-security.blade.php';
$ownerView = 'resources/views/livewire/owner/owner-security.blade.php';

return [
    // ── the score ─────────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'SX-M1',
        'name' => 'the weights no longer add up to 100',
        'file' => $score,
        'old' => "        'two_factor' => 30,\n",
        'new' => "        'two_factor' => 40,\n",
        'tests' => [['file' => $test, 'filter' => 'scores the six real protections out of 100']],
    ],
    [
        'id' => 'SX-M2',
        'name' => 'a protection the account cannot turn on counts against it',
        'file' => $score,
        'old' => "            if ((\$state[\$key]['offered'] ?? false) === true) {",
        'new' => '            if (true) {',
        'tests' => [['file' => $test, 'filter' => 'never counts a protection the account cannot turn on']],
    ],
    [
        'id' => 'SX-M3',
        'name' => 'the rounded shares no longer add up — the ring and the rows disagree',
        'file' => $score,
        'old' => '        foreach (array_slice($order, 0, $left) as $key) {',
        'new' => '        foreach ([] as $key) {',
        'tests' => [['file' => $test, 'filter' => 'never counts a protection the account cannot turn on']],
    ],
    [
        'id' => 'SX-M4',
        'name' => 'a weak account is called Strong',
        'file' => $score,
        'old' => "            'tier' => \$num >= 85 ? __('php/support/security/security_score.tier_strong')",
        'new' => "            'tier' => \$num >= 25 ? __('php/support/security/security_score.tier_strong')",
        'tests' => [['file' => $test, 'filter' => 'never counts a protection the account cannot turn on']],
    ],
    [
        'id' => 'SX-M5',
        'name' => 'the caption says everything is on whatever is off',
        'file' => $score,
        'old' => "            'caption' => \$next === null ? __('php/support/security/security_score.all_on_caption') : self::copy(\$next)['caption'],",
        'new' => "            'caption' => __('php/support/security/security_score.all_on_caption'),",
        'tests' => [['file' => $test, 'filter' => 'names the next real step in the caption']],
    ],
    [
        'id' => 'SX-M6',
        'name' => 'a protection that is off earns its points',
        'file' => $score,
        'old' => "            if (\$done) {\n                \$num += \$points[\$key];",
        'new' => "            if (true) {\n                \$num += \$points[\$key];",
        'tests' => [['file' => $test, 'filter' => 'never counts a protection the account cannot turn on']],
    ],
    // ── what both pages read ──────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'SX-M7',
        'name' => 'a passkey is counted though there is none',
        'file' => $overview,
        'old' => "                'passkey' => ['done' => \$passkeys !== [], 'offered' => true],",
        'new' => "                'passkey' => ['done' => true, 'offered' => true],",
        'tests' => [['file' => $test, 'filter' => 'lists each protection still off with its points']],
    ],
    [
        'id' => 'SX-M8',
        'name' => 'login approval is offered while email is thrown away',
        'file' => $overview,
        'old' => "            'available' => \$approvalHere && MailSafetyGuard::sendingNow(),",
        'new' => "            'available' => \$approvalHere,",
        'tests' => [
            ['file' => $test, 'filter' => 'gives the owner page the SAME score'],
            ['file' => $test, 'filter' => 'refuses to switch login approval ON while email is not being sent'],
        ],
    ],
    [
        'id' => 'SX-M9',
        'name' => 'used recovery codes are counted as left',
        'file' => $overview,
        'old' => "            'left' => count(array_filter(\$codes, fn (array \$c): bool => (\$c['used_at'] ?? null) === null)),",
        'new' => "            'left' => count(\$codes),",
        'tests' => [['file' => $test, 'filter' => 'says how many recovery codes are left']],
    ],
    // ── sign-in problems ──────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'SX-M10',
        'name' => 'a refusal at the platform console counts as one on this sign-in',
        'file' => $problems,
        'old' => "            ->where('guard', 'web')\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'warns about sign-ins refused on YOUR address']],
    ],
    [
        'id' => 'SX-M11',
        'name' => 'someone else’s refused sign-ins show on your page',
        'file' => $problems,
        'old' => "            ->where('identifier', \$email)\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'warns about sign-ins refused on YOUR address']],
    ],
    [
        'id' => 'SX-M12',
        'name' => 'refusals older than the 90 days the log keeps are counted',
        'file' => $problems,
        'old' => '        $since = CarbonImmutable::now()->subDays(LoginActivity::RETENTION_DAYS);',
        'new' => '        $since = CarbonImmutable::now()->subYears(5);',
        'tests' => [['file' => $test, 'filter' => 'warns about sign-ins refused on YOUR address']],
    ],
    [
        'id' => 'SX-M13',
        'name' => '"This was me" is ignored — the same warning comes back',
        'file' => $problems,
        'old' => '        if ($seen !== null && CarbonImmutable::parse($seen)->greaterThan($since)) {',
        'new' => '        if (false) {',
        'tests' => [['file' => $test, 'filter' => 'hides that warning for you only']],
    ],
    [
        'id' => 'SX-M14',
        'name' => '"This was me" stamps nothing',
        'file' => $alerts,
        'old' => "        \$user->forceFill(['security_alerts_seen_at' => now()])->save();\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'hides that warning for you only']],
    ],
    [
        'id' => 'SX-M15',
        'name' => '"This was me" leaves no audit row',
        'file' => $alerts,
        'old' => "        \$audit->record('user.security_alert_dismissed', \$user, null, ['refusals' => \$hidden]);\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'hides that warning for you only']],
    ],
    [
        'id' => 'SX-M16',
        'name' => 'the card shows with nothing to show',
        'file' => 'resources/views/components/security/problems.blade.php',
        'old' => "@if (\$problems['count'] > 0 && \$problems['latest'] !== null)",
        'new' => '@if (true)',
        'tests' => [['file' => $test, 'filter' => 'shows no warning when nothing happened']],
    ],
    // ── the owner page ────────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'SX-M17',
        'name' => 'login approval switches ON while every email is discarded',
        'file' => $ownerCtl,
        'old' => "        if (\$enabled && ! SecurityOverview::loginApproval(\$user, true)['available']) {",
        'new' => '        if (false) {',
        'tests' => [['file' => $test, 'filter' => 'refuses to switch login approval ON while email is not being sent']],
    ],
    [
        'id' => 'SX-M18',
        'name' => 'the refusal blocks switching it OFF too — the way out',
        'file' => $ownerCtl,
        'old' => "        if (\$enabled && ! SecurityOverview::loginApproval(\$user, true)['available']) {",
        'new' => "        if (! SecurityOverview::loginApproval(\$user, true)['available']) {",
        'tests' => [['file' => $test, 'filter' => 'refuses to switch login approval ON while email is not being sent']],
    ],
    [
        'id' => 'SX-M19',
        'name' => 'the page no longer says why login approval stays off',
        'file' => $ownerView,
        'old' => "                    @if (! \$approval['available'])\n",
        'new' => "                    @if (false)\n",
        'tests' => [['file' => $test, 'filter' => 'refuses to switch login approval ON while email is not being sent']],
    ],
    [
        'id' => 'SX-M20',
        'name' => 'an owner password box goes back to a bare input (the near-invisible box)',
        'file' => $ownerView,
        'old' => '<x-password-field name="current_password" id="vy-sx-so-pw"',
        'new' => '<input type="password" class="vy-input" name="current_password" id="vy-sx-so-pw"',
        'tests' => [['file' => $test, 'filter' => 'gives every owner password box the shared field']],
    ],
    [
        'id' => 'SX-M21',
        'name' => 'the owner sign-out dialog loses its mark — its error no longer reopens it (D-OWNER-SEC-ERRORS)',
        'file' => $ownerView,
        'old' => "                    <input type=\"hidden\" name=\"_form\" value=\"signout\">\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'shows a wrong owner password once']],
    ],
    [
        'id' => 'SX-M22',
        'name' => 'the owner change-password dialog shows every error on the page (D-OWNER-SEC-ERRORS)',
        'file' => $ownerView,
        'old' => "                        @if (\$errored === 'password' && \$errors->any())",
        'new' => '                        @if ($errors->any())',
        'tests' => [['file' => $test, 'filter' => 'shows a wrong owner password once']],
    ],
    // ── the shared look ───────────────────────────────────────────────────────────────────────────────────────────
    [
        'id' => 'SX-M23',
        'name' => 'the ring shows a fixed number, not the derived score',
        'file' => 'resources/views/components/security/hero.blade.php',
        'old' => "<span class=\"vy-sx-ring-n\">{{ \$score['num'] }}</span>",
        'new' => '<span class="vy-sx-ring-n">70</span>',
        'tests' => [['file' => $test, 'filter' => 'shows the back office its derived score']],
    ],
    [
        'id' => 'SX-M24',
        'name' => 'the back office stops reading the shared overview (its own copy of the score)',
        'file' => 'app/Livewire/Operator/OperatorSecurity.php',
        'old' => '        $overview = SecurityOverview::for($user, request(), true);',
        'new' => "        \$overview = SecurityOverview::for(\$user, request(), true);\n        \$overview['score']['num'] = 40;",
        'tests' => [['file' => $test, 'filter' => 'gives the owner page the SAME score']],
    ],
    [
        'id' => 'SX-M25',
        'name' => 'the dialog census stops finding the kit’s dialogs — they escape the dialog contract',
        'file' => 'tests/Support/dialog_helpers.php',
        'old' => "                || str_contains(\$attrs, 'vy-sx-scrim')            // the Security kit's dialogs (2026-10-24)\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'keeps every Security dialog under the dialog contract']],
    ],
];
