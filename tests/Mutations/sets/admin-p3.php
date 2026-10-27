<?php

declare(strict_types=1);

// Mutation set admin-p3 — run with `php tests/Mutations/run.php --set=admin-p3`. Each row: the exact text a guard lives in (`old`),
// what it becomes with the guard removed (`new`), and the test aimed at it. See tests/Mutations/run.php.

return [
    [
        'id' => 'P3-B1',
        'name' => 'layer-1 middleware unregistered',
        'file' => 'bootstrap/app.php',
        'old' => '            RefuseWritesDuringBreakGlass::class,
        ]);',
        'new' => '        ]);',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B2',
        'name' => 'layer-1 treats POST as safe',
        'file' => 'app/Http/Middleware/RefuseWritesDuringBreakGlass.php',
        'old' => 'if ($request->isMethodSafe() || ',
        'new' => 'if (true || ',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B3',
        'name' => 'layer-2 read-only wrap removed',
        'file' => 'app/Http/Middleware/EnterBreakGlassSession.php',
        'old' => '$response = PlatformWriteGuard::readOnly(fn () => $next($request));',
        'new' => '$response = $next($request);',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B4',
        'name' => 'priority fix removed',
        'file' => 'bootstrap/app.php',
        'old' => '        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: EnterBreakGlassSession::class,
        );
',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B5',
        'name' => 'no expiry check',
        'file' => 'app/Support/Platform/BreakGlass.php',
        'old' => '        if (! $session->expires_at->isFuture()) {',
        'new' => '        if (false) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B6',
        'name' => 'ended_at = now at expiry',
        'file' => 'app/Support/Platform/BreakGlass.php',
        'old' => '$endedAt = $reason === \'expired\' ? $session->expires_at : now();',
        'new' => '$endedAt = now();',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B7',
        'name' => 'tenant never told',
        'file' => 'app/Support/Platform/BreakGlass.php',
        'old' => '        $this->notifyTenant($session);
    }',
        'new' => '    }',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B8',
        'name' => 'step-up accepts any code',
        'file' => 'app/Http/Controllers/Admin/BreakGlassController.php',
        'old' => '|| ! $this->twoFactor->verify((string) $admin->two_factor_secret, $totp)) {',
        'new' => ') {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B9',
        'name' => 'unverified user may be borrowed',
        'file' => 'app/Support/Platform/BreakGlass.php',
        'old' => '                ->whereNotNull(\'users.email_verified_at\')
                ->orderBy(\'organization_user.created_at\')',
        'new' => '                ->orderBy(\'organization_user.created_at\')',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B10',
        'name' => 'enabled() always true',
        'file' => 'app/Support/Platform/BreakGlass.php',
        'old' => '        return (bool) config(\'platform.break_glass.enabled\', false);',
        'new' => '        return true;',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B11',
        'name' => 'controller never 404s',
        'file' => 'app/Http/Controllers/Admin/BreakGlassController.php',
        'old' => '        if (! BreakGlass::enabled()) {
            throw new NotFoundHttpException;',
        'new' => '        if (false) {
            throw new NotFoundHttpException;',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B12',
        'name' => 'no banner',
        'file' => 'app/Http/Middleware/EnterBreakGlassSession.php',
        'old' => '        return $this->withBanner($response, $session, $organization, $viewing);',
        'new' => '        return $response;',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B13',
        'name' => 'pages not audited',
        'file' => 'app/Http/Middleware/EnterBreakGlassSession.php',
        'old' => '        $this->audit->record(\'platform.break_glass.page\',',
        'new' => '        false && $this->audit->record(\'platform.break_glass.page\',',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B14',
        'name' => 'key hash not checked',
        'file' => 'app/Support/Platform/BreakGlass.php',
        'old' => '            && hash_equals($session->session_key_hash, hash(\'sha256\', $stash[\'key\']));',
        'new' => ';',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B15',
        'name' => 'may mix with a web login',
        'file' => 'app/Http/Controllers/Admin/BreakGlassController.php',
        'old' => '        if (Auth::guard(\'web\')->check()) {',
        'new' => '        if (false) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B16',
        'name' => 'suspended org not viewable',
        'file' => 'app/Http/Middleware/RefuseSuspendedOrganization.php',
        'old' => 'if ($request->routeIs(self::SWITCH_ROUTE) || $request->attributes->has(BreakGlass::ATTRIBUTE)) {',
        'new' => 'if ($request->routeIs(self::SWITCH_ROUTE)) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B17',
        'name' => 'wrapped refusal not unwrapped',
        'file' => 'app/Support/Platform/BreakGlass.php',
        'old' => '        for ($cur = $e; $cur !== null; $cur = $cur->getPrevious()) {',
        'new' => '        for ($cur = $e; $cur !== null; $cur = null) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B18',
        'name' => 'unlink ignores the floor',
        'file' => 'app/Http/Controllers/Admin/SupportController.php',
        'old' => '        if (! SignInCredentials::mayUnlink($account, (string) $row->getKey())) {',
        'new' => '        if (false) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformSupportToolsTest.php']],
    ],
    [
        'id' => 'P3-B19',
        'name' => 'verify uses fill (silently dropped)',
        'file' => 'app/Http/Controllers/Admin/SupportController.php',
        'old' => 'fn () => $account->forceFill([\'email_verified_at\' => now()])->save(),',
        'new' => 'fn () => $account->fill([\'email_verified_at\' => now()])->save(),',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformSupportToolsTest.php']],
    ],
    [
        'id' => 'P3-B20',
        'name' => 'discarding mail reported as sent',
        'file' => 'app/Http/Controllers/Admin/SupportController.php',
        // ⚠️ re-anchored TONIGHT 01: the skip-list refusal now sits between this line and the mutate() call.
        'old' => '        $discards = MailDelivery::discards();
        if (! $discards && ($refused = $this->refusedUndeliverable($account, \'verification\')) !== null) {',
        'new' => '        $discards = false;
        if (! $discards && ($refused = $this->refusedUndeliverable($account, \'verification\')) !== null) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformSupportToolsTest.php']],
    ],
    [
        'id' => 'P3-B21',
        'name' => 'lookup ledger stores the email',
        'file' => 'app/Http/Controllers/Admin/SupportController.php',
        'old' => '}, [\'by\' => $userId !== \'\' ? \'id\' : \'email\']);',
        'new' => '}, [\'by\' => $userId !== \'\' ? \'id\' : \'email\', \'email\' => $email]);',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformSupportToolsTest.php']],
    ],
    [
        'id' => 'P3-B22',
        'name' => 'support opened to every role',
        'file' => 'routes/web.php',
        'old' => '            Route::middleware($can(PlatformAdminRole::SUPPORT_USE))->group(function () {
                Route::get(\'support\',',
        'new' => '            Route::middleware($can(PlatformAdminRole::CONSOLE_VIEW))->group(function () {
                Route::get(\'support\',',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformSupportToolsTest.php']],
    ],
    [
        'id' => 'P3-B23',
        'name' => 'notice can be switched off',
        'file' => 'app/Services/NotificationPreferences.php',
        'old' => '\'description\' => \'Vyrobel support viewed your workspace, read-only — always on\', \'group\' => \'System\', \'audience\' => \'operator\', \'always_on\' => true,',
        'new' => '\'description\' => \'Vyrobel support viewed your workspace, read-only — always on\', \'group\' => \'System\', \'audience\' => \'operator\', \'always_on\' => false,',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
    [
        'id' => 'P3-B24',
        'name' => 'layer-1 ordering rule removed',
        'file' => 'bootstrap/app.php',
        'old' => '        $middleware->prependToPriorityList(
            before: EnterBreakGlassSession::class,
            prepend: RefuseWritesDuringBreakGlass::class,
        );
',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php']],
    ],
];
