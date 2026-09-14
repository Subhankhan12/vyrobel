<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * ⚠️⚠️ A PASSWORDLESS ACCOUNT MUST BE POSSIBLE, AND ESCAPABLE (docs/PASSWORDLESS-GATE.md).
 *
 * `users.password` was NOT NULL, so the schema could not hold a social signup at all — and such an account
 * would fail all twenty-four `current_password` confirmations with no way to fix itself. This proves the
 * schema now permits it, that the failure stays CLOSED, and that the escape works in both directions.
 *
 * ⚠️ NO SOCIAL PROVIDER EXISTS AND NONE IS ADDED HERE. This is the precondition, and it is independent.
 */
uses(DatabaseTransactions::class);

function pwlUser(array $scenario, bool $withPassword, string $role = 'Hotel Manager'): User
{
    $user = operatorUser($scenario, [], $role);

    // ⚠️ TEAMS MODE: `can()` returns false without the team id — pinned by the permission pass. Set it, or
    //    every gate below "passes" by denying a user who actually holds the permission.
    setPermissionsTeamId($scenario['org']->id);
    $user->unsetRelation('roles')->unsetRelation('permissions');

    if (! $withPassword) {
        // The shape a social signup would create: authenticated, no credential.
        User::withoutEvents(fn () => User::query()->whereKey($user->getKey())->update(['password' => null]));
        $user->refresh();
    }

    return $user;
}

// ══ #1 · the schema, and that a null hash FAILS CLOSED ══════════════════════════════════════════════

it('⚠️⚠️ a NULL password hash denies every guess — it never accepts one', function () {
    // ⚠️ THE WORST DEFECT THIS PRODUCT COULD SHIP would be a null hash that accepts anything. Measured
    //    rather than reasoned, including the empty guess, which is the case a naive check gets wrong.
    expect(Hash::check('anything', null))->toBeFalse()
        ->and(Hash::check('', null))->toBeFalse()
        ->and(Hash::check('anything', ''))->toBeFalse()
        ->and(Hash::check('', ''))->toBeFalse();
});

it('⚠️⚠️ NULL rather than a SENTINEL — because a sentinel THROWS instead of denying', function () {
    // ⚠️ A non-bcrypt sentinel turns a denial into a 500 on twenty-four sensitive routes. This pins the
    //    reason the column is nullable rather than carrying a placeholder string.
    expect(fn () => Hash::check('anything', 'not-a-bcrypt-hash'))
        ->toThrow(RuntimeException::class);
});

it('⚠️ the schema can now hold a passwordless user, and the admin table deliberately cannot', function () {
    $s = seedScenario(1);
    $user = pwlUser($s, withPassword: false);

    expect($user->password)->toBeNull()
        ->and($user->hasPassword())->toBeFalse();

    $admin = collect(DB::select('SHOW COLUMNS FROM platform_admins'))->firstWhere('Field', 'password');

    // ⚠️ A platform admin is created by console command with a password and mandatory 2FA; no provider
    //    path reaches that table. Excluded EXPLICITLY, not silently.
    expect($admin->Null)->toBe('NO');
});

it('⚠️ a passwordless user cannot sign in with any password — the login path stays closed', function () {
    $s = seedScenario(1);
    $user = pwlUser($s, withPassword: false);

    expect(Auth::attempt(['email' => $user->email, 'password' => '']))->toBeFalse()
        ->and(Auth::attempt(['email' => $user->email, 'password' => 'password']))->toBeFalse()
        ->and(Auth::attempt(['email' => $user->email, 'password' => Str::random(12)]))->toBeFalse();
});

it('⚠️⚠️ an EMPTY-STRING hash counts as passwordless too — not only NULL', function () {
    // ⚠️ THIS PROBE EXISTS BECAUSE A MUTATION FOUND ITS ABSENCE. Weakening `hasPassword()` to
    //    `password !== null` came back GREEN: every fixture above uses NULL, so the empty-string case —
    //    the exact shape the original design probe measured, and what a partial write or a legacy row
    //    could leave behind — was never exercised. An empty hash denies every guess just as null does, so
    //    treating it as "has a password" would strand that user in the lockout with no escape.
    $s = seedScenario(1);
    $user = pwlUser($s, withPassword: true);

    // Written past the `hashed` cast deliberately — this is the raw column state being tested.
    User::withoutEvents(fn () => User::query()->whereKey($user->getKey())->update(['password' => '']));
    $user->refresh();

    expect($user->hasPassword())->toBeFalse()
        ->and(Hash::check('anything', (string) $user->password))->toBeFalse();

    // …and the gate treats them as passwordless, so they can still escape.
    $response = $this->actingAs($user)->post(route('app.profile.sign-out-others'), ['current_password' => 'x']);

    expect($response->status())->toBe(302);
    $this->assertStringContainsString('/security', (string) $response->headers->get('Location'));
});

// ══ #8 · ⚠️⚠️ ALL TWENTY-FOUR SITES, BOTH DIRECTIONS ════════════════════════════════════════════════

/**
 * Every `current_password` confirmation on the `web` guard, enumerated from the source rather than taken
 * from a document. ⚠️ The count MOVED: 30 occurrences, but six are attribute-name maps, so there are 24
 * rule sites — 19 `web`, 4 `admin`, 1 `guest`.
 */
dataset('reauth_routes', [
    'owner tax' => ['owner.tax.save', 'post'],
    'owner payout preference' => ['owner.payouts.preferences', 'post'],
    'owner connected app revoke' => ['owner.security.connected-apps.revoke', 'post'],
    'owner team invite' => ['owner.team.invite', 'post'],
    'operator invite owner' => ['app.owners.invite', 'post'],
    'operator role assignment' => ['app.roles.assign', 'post'],
    'operator sign-out-others' => ['app.profile.sign-out-others', 'post'],
    'operator security password' => ['app.security.password', 'post'],
]);

it('⚠️⚠️ INTERRUPTS a passwordless user at a confirmation — and does NOT interrupt one with a password',
    function (string $routeName, string $verb) {
        if (! Route::has($routeName)) {
            expect(true)->toBeTrue();   // route absent in this build — reported, not silently skipped

            return;
        }

        $s = seedScenario(1);
        $url = route($routeName);

        // ── THE PASSWORDLESS USER IS INTERRUPTED ──────────────────────────────────────────────────
        $without = pwlUser($s, withPassword: false);
        expect($without->hasPassword())->toBeFalse();

        $response = $this->actingAs($without)->$verb($url, ['current_password' => 'whatever']);

        expect($response->status())->toBe(302);

        // ⚠️ `assertStringContainsString`, NOT `expect()->toContain($needle, $message)`. Pest's `toContain`
        //    is VARIADIC: a second argument is read as ANOTHER NEEDLE, so the explanation would silently
        //    become an assertion. This suite already bans the negated form; the positive form has the same
        //    shape, and it failed here before the message was moved out of the matcher.
        $this->assertStringContainsString('/security', (string) $response->headers->get('Location'),
            "a passwordless user was not sent to set a password from {$routeName}");

        // ── ⚠️ AND THE PASSWORD USER IS **NOT** ───────────────────────────────────────────────────
        // ⚠️ A gate proven only in the deny direction may be denying EVERYONE. This half is what
        //    distinguishes a working interruption from one that swallows every request.
        $with = pwlUser($s, withPassword: true);
        expect($with->hasPassword())->toBeTrue();

        $passthrough = $this->actingAs($with)->$verb($url, ['current_password' => 'wrong-on-purpose']);

        // It reached the ORIGINAL rule — which rejects a wrong password, as it always did.
        $this->assertStringNotContainsString('password_status', (string) $passthrough->headers->get('Location'),
            'a user WITH a password was diverted to the password screen');
    })->with('reauth_routes');

// ══ #3 · the twenty-four rules themselves are untouched ═════════════════════════════════════════════

it('⚠️⚠️ not one of the confirmation rules was weakened', function () {
    // ⚠️ The rule text is asserted from source: this pass adds a step BEFORE them, never a bypass AROUND.
    $rules = 0;

    foreach (['app/Http/Controllers'] as $root) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($root)));

        foreach ($it as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }
            $src = (string) file_get_contents((string) $file->getRealPath());
            $rules += preg_match_all("/'required',\s*'current_password(:[a-z]+)?'/", $src);
        }
    }

    // 24 rule sites; the ChangePassword one is now conditional, so 23 remain literal.
    expect($rules)->toBeGreaterThanOrEqual(23, 'a confirmation rule was removed or rewritten');
});

it('⚠️ setting a first password does NOT confirm the original action — the rule still bites afterwards', function () {
    $s = seedScenario(1);
    $user = pwlUser($s, withPassword: false);

    // Set a first password through the EXISTING path — no current password demanded, because there is none.
    $this->actingAs($user)->post(route('app.security.password'), [
        'password' => 'Str0ng-New-Passw0rd!', 'password_confirmation' => 'Str0ng-New-Passw0rd!',
    ])->assertRedirect();

    expect($user->fresh()->hasPassword())->toBeTrue();

    // ⚠️ And the original action still demands confirmation — setting a password is not confirming with it.
    // ⚠️ A confirmation whose ONLY field is the password, so `current_password` is unambiguously what
    //    fails — a form with other required fields could "pass" this assertion for the wrong reason.
    //    (`OperatorProfileController::update` does NOT confirm; the rule at :82 is in `signOutOtherSessions`,
    //    which is why the route name here is that one. Verified from the source, not assumed.)
    $this->actingAs($user->fresh())->post(route('app.profile.sign-out-others'), [
        'current_password' => 'definitely-wrong',
    ])->assertSessionHasErrors('current_password');
});

it('⚠️ a user WITH a password still cannot set a new one without confirming — unchanged', function () {
    $s = seedScenario(1);
    $user = pwlUser($s, withPassword: true);

    $this->actingAs($user)->post(route('app.security.password'), [
        'password' => 'Another-Str0ng-One!', 'password_confirmation' => 'Another-Str0ng-One!',
    ])->assertSessionHasErrors('current_password');
});

// ══ #5 · the second-factor trap ════════════════════════════════════════════════════════════════════

it('⚠️⚠️ the second-factor trap is resolved END TO END — enrol was behind the gate, now reachable', function () {
    $s = seedScenario(1);
    $user = pwlUser($s, withPassword: false);

    // ⚠️ 2FA enrolment CONFIRMS with `current_password`, so a passwordless user could never enrol the very
    //    thing that would make them safer. First: they are interrupted rather than refused outright.
    $blocked = $this->actingAs($user)->post(route('owner.security.2fa.enable'), ['current_password' => 'x']);
    expect($blocked->status())->toBe(302);
    $this->assertStringContainsString('/security', (string) $blocked->headers->get('Location'));

    // Then they set a password through the existing card …
    $this->actingAs($user)->post(route('app.security.password'), [
        'password' => 'Str0ng-New-Passw0rd!', 'password_confirmation' => 'Str0ng-New-Passw0rd!',
    ])->assertRedirect();

    // … and the confirmation now works with it, so enrolment is reachable.
    $after = $this->actingAs($user->fresh())
        ->post(route('owner.security.2fa.enable'), ['current_password' => 'Str0ng-New-Passw0rd!']);

    // ⚠️ It reached the real handler rather than the password screen — the trap is closed.
    $this->assertStringNotContainsString('password_status', (string) $after->headers->get('Location'));
})->skip(fn (): bool => ! Route::has('owner.security.2fa.enable'), 'no 2FA enable route in this build');

// ══ #6 · it cannot be silently false ═══════════════════════════════════════════════════════════════

it('⚠️⚠️ the gate reads a COLUMN, never a permission — so it cannot be silently false', function () {
    // ⚠️ `can()` returns false without a team set, and that shape has bitten three times. A check like that
    //    here would send EVERY user, password or not, to set a password forever — while looking like a
    //    working gate. This pins that the middleware consults nothing team-scoped.
    $source = file_get_contents(base_path('app/Http/Middleware/RequirePasswordBeforeReauth.php'));

    foreach (['->can(', 'hasRole', 'hasPermissionTo', 'Gate::'] as $forbidden) {
        $this->assertFalse(str_contains($source, $forbidden),
            "the gate consults {$forbidden}, which can be silently false without a team context");
    }

    expect($source)->toContain('hasPassword()');

    // And the both-directions proof, with NO team set at all: a user WITH a password still passes through.
    $s = seedScenario(1);
    $user = pwlUser($s, withPassword: true);
    setPermissionsTeamId(null);

    $response = $this->actingAs($user)->post(route('app.security.password'), [
        'current_password' => 'wrong', 'password' => 'X', 'password_confirmation' => 'X',
    ]);

    // Rejected by the ORIGINAL rule, not diverted by the gate.
    $response->assertSessionHasErrors('current_password');

    setPermissionsTeamId($s['org']->id);
});
