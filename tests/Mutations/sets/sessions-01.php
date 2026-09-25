<?php

declare(strict_types=1);

// Mutation set sessions-01 — SESSIONS 01 (L16): each layer that makes a sign-out mean signed out, removed one at a
// time. Run with `php tests/Mutations/run.php --set=sessions-01`. See tests/Mutations/run.php.

return [
    [
        'id' => 'S01-M1',
        'name' => 'the password-change check removed from the web group',
        'file' => 'bootstrap/app.php',
        'old' => '            EndSessionsOnPasswordChange::class,
',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/SignOutMeansSignedOutTest.php', 'filter' => 'web: A changes the password'], ['file' => 'tests/Feature/SignOutMeansSignedOutTest.php', 'filter' => 'web: a password RESET']],
    ],
    [
        'id' => 'S01-M2',
        'name' => 'a guest password change stops cycling the remember token',
        'file' => 'app/Models/Guest.php',
        'old' => '            \'password\' => $plainPassword,
            \'remember_token\' => Str::random(60),
',
        'new' => '            \'password\' => $plainPassword,
',
        'tests' => [['file' => 'tests/Feature/SignOutMeansSignedOutTest.php', 'filter' => 'remember me']],
    ],
    [
        'id' => 'S01-M3',
        'name' => '"sign out others" deletes no rows',
        'file' => 'app/Support/Security/ActiveSessions.php',
        'old' => '        $deleted = self::terminateFor((string) $user->getKey(), $request->session()->getId());',
        'new' => '        $deleted = 0;',
        'tests' => [['file' => 'tests/Feature/SignOutMeansSignedOutTest.php', 'filter' => 'also deletes the other session ROWS, and never']],
    ],
    [
        'id' => 'S01-M4',
        'name' => '"sign out others" no longer re-hashes (layer 2)',
        'file' => 'app/Support/Security/ActiveSessions.php',
        'old' => '        $guard->logoutOtherDevices($currentPassword);
',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/SignOutMeansSignedOutTest.php', 'filter' => 'whatever the session driver'], ['file' => 'tests/Feature/SignOutMeansSignedOutTest.php', 'filter' => 'owner: .sign out all other sessions']],
    ],
    [
        'id' => 'S01-M5',
        'name' => 'force sign-out on yourself ends every session again',
        'file' => 'app/Http/Controllers/Admin/AdminUserController.php',
        'old' => '        if ($target->is($actor)) {',
        'new' => '        if (false) {',
        'tests' => [['file' => 'tests/Feature/SignOutMeansSignedOutTest.php', 'filter' => 'YOURSELF']],
    ],
    [
        'id' => 'S01-M6',
        'name' => 'a guest password change deletes no other rows',
        'file' => 'app/Models/Guest.php',
        'old' => '        ActiveSessions::terminateFor((string) $this->getKey(), $keepSessionId);
',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/SignOutMeansSignedOutTest.php', 'filter' => 'ROWS of that guest']],
    ],
    [
        'id' => 'S01-M7',
        'name' => 'break-glass leaves the viewed user as the default guard',
        'file' => 'app/Http/Middleware/EnterBreakGlassSession.php',
        'old' => '        Auth::shouldUse(\'admin\');
        app()->forgetInstance(\'auth.driver\');
',
        'new' => '',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php', 'filter' => 'SESSIONS 01']],
    ],
    [
        'id' => 'S01-M8',
        'name' => 'the password-change check no longer stands aside for break-glass',
        'file' => 'app/Http/Middleware/EndSessionsOnPasswordChange.php',
        'old' => '        if ($request->attributes->has(BreakGlass::ATTRIBUTE)) {',
        'new' => '        if (false) {',
        'tests' => [['file' => 'tests/Feature/Admin/PlatformBreakGlassTest.php', 'filter' => 'SESSIONS 01']],
    ],
];
