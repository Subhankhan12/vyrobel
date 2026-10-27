<?php

declare(strict_types=1);

use App\Models\AuditEvent;
use App\Models\IpAllowRule;
use App\Models\UserIdentity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

// ⚠️⚠️ SECURITY, 2026-10-05 (the founder: "this page design is not good. Fix it."). 🖥 Before: a 560 px column of cards beside
// full-width ones with the right half of the page empty, "Current password" labelled twice, every dialog on an undefined
// `vyp-scrim` class with inline positioning, 101 inline styles, flash colours under AA. 📖 And beneath the look: signing out of
// other devices from here landed on the Profile page; one error message appeared in three cards at once; removing the allowlist
// rule that admits you would lock you out on the next request; disconnecting X said "Google disconnected."; a passkey added from
// Safari was named "This device" for ever; "a passkey never leaves your device" (a synced one does). Each test failed first.

function sec5Page(array $s, $user = null): string
{
    return (string) test()->actingAs($user ?? operatorUser($s, [], 'Property Owner'))->get(route('app.security'))->assertOk()->getContent();
}

/**
 * The hidden fields the page's own form posts to $action — so a test sends what a browser sends, and a marker removed from the
 * view (a mutation) changes the request too.
 *
 * @return array<string, string>
 */
function sec5Hidden(string $html, string $action): array
{
    preg_match('~<form method="POST" action="'.preg_quote($action, '~').'"[^>]*>(.*?)</form>~s', $html, $form);
    preg_match_all('~<input type="hidden" name="([^"]+)" value="([^"]*)"~', $form[1] ?? '', $m, PREG_SET_ORDER);
    $fields = [];
    foreach ($m as [, $name, $value]) {
        if ($name !== '_token') {
            $fields[$name] = html_entity_decode($value);
        }
    }

    return $fields;
}

/** The view as it runs — its Blade comments out (they quote the old markup they replaced; a scan must not read its own note). */
function sec5Src(string $view = 'views/livewire/operator/operator-security.blade.php'): string
{
    // I18N-01 — the view's words are read in English, as the page shows them (they now live in lang/en)
    return (string) preg_replace('/\{\{--.*?--\}\}/s', '', i18nEnglishSource(resource_path($view)));
}

// ── his report: the design ─────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ lays the page out in three columns of the shared Security kit — no 560 px column beside full-width ones', function () {
    // 2026-10-24 — his design replaced the two named groups (2026-10-05) with three columns, shared with the owner page;
    // the defect this pinned (a narrow column beside full-width cards, the right half of the page empty) stays refused
    $s = seedScenario(1);
    $html = sec5Page($s);

    expect($html)->toContain('<div class="vy-sx-cols">')
        ->and(substr_count($html, '<div class="vy-sx-col">'))->toBe(3);
    expect(sec5Src())->not->toContain('max-width:560px')->not->toContain('max-width: 560px');
    expect((string) file_get_contents(resource_path('views/components/security/styles.blade.php')))
        ->toMatch('~\.vy-sx-cols \{[^}]*grid-template-columns: repeat\(auto-fit, minmax\(min\(\d+px, 100%\), 1fr\)\)~');
});

it('labels Current password once', function () {
    $s = seedScenario(1);

    // 🖥 before: "CURRENT PASSWORD" printed twice above one box
    expect(substr_count(sec5Page($s), '<label class="vy-sx-label" for="vy-sec-cur">'))->toBe(1);
});

it('⚠️⚠️ opens every dialog centred on the Security kit’s solid card — never the undefined scrim with inline positioning', function () {
    $src = sec5Src();

    // 📖 before (2026-10-05): class="vyp-scrim" (no CSS anywhere) + style="position:fixed;inset:0;…display:flex" beside x-show.
    // Since 2026-10-24 every dialog is the kit's scrim + solid card, each scrim holding exactly one dialog panel.
    expect($src)->not->toContain('vyp-scrim')->not->toContain('position:fixed')
        ->and(substr_count($src, 'class="vy-sx-scrim"'))->toBeGreaterThanOrEqual(6)
        ->and(substr_count($src, 'class="vy-sx-scrim"'))->toBe(substr_count($src, 'class="vy-sx-dialog"'));
});

it('⚠️ shows flashes in the shared AA classes, not hand-mixed colours', function () {
    $s = seedScenario(1);
    $html = (string) test()->actingAs(operatorUser($s, [], 'Property Owner'))->withSession(['security_notice' => 'Saved it.'])
        ->get(route('app.security'))->getContent();

    // 🔬 before: #1a7f4b on its 10% wash — 3.95:1, under AA
    expect($html)->toContain('<div class="vy-flash is-ok vy-fade" role="status">Saved it.</div>')
        ->and(sec5Src())->not->toContain('#1a7f4b');
});

// ── sessions ──────────────────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ signs one other device out from its row — never the one in use, never by its raw session id', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, [], 'Property Owner');
    $other = operatorUser($s, [], 'Front Desk');
    DB::table('sessions')->insert([
        ['id' => 'sess5-phone-'.Str::random(20), 'user_id' => $me->id, 'ip_address' => '5.6.7.8', 'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1', 'last_activity' => time() - 600, 'payload' => ''],
        ['id' => 'sess5-theirs-'.Str::random(20), 'user_id' => $other->id, 'ip_address' => '9.9.9.9', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0)', 'last_activity' => time(), 'payload' => ''],
    ]);
    $phone = (string) DB::table('sessions')->where('user_id', $me->id)->where('ip_address', '5.6.7.8')->value('id');
    $theirs = (string) DB::table('sessions')->where('user_id', $other->id)->value('id');

    $html = sec5Page($s, $me);
    // a session id is a key to that sign-in — the page names each session by a one-way token, never by the id itself
    expect($html)->not->toContain($phone)->toContain('name="session" value="'.hash('sha256', $phone).'"')
        ->toContain('>Sign out</button>');

    test()->actingAs($me)->post(route('app.security.sessions.sign-out'), ['session' => hash('sha256', $phone)])
        ->assertRedirect(route('app.security'))->assertSessionHas('security_notice', 'Signed out of Safari · iPhone.');
    expect(DB::table('sessions')->where('id', $phone)->exists())->toBeFalse()
        ->and(AuditEvent::query()->where('action', 'user.session_signed_out')->exists())->toBeTrue();

    // someone else's session is not found among mine
    test()->actingAs($me)->post(route('app.security.sessions.sign-out'), ['session' => hash('sha256', $theirs)])
        ->assertSessionHas('security_error');
    expect(DB::table('sessions')->where('id', $theirs)->exists())->toBeTrue();
})->group('security');

it('⚠️ brings “Sign out of all other devices” back to this page, and its password error into its dialog', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, [], 'Property Owner');

    // 📖 before: started here, it landed on the Profile page
    test()->actingAs($me)->post(route('app.profile.sign-out-others'), ['current_password' => 'password', 'return' => 'security'])
        ->assertRedirect(route('app.security'))->assertSessionHas('security_notice');
    // without `return`, it still lands where it always did
    test()->actingAs($me)->post(route('app.profile.sign-out-others'), ['current_password' => 'password'])
        ->assertRedirect(route('app.profile'));

    // a wrong password reopens the dialog with the error inside it — once, not also at the top and in two other cards
    // (the page is read straight after the refusal — asserting the session's errors first would consume them)
    $form = sec5Hidden(sec5Page($s, $me), route('app.profile.sign-out-others'));
    expect($form)->toBe(['return' => 'security', '_form' => 'signout']);
    test()->actingAs($me)->from(route('app.security'))->post(route('app.profile.sign-out-others'), $form + ['current_password' => 'not-it'])
        ->assertRedirect(route('app.security'));
    $html = (string) test()->actingAs($me)->get(route('app.security'))->getContent();
    expect($html)->toContain('signOutOpen: true')
        ->and(substr_count($html, e(__('validation.current_password'))))->toBe(1);
});

// ── the IP allowlist ──────────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ refuses to remove the one allowlist rule that lets you in — the next page would lock you out', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, [], 'Property Owner');
    $mine = IpAllowRule::create(['ip_address' => '127.0.0.1', 'label' => 'Office']);
    $office = IpAllowRule::create(['ip_address' => '203.0.113.9', 'label' => 'Branch']);

    // 📖 before: removed — and 🔬 EnforceIpAllowlist then refused every request from 127.0.0.1 with the other rule still on
    test()->actingAs($me)->delete(route('app.security.ip-rules.remove', $mine->id))
        ->assertSessionHas('security_error', fn (string $m): bool => str_contains($m, 'lock you out') && str_contains($m, '127.0.0.1'));
    expect(IpAllowRule::query()->find($mine->id))->not->toBeNull();

    // another rule can go; and with yours the only one left, removing it turns the allowlist off — never a lockout
    test()->actingAs($me)->delete(route('app.security.ip-rules.remove', $office->id))->assertSessionHas('security_notice');
    test()->actingAs($me)->delete(route('app.security.ip-rules.remove', $mine->id))
        ->assertSessionHas('security_notice', fn (string $m): bool => str_contains($m, 'allowlist is off'));
    expect(IpAllowRule::query()->count())->toBe(0);
})->group('security');

it('⚠️ asks before removing an allowlist rule, in a dialog — never the browser’s confirm()', function () {
    expect(sec5Src())->not->toContain('onsubmit="return confirm(')->toContain('x-show="ipRemove !== null"');
});

it('says "back office", never "admin panel"', function () {
    $s = seedScenario(1);

    expect(sec5Page($s))->not->toContain('admin panel');
});

// ── honest words ──────────────────────────────────────────────────────────────────────────────────────────────

it('⚠️ says what the trusted-devices list means in the state the account is in', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, [], 'Property Owner');

    // 📖 before: "Every sign-in will ask for your 2FA code." — said while two-factor was OFF, when no sign-in asks for one
    expect(sec5Page($s, $me))->toContain('Two-factor is off, so no device skips it.')->not->toContain('Every sign-in will ask for your 2FA code');

    $me->forceFill(['two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_recovery_codes' => encrypt(json_encode(['aaaa-bbbb'])),
        'two_factor_confirmed_at' => now()])->save();
    expect(sec5Page($s, $me->fresh()))->toContain('Every sign-in asks for your code.');
});

it('⚠️ names the provider it connected or disconnected — never "Google" for X', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, [], 'Property Owner');
    $x = UserIdentity::create(['user_id' => $me->getKey(), 'provider' => 'twitter', 'provider_user_id' => 'x-'.Str::random(10)]);

    // 📖 before: SocialAuthController::unlink always said "Google disconnected."
    test()->actingAs($me)->post(route('app.security.connections.unlink', $x->id))
        ->assertRedirect(route('app.security'))->assertSessionHas('connections_status', 'X disconnected.');
    expect((string) file_get_contents(app_path('Http/Controllers/Auth/SocialAuthController.php')))
        ->not->toContain("'Google connected. You can now sign in with it.'")
        ->not->toContain("'That Google account is already connected.'");
});

it('⚠️ keeps the passkey card honest — a synced passkey does leave the device, and Safari has a name', function () {
    $src = sec5Src('views/components/auth/passkeys.blade.php');

    // 📖 before: "a passkey never leaves your device" (iCloud Keychain and Google Password Manager sync them); and the label read
    //    navigator.userAgentData?.platform — Safari and Firefox have none, so their passkeys were named "This device" for ever
    expect($src)->not->toContain('never leaves your device')->not->toContain("|| 'This device'")
        ->toContain("'Passkey on ' + platform");
});

it('⚠️ shows each error inside the form that caused it, once — not in three cards at once', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, [], 'Property Owner');
    $me->forceFill(['two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_recovery_codes' => encrypt(json_encode(['aaaa-bbbb'])),
        'two_factor_confirmed_at' => now()])->save();

    // a wrong current password on Change password — 📖 before, it also appeared under Turn off two-factor and under Passkeys
    $form = sec5Hidden(sec5Page($s, $me->fresh()), route('app.security.password'));
    test()->actingAs($me->fresh())->from(route('app.security'))->post(route('app.security.password'), $form + [
        'current_password' => 'wrong-one', 'password' => 'A-new-pass-12!', 'password_confirmation' => 'A-new-pass-12!',
    ])->assertRedirect(route('app.security'));
    $html = (string) test()->actingAs($me->fresh())->get(route('app.security'))->getContent();

    // once, and in the card it came from — never the page's top banner (SC-M21: a count alone was satisfied by the banner)
    $message = e(__('validation.current_password'));
    expect(substr_count($html, $message))->toBe(1)
        ->and(strpos($html, $message))->toBeGreaterThan((int) strpos($html, 'id="vy-sec-pw-t"'))
        ->and(strpos($html, $message))->toBeLessThan((int) strpos($html, 'action="'.route('app.security.password').'"'))
        ->and($html)->not->toContain('<div class="vy-flash is-err vy-fade" role="alert">'.$message);
});

it('⚠️ shows a wrong two-factor code once, under the code — not also at the top of the page (the review)', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, [], 'Property Owner');
    test()->actingAs($me)->from(route('app.security'))->post(route('app.security.2fa.enable'))->assertRedirect(route('app.security'));
    $form = sec5Hidden((string) test()->actingAs($me)->get(route('app.security'))->getContent(), route('app.security.2fa.confirm'));

    // 📖 before: the confirm form carried no `_form`, so its error fell to the page's catch-all banner AND showed under the field
    test()->actingAs($me)->from(route('app.security'))->post(route('app.security.2fa.confirm'), $form + ['code' => '000000'])
        ->assertRedirect(route('app.security'));
    $html = (string) test()->actingAs($me)->get(route('app.security'))->getContent();

    // once, and in its place: after the code box — never the top banner (SC-M20: a count alone was satisfied by the banner)
    $at = strpos($html, 'That code isn’t right.');
    expect(substr_count($html, 'That code isn’t right.'))->toBe(1)
        ->and($at)->toBeGreaterThan((int) strpos($html, 'id="tf_code"'))
        ->and($html)->not->toContain('<div class="vy-flash is-err vy-fade" role="alert">That code isn’t right.');
});

it('offers the export as choice tiles with visible labels — no native dropdowns', function () {
    $src = sec5Src();

    expect($src)->not->toContain('<select name="format"')->not->toContain('<select name="range"')
        ->toContain('<x-operator.choices name="format"')->toContain('<x-operator.choices name="range"')
        ->toContain('<label class="vy-sx-label" for="vy-sec-from">From</label>');
});
