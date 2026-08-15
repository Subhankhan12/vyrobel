<?php

/*
 * D24-2 — PASSKEYS IN THE OPERATOR REALM (2026-08-15).
 *
 * ⚠️ THE SUBSYSTEM WAS ALREADY BUILT AND ALREADY REALM-AGNOSTIC — confirmed before writing a line, because
 * the audit has under-described before (DO13-4's "four paths" were nine). `PasskeyService` contains ZERO
 * owner references, `Passkey belongsTo(User)`, and `PasskeyController` reads `$request->user()` throughout.
 * So this was a WIRING pass, not a generalisation: the operator realm mounts the SAME controller on the SAME
 * service and renders the SAME `<x-auth.passkeys>` card. Size stayed S.
 *
 * ⚠️ AND SIGN-IN NEEDED NO WIRING AT ALL. `PasskeyLoginController` runs on the shared `web` guard and
 * redirects through the shared `RedirectsByRole` concern — the same one password login, 2FA and password
 * reset use — so an operator's passkey already authenticated and already landed on `/app`. The audit
 * suspected this; it is confirmed here rather than assumed, and pinned so a realm-specific redirect cannot
 * be introduced later.
 *
 * ⚠️ THE CEREMONY IS DEVICE-GATED FOR *PHP*, AND IT WAS PROVEN ANYWAY. No PHP test can mint a valid
 * authenticator signature, so these tests lock the routes, the options, the render, the self-only scoping
 * and the redirect. The CRYPTO was then proven separately AGAINST THE OPERATOR MOUNT, in Chromium with a CDP
 * virtual authenticator (`WebAuthn.addVirtualAuthenticator`, resident key + user verification):
 *
 *     register  → the real "Add a passkey" button ran `navigator.credentials.create()` and the credential
 *                 persisted for the OPERATOR ("macOS", last used 0 seconds ago);
 *     audit     → the row written was `app.passkey_registered` — the realm-accurate name, which is how that
 *                 fix is known to work rather than merely to be present in the source;
 *     sign-in   → logged out, then usernameless `navigator.credentials.get()` with a REAL signed assertion
 *                 → `{ok: true, redirect: /app/dashboard}` → landed on the OPERATOR dashboard.
 *
 * ⚠️ So the realm-correct redirect is PROVEN with real crypto, not inferred from the shared concern. The
 * browser fixtures were deleted afterwards and the removal raw-query verified (0 passkeys, 0 audit rows).
 * ⚠️ Two gotchas that make this reproducible: WebAuthn REJECTS an IP as `rp.id`, so the browser pass must use
 * `localhost:8000` and not `127.0.0.1:8000`; and usernameless sign-in needs a DISCOVERABLE credential.
 */

use App\Http\Controllers\Auth\PasskeyLoginController;
use App\Models\Passkey;
use App\Models\User;
use App\Support\Auth\PasskeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/** ⚠️ Prefixed — a top-level function in a Pest file is GLOBAL and has collided in this suite before. */
function d242Operator(array $s, string $email = 'rosa@example.test'): User
{
    return operatorUser($s, ['name' => 'Rosa Manager', 'email' => $email, 'password' => 'Op-Pass1!'], 'Hotel Manager');
}

/** A real passkey row for a user — the credential fields the model requires, no ceremony involved. */
function d242Passkey(User $user, string $label = 'Rosa MacBook'): Passkey
{
    return Passkey::create([
        'user_id' => $user->getKey(),
        'credential_id' => base64_encode(random_bytes(32)),
        'public_key' => base64_encode(random_bytes(64)),
        'label' => $label,
    ]);
}

// ── THE WIRING ────────────────────────────────────────────────────────────────────────────────────

it('⚠️ D24-2 — the operator passkey routes are mounted and reachable AS AN OPERATOR', function () {
    $s = seedScenario(1);
    $user = d242Operator($s);

    // ⚠️ READS THE ROUTER, not a pinned URL string: if the mount is removed the route stops existing and this
    // fails on the resolve rather than on a 404 that could have a dozen other causes.
    expect(Route::has('app.security.passkeys.options'))->toBeTrue()
        ->and(Route::has('app.security.passkeys.store'))->toBeTrue()
        ->and(Route::has('app.security.passkeys.remove'))->toBeTrue();

    $res = $this->actingAs($user)->postJson(route('app.security.passkeys.options'));
    $res->assertOk()->assertJsonStructure(['challenge', 'rp', 'user', 'pubKeyCredParams']);

    // The challenge is stashed for the verify step — the same two-step contract the owner mount uses.
    expect(session()->has('passkey.creation'))->toBeTrue();
});

it('⚠️ D24-2 — the operator Security page renders the SHARED passkey card, with the OPERATOR routes', function () {
    $s = seedScenario(1);
    $user = d242Operator($s);
    d242Passkey($user, 'Rosa YubiKey');

    $html = (string) $this->actingAs($user)->get('/app/security')->assertOk()->getContent();

    expect($html)->toContain('Passkeys')
        ->toContain('Rosa YubiKey')                       // the operator's own registered key
        ->toContain('Add a passkey')
        // ⚠️ THE OPERATOR ROUTES, not the owner ones — the wiring that would silently be wrong if the shared
        // component's props were mis-passed, and the page would still look perfectly fine.
        ->toContain('/app/security/passkeys')
        ->and($html)->not->toContain('/owner/security/passkeys');
});

it('⚠️ D24-2 — the card is ONE shared component, not a copy (§N.8f)', function () {
    // ⚠️ THE ANTI-DRIFT GUARD. Copying the owner card into the operator view would have been the quickest
    // wiring and would have left two ceremonies to drift apart the first time either was touched. Both views
    // must go through the same component, and the component must be the only place the markup lives.
    $shared = resource_path('views/components/auth/passkeys.blade.php');
    expect(is_file($shared))->toBeTrue();

    foreach (['livewire/owner/owner-security', 'livewire/operator/operator-security'] as $view) {
        $src = (string) file_get_contents(resource_path('views/'.$view.'.blade.php'));
        expect(str_contains($src, '<x-auth.passkeys'))->toBeTrue("{$view} does not use the shared card");
        // The ceremony must NOT be re-implemented in either page.
        expect(str_contains($src, 'navigator.credentials.create'))->toBeFalse("{$view} re-implements the ceremony");
    }

    // And the controller is genuinely shared — one class serving both mounts.
    $routes = (string) file_get_contents(base_path('routes/web.php'));
    expect(substr_count($routes, "[PasskeyController::class, 'options']"))->toBe(2)
        ->and(substr_count($routes, "[PasskeyController::class, 'store']"))->toBe(2)
        ->and(substr_count($routes, "[PasskeyController::class, 'destroy']"))->toBe(2);
});

// ── RBAC — THE PART THAT WOULD BE A REAL VULNERABILITY ────────────────────────────────────────────

it('⚠️ D24-2 — an operator CANNOT remove another user’s passkey (self-only)', function () {
    $s = seedScenario(1);
    $rosa = d242Operator($s, 'rosa@example.test');
    $frank = operatorUser($s, ['name' => 'Frank Desk', 'email' => 'frank@example.test', 'password' => 'Op-Pass1!'], 'Front Desk');

    $victim = d242Passkey($frank, 'Frank iPhone');
    // ⚠️ NON-VACUITY: the target really exists, so a passing "still there" assertion means something.
    expect(Passkey::query()->whereKey($victim->getKey())->exists())->toBeTrue();

    $this->actingAs($rosa)->post(route('app.security.passkeys.remove', $victim->getKey()), [
        'current_password' => 'Op-Pass1!',   // Rosa's OWN password — the re-auth passes; the scope must not
    ])->assertRedirect();

    // ⚠️ FRANK'S PASSKEY SURVIVES. Verified by RAW QUERY — a check that ran through the same scoping it is
    // testing would be vacuous (§N.18.1).
    expect((int) DB::table('passkeys')->where('id', $victim->getKey())->count())->toBe(1);
});

it('⚠️ D24-2 — an operator sees ONLY their own passkeys on the Security page', function () {
    $s = seedScenario(1);
    $rosa = d242Operator($s, 'rosa@example.test');
    $frank = operatorUser($s, ['name' => 'Frank Desk', 'email' => 'frank@example.test'], 'Front Desk');

    d242Passkey($rosa, 'Rosa MacBook');
    d242Passkey($frank, 'Frank iPhone');

    $html = (string) $this->actingAs($rosa)->get('/app/security')->assertOk()->getContent();
    expect($html)->toContain('Rosa MacBook')
        ->and($html)->not->toContain('Frank iPhone');
});

it('⚠️ D24-2 — an operator CAN remove their OWN passkey (the scope is not just "deny everything")', function () {
    // ⚠️ THE OTHER HALF OF THE SCOPE TEST. A `where('user_id', ...)` that accidentally matched nothing would
    // pass the cross-user test above while breaking the feature entirely.
    $s = seedScenario(1);
    $rosa = d242Operator($s);
    $own = d242Passkey($rosa, 'Rosa MacBook');

    $this->actingAs($rosa)->post(route('app.security.passkeys.remove', $own->getKey()), [
        'current_password' => 'Op-Pass1!',
    ])->assertRedirect();

    expect((int) DB::table('passkeys')->where('id', $own->getKey())->count())->toBe(0);
});

it('⚠️ D24-2 — removal is re-auth gated: a wrong password removes nothing', function () {
    $s = seedScenario(1);
    $rosa = d242Operator($s);
    $own = d242Passkey($rosa);

    $this->actingAs($rosa)->post(route('app.security.passkeys.remove', $own->getKey()), [
        'current_password' => 'not-the-password',
    ])->assertSessionHasErrors('current_password');

    expect((int) DB::table('passkeys')->where('id', $own->getKey())->count())->toBe(1);
});

it('⚠️ D24-2 — the passkey routes require authentication', function () {
    seedScenario(1);
    $this->postJson(route('app.security.passkeys.options'))->assertStatus(302);   // guest → login
});

// ── THE REDIRECT: ALREADY REALM-CORRECT, PINNED SO IT STAYS THAT WAY ──────────────────────────────

it('⚠️ D24-2 — passkey sign-in redirects an OPERATOR to /app and an OWNER to /owner (the shared concern)', function () {
    /*
     * ⚠️ THE REALM BUG THAT WOULD HAVE BEEN EASY TO SHIP. The usernameless sign-in is on the shared `web`
     * guard, so wiring operator passkeys without checking the redirect could have sent operators to the OWNER
     * dashboard. It does not: `PasskeyLoginController` resolves the destination through `RedirectsByRole`,
     * the SAME concern password login / 2FA / password reset use.
     *
     * ⚠️ Asserted against the concern's real behaviour rather than a hardcoded expectation, and pinned so a
     * later "give passkey login its own redirect" cannot silently break one realm.
     */
    $s = seedScenario(1);
    $operator = d242Operator($s);
    $owner = ownerRoleUser($s, 0);

    $resolve = function (User $user): ?string {
        $controller = new PasskeyLoginController(app(PasskeyService::class));

        return (function (User $u): ?string {
            return $this->homeFor($u);
        })->call($controller, $user);
    };

    expect($resolve($operator))->toBe(route('app.dashboard'))
        ->and($resolve($owner))->toBe(route('owner.dashboard'));

    // The login controller must keep USING that concern — a private copy would drift from every other flow.
    $src = (string) file_get_contents(app_path('Http/Controllers/Auth/PasskeyLoginController.php'));
    expect($src)->toContain('homeFor(')
        ->and((string) file_get_contents(app_path('Http/Controllers/Auth/Concerns/RedirectsByRole.php')))
        ->toContain('owner.dashboard')->toContain('app.dashboard');
});

// ── OWNER UNCHANGED ───────────────────────────────────────────────────────────────────────────────

it('⚠️ D24-2 — the OWNER passkey surface is unchanged by the extraction', function () {
    // The card moved into a shared component; the owner page must render exactly as before, on its OWN routes.
    $s = seedScenario(1);
    $owner = ownerRoleUser($s, 0);
    d242Passkey($owner, 'Owner Phone');

    $html = (string) $this->actingAs($owner)->get('/owner/security')->assertOk()->getContent();

    expect($html)->toContain('Passkeys')
        ->toContain('Owner Phone')
        ->toContain('/owner/security/passkeys')
        ->and($html)->not->toContain('/app/security/passkeys');
});

it('⚠️ D24-2 — the audit trail names the REAL realm (it would have written "owner" for an operator)', function () {
    // ⚠️ The one place the shared controller must be realm-aware. Hardcoding `owner.` — which it did — would
    // have written a false realm into the audit log the moment an operator registered a passkey: the audit
    // trail's own §N.8d, and invisible until someone read it back.
    $src = (string) file_get_contents(app_path('Http/Controllers/Auth/PasskeyController.php'));

    expect($src)->toContain("realmPrefix(\$request).'.passkey_registered'")
        ->toContain("realmPrefix(\$request).'.passkey_removed'")
        ->and($src)->not->toContain("record('owner.passkey_registered'")
        ->and($src)->not->toContain("record('owner.passkey_removed'");
});

it('⚠️ D24-2 — no fixture residue (raw query, independent of any scope)', function () {
    expect((int) DB::table('passkeys')->count())->toBe(0);
});
