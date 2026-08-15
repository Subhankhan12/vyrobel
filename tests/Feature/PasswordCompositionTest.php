<?php

/*
 * DO13-4 — REAL PASSWORD COMPOSITION, AT EVERY SET-PASSWORD PATH (2026-08-15).
 *
 * ⚠️ THE DEFECT. Every user-facing set-password path validated `Password::min(8)` and nothing else, so
 * `aaaaaaaa` was an acceptable password — on public self-signup, on both invite-acceptance flows, on both
 * reset flows, on three guest surfaces and on the platform-admin account — while the Guest Signup screen
 * promised "8+ characters with a number and symbol". Nine independent copies of one rule, and all nine weak.
 *
 * ⚠️ THE AUDIT NAMED FOUR OF THE NINE. It listed AcceptInvite / PasswordReset / ChangePassword /
 * AcceptOwnerTeamInvite. A sweep found five more — Onboarding (public SELF-SIGNUP), Admin\AccountController,
 * and the entire guest realm (Account, Claim, PasswordReset). ⚠️ **A search finds only what you name**
 * (§N.8f, the D34-1 / DO-RESTORE-5 lesson): the four were the ones a previous reader had already written
 * down, and the missing five included the most public path in the product.
 *
 * ⚠️ WHY THIS FILE TESTS EVERY PATH SEPARATELY RATHER THAN TESTING THE RULE ONCE. `Password::defaults()` is
 * opt-in per call site: a controller that keeps `Password::min(8)` silently ignores the policy and no
 * unit test of the policy object would ever notice. The every-copy guarantee is PER PATH, so each path is
 * driven end-to-end with a weak password and a compliant one.
 *
 * ⚠️ COMPOSITION IS A SET-TIME RULE, NOT A LOGIN RULE. Existing users keep their existing passwords — login
 * compares a hash and never re-validates composition. Asserted below rather than assumed, because getting
 * that wrong would lock out every existing account.
 */

use App\Actions\Authorization\OrganizationRoleProvisioner;
use App\Models\Guest;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use App\Services\OwnerInviteService;
use App\Support\Auth\PasswordPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

uses(RefreshDatabase::class);

/** ⚠️ Prefixed — a top-level const in a Pest file is GLOBAL and has collided in this suite before. */
const DO134_OK = 'Str0ng-Pass!';          // letters + numbers + symbol, 12 chars
const DO134_NO_NUMBER = 'Strong-Pass!';   // letters + symbol, no digit
const DO134_NO_SYMBOL = 'StrongPass12';   // letters + digits, no symbol
const DO134_NO_LETTER = '12345678!';      // digits + symbol, no letter
const DO134_SHORT = 'Ab1!';               // everything but length

/** Every composition failure the policy must catch, keyed for a readable failure message. */
function do134Weak(): array
{
    return ['no number' => DO134_NO_NUMBER, 'no symbol' => DO134_NO_SYMBOL, 'no letter' => DO134_NO_LETTER, 'too short' => DO134_SHORT];
}

// ── THE POLICY ITSELF ─────────────────────────────────────────────────────────────────────────────

it('⚠️ DO13-4 — the ONE policy is registered as the framework default and has the promised composition', function () {
    // ⚠️ READS THE LIVE RULE, not a pinned constant (DO-RESTORE-2's trap): if the provider stops registering
    // the policy, or someone edits the composition, this reads the changed value rather than a copy of it.
    $rule = Password::defaults();
    $ref = new ReflectionObject($rule);
    $read = function (string $prop) use ($ref, $rule) {
        $p = $ref->getProperty($prop);
        $p->setAccessible(true);

        return $p->getValue($rule);
    };

    expect($read('min'))->toBe(PasswordPolicy::MIN_LENGTH)
        ->and($read('letters'))->toBeTrue()
        ->and($read('numbers'))->toBeTrue()
        ->and($read('symbols'))->toBeTrue()
        // ⚠️ NOT enforced, deliberately: no screen promises mixed case, and `uncompromised()` is a founder
        // call (an outbound HIBP request on every password set). If either turns on, it must be a decision —
        // this assertion is what makes it one instead of a silent drift.
        ->and($read('mixedCase'))->toBeFalse()
        ->and($read('uncompromised'))->toBeFalse();

    // The user-facing sentence must describe the rule it sits beside — the halves live in one class so they
    // cannot drift, and this pins the specific claims.
    expect(PasswordPolicy::hint())->toContain('8')
        ->and(strtolower(PasswordPolicy::hint()))->toContain('number')
        ->and(strtolower(PasswordPolicy::hint()))->toContain('symbol');
});

it('⚠️ DO13-4 — NO set-password path is left on the length-only rule (§N.8f, all nine)', function () {
    // ⚠️ THE STRUCTURAL GUARD, and the one that would have caught the original defect. `Password::defaults()`
    // is opt-in, so a new controller written with `min(8)` re-opens the hole silently. This sweeps app/ for
    // any surviving length-only rule rather than trusting a list of nine filenames.
    $offenders = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path()));
    foreach ($it as $file) {
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
            continue;
        }
        // The platform-admin command is the ONE legitimate exception — it requires MORE (min(12)+mixedCase),
        // so pointing it at the shared default would WEAKEN it. Confirmed before excluding, not assumed.
        if (str_contains($file->getPathname(), 'CreatePlatformAdminCommand')) {
            continue;
        }

        // ⚠️ TOKENISED, NOT GREPPED. A first draft matched raw text and flagged three files whose only "hit"
        // was a COMMENT quoting the old rule — including this fix's own explanation of what it replaced.
        // Scanning real tokens means the guard sees what executes, not what is written about it.
        $code = '';
        foreach (token_get_all((string) file_get_contents($file->getPathname())) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $code .= is_array($token) ? $token[1] : $token;
        }

        if (preg_match('/(?:Password|PasswordRule)::min\(\s*\d+\s*\)/', $code)) {
            $offenders[] = $file->getFilename();
        }
    }

    // ⚠️ NON-VACUITY: the sweep really does read files (it finds the deliberate exception when not excluded).
    expect(class_exists(PasswordPolicy::class))->toBeTrue();
    expect(array_values(array_unique($offenders)))->toBe([], 'a set-password path still uses a length-only rule: '.implode(', ', array_unique($offenders)));
});

it('⚠️ DO13-4 — the platform-admin command keeps its STRONGER bar (confirm-before-collapsing)', function () {
    // Same concept, legitimately different: a super-admin credential is created once, by a human at a
    // terminal, and carries a higher bar. Collapsing it onto the shared default would have been a REGRESSION
    // dressed as consistency — the judgement the payout-status maps needed too.
    $src = (string) file_get_contents(app_path('Console/Commands/CreatePlatformAdminCommand.php'));
    expect($src)->toContain('Password::min(12)')
        ->and($src)->toContain('mixedCase()')
        ->and(PasswordPolicy::MIN_LENGTH)->toBeLessThan(12);
});

// ── EVERY PATH, END TO END ────────────────────────────────────────────────────────────────────────

it('⚠️ path 1/9 — public SELF-SIGNUP (⚠️ NOT in the audit\'s four; the most public path in the product)', function () {
    $payload = fn (string $pw): array => [
        'org_name' => 'New Org', 'property_name' => 'New Property', 'property_type' => 'hotel',
        'currency' => 'EUR', 'timezone' => 'UTC', 'name' => 'Founder', 'email' => 'founder@example.test',
        'password' => $pw, 'password_confirmation' => $pw,
    ];

    foreach (do134Weak() as $why => $weak) {
        $this->post('/signup', $payload($weak))->assertSessionHasErrors('password');
    }
    expect(User::query()->where('email', 'founder@example.test')->exists())->toBeFalse();

    $this->post('/signup', $payload(DO134_OK))->assertSessionHasNoErrors();
    expect(User::query()->where('email', 'founder@example.test')->exists())->toBeTrue();
});

it('⚠️ path 2/9 — the operator INVITE acceptance', function () {
    Mail::fake();
    $s = seedScenario(1);
    app(OrganizationRoleProvisioner::class)->provision($s['org']);

    // ⚠️ THE INVITE COMES FROM THE REAL SERVICE, which returns the plaintext token once — the same token the
    // acceptance controller hashes and looks up. Self-contained on purpose: borrowing another test file's
    // global helper would make this guard depend on that file still existing and loading first.
    ['token' => $token] = app(OwnerInviteService::class)
        ->invite('Morgan Manager', 'morgan@example.test', [], null, 'manager');

    foreach (do134Weak() as $weak) {
        $this->post("/invite/{$token}", ['password' => $weak, 'password_confirmation' => $weak])
            ->assertSessionHasErrors('password');
    }

    $this->post("/invite/{$token}", ['password' => DO134_OK, 'password_confirmation' => DO134_OK])
        ->assertSessionHasNoErrors();
    expect(Hash::check(DO134_OK, User::query()->where('email', 'morgan@example.test')->first()->password))->toBeTrue();
});

it('⚠️ path 3/9 — the operator/owner PASSWORD RESET', function () {
    $user = User::factory()->create(['email' => 'reset@example.test', 'password' => 'Existing-Pass1!', 'status' => 'active']);
    $token = app('auth.password.broker')->createToken($user);

    $post = fn (string $pw) => $this->post('/reset-password', [
        'token' => $token, 'email' => 'reset@example.test', 'password' => $pw, 'password_confirmation' => $pw,
    ]);

    foreach (do134Weak() as $why => $weak) {
        $post($weak)->assertSessionHasErrors('password');
    }
    $post(DO134_OK)->assertSessionHasNoErrors();
    expect(Hash::check(DO134_OK, $user->fresh()->password))->toBeTrue();
});

it('⚠️ path 4/9 — the operator CHANGE PASSWORD (signed-in)', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Rosa', 'password' => 'Existing-Pass1!'], 'Hotel Manager');

    $post = fn (string $pw) => $this->actingAs($user)->post('/app/security/password', [
        'current_password' => 'Existing-Pass1!', 'password' => $pw, 'password_confirmation' => $pw,
    ]);

    foreach (do134Weak() as $why => $weak) {
        $post($weak)->assertSessionHasErrors('password');
    }
    $post(DO134_OK)->assertSessionHasNoErrors();
    expect(Hash::check(DO134_OK, $user->fresh()->password))->toBeTrue();
});

it('⚠️ path 5/9 — the OWNER change password (a separate route on the same controller)', function () {
    $s = seedScenario(1);
    $user = User::factory()->create(['password' => 'Existing-Pass1!', 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    Owner::factory()->create(['user_id' => $user->id]);
    grantBackOfficeRole($user, $s['org'], 'External Owner');

    $post = fn (string $pw) => $this->actingAs($user)->post('/owner/security/password', [
        'current_password' => 'Existing-Pass1!', 'password' => $pw, 'password_confirmation' => $pw,
    ]);

    foreach (do134Weak() as $why => $weak) {
        $post($weak)->assertSessionHasErrors('password');
    }
    $post(DO134_OK)->assertSessionHasNoErrors();
    expect(Hash::check(DO134_OK, $user->fresh()->password))->toBeTrue();
});

it('⚠️ path 6/9 — the PLATFORM-ADMIN account password (⚠️ NOT in the audit\'s four)', function () {
    $admin = platformAdmin();   // the suite's own helper — its factory password is 'password'

    $post = fn (string $pw) => $this->actingAs($admin, 'admin')->put(route('admin.account.password'), [
        'current_password' => 'password', 'password' => $pw, 'password_confirmation' => $pw,
    ]);

    foreach (do134Weak() as $why => $weak) {
        $post($weak)->assertSessionHasErrors('password');
    }
    $post(DO134_OK)->assertSessionHasNoErrors();
    expect(Hash::check(DO134_OK, $admin->fresh()->password))->toBeTrue();
});

/** A standalone claimed guest — the shape GuestAccountTest uses, inlined so this guard stands alone. */
function do134Guest(string $email, string $pw): Guest
{
    $g = Guest::create(['email' => $email, 'first_name' => 'First', 'last_name' => 'Last', 'language' => 'en']);
    $g->password = $pw;
    $g->email_verified_at = now();
    $g->save();

    return $g->fresh();
}

it('⚠️ path 7/9 — the GUEST account change-password (⚠️ NOT in the audit\'s four)', function () {
    seedScenario(1);
    $guest = do134Guest('ada.pw@example.test', 'Existing-Pass1!');

    $post = fn (string $pw) => $this->actingAs($guest, 'guest')->post(route('guest.account.password'), [
        'current_password' => 'Existing-Pass1!', 'password' => $pw, 'password_confirmation' => $pw,
    ]);

    foreach (do134Weak() as $weak) {
        $post($weak)->assertSessionHasErrors('password');
    }
    $post(DO134_OK)->assertSessionHasNoErrors();
    expect(Hash::check(DO134_OK, (string) $guest->fresh()->password))->toBeTrue();
});

it('⚠️ path 8/9 — the GUEST password reset (its OWN broker, on the guests provider)', function () {
    seedScenario(1);
    $guest = do134Guest('reset.pw@example.test', 'Existing-Pass1!');
    $token = Illuminate\Support\Facades\Password::broker('guests')->createToken($guest);

    $post = fn (string $pw) => $this->post(route('guest.password.update'), [
        'token' => $token, 'email' => 'reset.pw@example.test', 'password' => $pw, 'password_confirmation' => $pw,
    ]);

    foreach (do134Weak() as $weak) {
        $post($weak)->assertSessionHasErrors('password');
    }
    $post(DO134_OK)->assertSessionHasNoErrors();
    expect(Hash::check(DO134_OK, (string) $guest->fresh()->password))->toBeTrue();
});

it('⚠️ path 9/9 — the GUEST CLAIM: a booking-tied account\'s FIRST password', function () {
    $s = seedScenario(1);

    // ⚠️ A claimable guest needs a REAL BOOKING and a null password (`GuestAccountService::resolveClaimable`),
    // not merely an email. A bare Guest resolves to null and the controller renders the generic "invalid
    // link" VIEW with no validation errors — which reads exactly like the rule failing when it is the fixture.
    $s['guest']->update(['email' => 'claim.pw@example.test', 'first_name' => 'Claim', 'last_name' => 'Guest']);
    book($s, ['unit_id' => $s['units'][0]->id]);
    $claimGuest = $s['guest']->fresh();
    expect($claimGuest->password)->toBeNull();   // non-vacuity: the link is genuinely claimable

    $link = URL::temporarySignedRoute('guest.claim.complete', now()->addHour(), [
        'guest' => $claimGuest->getKey(), 'hash' => sha1((string) $claimGuest->email),
    ]);

    foreach (do134Weak() as $weak) {
        $this->post($link, ['password' => $weak, 'password_confirmation' => $weak])
            ->assertSessionHasErrors('password');
    }
    expect($claimGuest->fresh()->password)->toBeNull();   // no weak password was ever set

    $this->post($link, ['password' => DO134_OK, 'password_confirmation' => DO134_OK]);
    expect(Hash::check(DO134_OK, (string) $claimGuest->fresh()->password))->toBeTrue();
});

// ── THE THINGS THAT MUST NOT HAVE CHANGED ─────────────────────────────────────────────────────────

it('⚠️ DO13-4 — an EXISTING weak password still logs in (composition is a SET-time rule, not a login rule)', function () {
    // ⚠️ THE REGRESSION THAT WOULD HURT MOST. Every existing user chose their password under the old rule;
    // if login re-validated composition, this change would lock out the entire user base at once.
    $s = seedScenario(1);
    $user = operatorUser($s, ['email' => 'legacy@example.test', 'password' => 'weakpass'], 'Hotel Manager');
    expect(Hash::check('weakpass', $user->fresh()->password))->toBeTrue();   // non-vacuity: it really is weak

    $this->post('/login', ['email' => 'legacy@example.test', 'password' => 'weakpass'])
        ->assertSessionHasNoErrors();
    $this->assertAuthenticated();
});

it('⚠️ DO13-4 — every set-password SCREEN states the rule, from the one definition', function () {
    /*
     * ⚠️ THE COPY IS PART OF THE FIX, NOT DECORATION. A rule the UI does not state produces rejections the
     * user cannot act on — and the ninth screen (operator Security) was found ONLY by sweeping the ROUTE:
     * a copy-sweep for "8 characters" could not see it, precisely because it made no claim at all.
     */
    $screens = [
        'auth/reset-password', 'auth/onboarding', 'auth/set-password', 'admin/account/index',
        'livewire/owner/owner-security', 'livewire/operator/operator-security',
        'guest/account', 'guest/auth/reset-password', 'guest/auth/set-password',
    ];

    foreach ($screens as $screen) {
        $path = resource_path('views/'.$screen.'.blade.php');
        expect(is_file($path))->toBeTrue("missing set-password screen: {$screen}");
        // ⚠️ NO custom message here: Pest's `toContain()` treats extra arguments as MORE NEEDLES, so a
        // message would be searched for as text and fail on every correct file. The screen name is asserted
        // through the is_file() expectation above instead.
        expect(str_contains((string) file_get_contents($path), 'PasswordPolicy::hint()'))
            ->toBeTrue("{$screen} does not state the password rule");
    }

    // ⚠️ And the OLD sentence is gone everywhere — a screen still promising only length would be the §N.8d
    // defect pointed the other way: the UI under-stating what the server enforces.
    $stale = [];
    foreach (glob(resource_path('views/**/*.blade.php')) ?: [] as $f) {
        if (preg_match('/>\s*(?:🔒\s*)?At least 8 characters\.?\s*</', (string) file_get_contents($f))) {
            $stale[] = basename($f);
        }
    }
    expect($stale)->toBe([]);
});
