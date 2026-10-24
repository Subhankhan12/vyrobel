<?php

declare(strict_types=1);

// Mutation set security-protections — Part 3 of the Security plan (2026-10-24): login alerts (the "Login from a new device" switch
// existed and sent nothing), the session timeout, when the password last changed, login approval on the back office and deciding a
// waiting sign-in from a device already signed in. Run with `php tests/Mutations/run.php --set=security-protections`. ⚠️ The 🖥 walk
// proved the rows, the switch and the timeout saving through the page and the "Approval needed" card with Deny; a real 15-minute
// sign-out was not waited out in a browser — the tests give the session its last-seen time, as a browser's session holds it.

$test = 'tests/Feature/SecurityProtectionsTest.php';
$device = 'app/Support/Security/NewDeviceSignIn.php';
$timeout = 'app/Http/Middleware/EnforceSessionTimeout.php';
$ctl = 'app/Http/Controllers/Auth/AccountSecurityController.php';
$approvals = 'app/Support/Auth/LoginApprovalService.php';

return [
    [
        'id' => 'SXP-M1',
        'name' => 'every sign-in is "from a new device" — the same device alerts again',
        'file' => $device,
        'old' => '        return ! $prior->contains(fn (?string $ua): bool => DeviceLabel::from($ua) === $device);',
        'new' => '        return true;',
        'tests' => [['file' => $test, 'filter' => 'alerts a person when their account signs in from a device it has not used']],
    ],
    [
        'id' => 'SXP-M2',
        'name' => 'an account’s very first sign-in alerts its owner',
        'file' => $device,
        'old' => "        if (\$prior->isEmpty()) {\n            return false;\n        }",
        'new' => "        if (\$prior->isEmpty()) {\n            return true;\n        }",
        'tests' => [['file' => $test, 'filter' => 'never alerts on an account’s very first sign-in']],
    ],
    [
        'id' => 'SXP-M3',
        'name' => 'the sign-in happening now counts as its own history — a new device never alerts',
        'file' => $device,
        'old' => "            ->where('occurred_at', '<', \$before->startOfSecond())",
        'new' => "            ->where('occurred_at', '<=', \$before)",
        'tests' => [['file' => $test, 'filter' => 'never treats the sign-in happening now as its own history']],
    ],
    [
        'id' => 'SXP-M4',
        'name' => 'a staff alert ignores its switch (it rides an always-on key)',
        'file' => 'app/Support/Security/NewDeviceSignIn.php',
        'old' => '        return $isOwner ? self::OWNER_KEY : self::OPERATOR_KEY;',
        'new' => "        return 'support_access';",
        'tests' => [['file' => $test, 'filter' => 'makes the “Login from a new device” switch real']],
    ],
    [
        'id' => 'SXP-M5',
        'name' => 'the Security switch writes nothing — the alert stays on',
        'file' => 'app/Services/NotificationPreferences.php',
        'old' => "        foreach (self::CHANNELS as \$channel) {\n            NotificationPreference::query()->updateOrCreate(\n                ['user_id' => \$user->getKey(), 'event_key' => \$eventKey, 'channel' => \$channel],\n                ['enabled' => \$on],\n            );\n        }\n    }\n\n    /**\n     * The current on/off state",
        'new' => "    }\n\n    /**\n     * The current on/off state",
        'tests' => [['file' => $test, 'filter' => 'makes the “Login from a new device” switch real']],
    ],
    [
        'id' => 'SXP-M6',
        'name' => 'an owner’s always-on sign-in alert is offered as a switch',
        'file' => 'app/Support/Security/NewDeviceSignIn.php',
        'old' => '        $isOwner = app(TenantContext::class)->runAsSuperAdmin(',
        'new' => '        $isOwner = false && app(TenantContext::class)->runAsSuperAdmin(',
        'tests' => [['file' => $test, 'filter' => 'says an owner’s sign-in alerts are always on']],
    ],
    [
        'id' => 'SXP-M7',
        'name' => 'the chosen timeout is never enforced',
        'file' => $timeout,
        'old' => '            if (is_int($last) && time() - $last > $minutes * 60) {',
        'new' => '            if (false) {',
        'tests' => [['file' => $test, 'filter' => 'signs a person out after the inactivity THEY chose']],
    ],
    [
        'id' => 'SXP-M8',
        'name' => 'a page refreshing itself counts as the person — an open tab never times out',
        'file' => $timeout,
        'old' => '            if (! is_int($last) || ! SessionTimeout::isBackground($request)) {',
        'new' => '            if (true) {',
        'tests' => [['file' => $test, 'filter' => 'never counts a page refreshing itself as the person']],
    ],
    [
        'id' => 'SXP-M9',
        'name' => 'any number is accepted as a timeout',
        'file' => $ctl,
        'old' => "        \$data = \$request->validate(['minutes' => ['nullable', Rule::in(array_map('strval', SessionTimeout::OPTIONS))]]);",
        'new' => "        \$data = \$request->validate(['minutes' => ['nullable']]);",
        'tests' => [['file' => $test, 'filter' => 'refuses a timeout that is not one of the choices']],
    ],
    [
        'id' => 'SXP-M10',
        'name' => 'a password change is never dated',
        'file' => 'app/Models/User.php',
        'old' => "            if (\$user->isDirty('password') && \$user->password !== null) {\n                \$user->password_changed_at = now();\n            }",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'records when a password changes']],
    ],
    [
        'id' => 'SXP-M11',
        'name' => 'someone else’s waiting sign-in can be decided from your page',
        'file' => $approvals,
        'old' => "    public function pending(User \$user, string \$approvalId): ?LoginApproval\n    {\n        \$approval = LoginApproval::query()\n            ->where('user_id', (string) \$user->getKey())\n",
        'new' => "    public function pending(User \$user, string \$approvalId): ?LoginApproval\n    {\n        \$approval = LoginApproval::query()\n",
        'tests' => [['file' => $test, 'filter' => 'lets a person approve a waiting sign-in from a device where they are signed in']],
    ],
    [
        'id' => 'SXP-M12',
        'name' => 'Deny approves the sign-in',
        'file' => $approvals,
        'old' => "        \$approval->forceFill(\$approve ? ['approved_at' => Carbon::now()] : ['consumed_at' => Carbon::now()])->save();",
        'new' => "        \$approval->forceFill(['approved_at' => Carbon::now()])->save();",
        'tests' => [['file' => $test, 'filter' => 'closes a denied sign-in for good']],
    ],
    [
        'id' => 'SXP-M13',
        'name' => 'the back office switches login approval ON while email is thrown away',
        'file' => $ctl,
        'old' => "        if (\$enabled && ! SecurityOverview::loginApproval(\$user, true)['available']) {",
        'new' => '        if (false) {',
        'tests' => [['file' => $test, 'filter' => 'gives the back office login approval too']],
    ],
    [
        'id' => 'SXP-M14',
        'name' => 'the owner page offers a switch for alerts that cannot be switched',
        'file' => 'resources/views/components/security/protection-rows.blade.php',
        'old' => "        @unless (\$alerts['always_on'])\n",
        'new' => "        @unless (false)\n",
        'tests' => [['file' => $test, 'filter' => 'says an owner’s sign-in alerts are always on']],
    ],
];
