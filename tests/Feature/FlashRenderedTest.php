<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\PlatformAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

// ── F-1 · F-4 · F-5 (+ a fourth door) — A MESSAGE SET IS NOT A MESSAGE SEEN ──────────────────────
// ⚠️⚠️ EVERY ONE OF THESE THREE PASSED A TEST THAT ASSERTED THE FLASH WAS SET. That is exactly how
// they hid. So every assertion below reads the RENDERED PAGE. `assertSessionHas` is deliberately
// never used as the proof — see the demonstration test at the bottom, which shows it passing while
// the screen shows nothing.

/** A real owner in this scenario — local, because the shared helper lives in another test file. */
function fr06Owner(array $scenario): User
{
    $user = User::factory()->create(['email' => 'fix06-owner@example.test', 'password' => 'password', 'status' => 'active']);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    Owner::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    return $user;
}

it('⚠️ F-1 HALF ONE — a SUCCESSFUL password change says so on the operator security page', function () {
    $s = seedScenario(1);
    $actor = operatorUser($s, [], 'Hotel Manager');

    $this->actingAs($actor)->post(route('app.security.password'), [
        'current_password' => 'password',
        'password' => 'a-new-strong-secret-11',
        'password_confirmation' => 'a-new-strong-secret-11',
    ])->assertRedirect(route('app.security'));

    // ⚠️ READ THE PAGE. Phase 3 proved this half by changing a real password and watching it say nothing.
    $this->actingAs($actor->fresh())->get(route('app.security'))
        ->assertOk()
        ->assertSee('Your password has been changed.');
});

it('⚠️ F-1 HALF TWO — the passwordless gate explains WHY it interrupted', function () {
    $s = seedScenario(1);
    $actor = operatorUser($s, [], 'Hotel Manager');

    // The same key carries the gate's message. Flash it the way the middleware does and read the page.
    $this->actingAs($actor)
        ->withSession(['password_status' => 'This action needs confirming with your password, and your account does not have one yet. Set one now and you will be brought back.'])
        ->get(route('app.security'))
        ->assertOk()
        ->assertSee('does not have one yet', false);
});

it('⚠️ F-4 — the owner email-change outcome is VISIBLE on the sign-in page it lands on', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, [], 'Hotel Manager');
    $user->forceFill(['pending_email' => 'new-owner-address@example.test'])->save();

    $url = URL::signedRoute('owner.account.email.verify', [
        'user' => $user->getKey(),
        'hash' => sha1('new-owner-address@example.test'),
    ]);

    $this->get($url)->assertRedirect(route('login'));

    $this->get(route('login'))->assertOk()->assertSee('Your email address has been updated', false);
});

it('⚠️⚠️ F-5 — the admin outcome is visible AND NAMES THE NEW ADDRESS', function () {
    $admin = PlatformAdmin::query()->create([
        'name' => 'Platform Root', 'email' => 'root@vyrobel.test',
        'password' => 'password', 'pending_email' => 'root-new@vyrobel.test',
    ]);

    $url = URL::signedRoute('admin.account.email.verify', [
        'admin' => $admin->getKey(), 'hash' => sha1('root-new@vyrobel.test'),
    ]);

    $this->get($url)->assertRedirect(route('admin.login'));

    // ⚠️ THE WORDING IS PART OF THE FIX: "sign in with your new address" without showing it is useless.
    $this->get(route('admin.login'))->assertOk()
        ->assertSee('root-new@vyrobel.test')
        ->assertSee('Your sign-in email is now', false);
});

it('⚠️ F-5 — a TAMPERED or STALE link says something different and actionable, on the same URL', function () {
    $admin = PlatformAdmin::query()->create([
        'name' => 'Platform Root2', 'email' => 'root2@vyrobel.test',
        'password' => 'password', 'pending_email' => 'root2-new@vyrobel.test',
    ]);

    // Wrong hash — the tampered case. All four outcomes land on the SAME url, so the message is the
    // only thing that distinguishes them.
    $url = URL::signedRoute('admin.account.email.verify', [
        'admin' => $admin->getKey(), 'hash' => sha1('someone-elses@vyrobel.test'),
    ]);
    $this->get($url)->assertRedirect(route('admin.login'));

    $this->get(route('admin.login'))->assertOk()
        ->assertSee('no longer valid', false)
        ->assertDontSee('root2-new@vyrobel.test');     // ⚠️ a failed attempt must not leak the pending address

    expect((string) $admin->fresh()->email)->toBe('root2@vyrobel.test');   // behaviour unchanged
});

it('⚠️ THE FOURTH DOOR — a failed Stripe onboarding is visible on the payout-method screen', function () {
    $s = seedScenario(1);
    $owner = fr06Owner($s);

    $this->actingAs($owner)
        ->withSession(['payout_method_error' => 'We couldn’t start Stripe onboarding — please try again.'])
        ->get(route('owner.payout-method'))
        ->assertOk()
        ->assertSee('start Stripe onboarding', false);
});

it('⚠️⚠️ THE WRONG-FIX DEMONSTRATION — asserting the session key PASSES while the screen shows nothing', function () {
    $s = seedScenario(1);
    $actor = operatorUser($s, [], 'Hotel Manager');

    $response = $this->actingAs($actor)->post(route('app.security.password'), [
        'current_password' => 'password',
        'password' => 'another-strong-secret-22',
        'password_confirmation' => 'another-strong-secret-22',
    ]);

    // ⚠️ THIS IS THE TEST THAT LET ALL THREE DEFECTS HIDE. It is green whether or not any view renders
    // the key — it proves only that a controller called ->with(). Kept deliberately, and labelled, so the
    // difference between "set" and "seen" is visible in the suite itself rather than in a report.
    $response->assertSessionHas('password_status');

    expect(true)->toBeTrue();
});

// ── THE GUARD — and precisely what it does NOT close ─────────────────────────────────────────────
it('⚠️ GUARD — every flash key a controller sets is read somewhere', function () {
    $stripPhp = fn (string $s): string => preg_replace('~^\s*//.*$~m', '', (string) preg_replace('~/\*.*?\*/~s', '', $s));
    $stripBlade = function (string $s): string {
        foreach (['~<style\b.*?</style>~is', '~<script\b.*?</script>~is', '~\{\{--.*?--\}\}~s', '~<!--.*?-->~s', '~/\*.*?\*/~s'] as $p) {
            $s = (string) preg_replace($p, '', $s);
        }

        return $s;   // ⚠️ stripped BEFORE scanning: a comment containing the needle has fired this trap ten times
    };

    $files = function (string $dir): Generator {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($dir))) as $f) {
            if ($f->isFile() && str_ends_with((string) $f, '.php')) {
                yield (string) $f;
            }
        }
    };

    // ⚠️ TWO NAMES ARE EXCLUDED, AND THE REASON IS NOT "THEY FAIL". `->with('x', …)` is ALSO Eloquent's
    // eager-load, and these two are relation names on a query builder, never a flash. Static analysis cannot
    // tell the two apart without type inference, so they are named here rather than silently filtered.
    $eloquentCollisions = ['owner', 'unit'];

    $set = [];
    $read = [];
    foreach ($files('app') as $p) {
        $src = $stripPhp((string) file_get_contents($p));
        preg_match_all("~->with\(\s*'([a-z0-9_]+)'\s*,~", $src, $m);
        foreach ($m[1] as $k) {
            $set[$k] ??= $p;
        }
        preg_match_all("~session\(\s*'([a-z0-9_]+)'|session\(\)->(?:get|has|pull)\(\s*'([a-z0-9_]+)'~", $src, $r);
        $read = array_merge($read, array_filter($r[1]), array_filter($r[2]));
    }
    foreach ($files('resources/views') as $p) {
        $src = $stripBlade((string) file_get_contents($p));
        preg_match_all("~session\(\s*'([a-z0-9_]+)'|session\(\)->(?:get|has|pull)\(\s*'([a-z0-9_]+)'~", $src, $r);
        $read = array_merge($read, array_filter($r[1]), array_filter($r[2]));
    }

    $read = array_unique($read);
    $orphans = array_values(array_diff(array_keys($set), $read, $eloquentCollisions));

    // NON-VACUITY: the scan must actually be finding keys, or an empty diff proves nothing.
    expect(count($set))->toBeGreaterThan(50)
        ->and(count($read))->toBeGreaterThan(50);

    expect($orphans)->toBe([], 'flash keys set by a controller and read by nothing: '.implode(', ', $orphans));
});
