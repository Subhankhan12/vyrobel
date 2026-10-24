<?php

declare(strict_types=1);

use App\Models\AuditEvent;
use App\Models\LoginActivity;
use App\Models\User;
use App\Support\Security\SecurityScore;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(DatabaseTransactions::class);

// ⚠️⚠️ SECURITY, 2026-10-24 (the founder's design, "this is what is missing inside our security page … color and most of
// important things"). Both Security pages — the back office's and the owner portal's — on ONE design: a score derived from
// real settings, three columns, the warm colours, compact rows with dialogs. Beneath the look, the defects the audit found:
// the owner page's password boxes all but invisible, one password error printed under every field (D-OWNER-SEC-ERRORS), and
// login approval switched on while every email is discarded — a held sign-in nobody can approve. Each test failed first.

// ⚠️ The kit's class names are in the stylesheet on every page, so an assertion looks for the ELEMENT
// (`<section class="vy-sx-alert`), never the bare class name — the first draft did, and read its own needle.
function sxPage(string $route, User $user): string
{
    return (string) test()->actingAs($user)->get(route($route))->assertOk()->getContent();
}

/** A sign-in refused after too many wrong passwords, as `RecordAuthActivity::lockout()` writes it. */
function sxLockout(string $email, int $daysAgo = 1, string $ip = '82.14.7.9', string $guard = 'web'): LoginActivity
{
    return LoginActivity::create([
        'event' => 'lockout',
        'guard' => $guard,
        'identifier' => $email,
        'ip' => $ip,
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Safari/537.36',
        'occurred_at' => now()->subDays($daysAgo),
    ]);
}

/**
 * The hidden fields the page's own form posts to $action, so a test sends what a browser sends.
 *
 * @return array<string, string>
 */
function sxHidden(string $html, string $action): array
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

/** Make the app believe email really goes out (a real SMTP host), with nothing actually sent. */
function sxMailOn(): void
{
    Mail::fake();
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.hostinger.com']);
}

// ── THE SCORE — one rule for both pages, every point a real setting ──────────────────────────────────────────────

it('⚠️⚠️ scores the six real protections out of 100, and the points add up to the ring', function () {
    expect(array_sum(SecurityScore::WEIGHTS))->toBe(100);

    $all = array_map(fn (): array => ['done' => true, 'offered' => true], SecurityScore::WEIGHTS);
    $full = SecurityScore::from($all);
    expect($full['num'])->toBe(100)->and($full['tier'])->toBe('Strong')->and($full['available'])->toBe(0)
        ->and($full['caption'])->toBe('All protections are on — your account is fully secured.');
});

it('⚠️⚠️ never counts a protection the account cannot turn on — the rest share the 100, and still add up', function () {
    $state = [
        'password' => ['done' => true, 'offered' => true],
        'two_factor' => ['done' => false, 'offered' => true],
        'recovery_codes' => ['done' => false, 'offered' => true],
        'passkey' => ['done' => false, 'offered' => true],
        'login_approval' => ['done' => false, 'offered' => false],
        'login_alerts' => ['done' => false, 'offered' => false],
    ];
    $score = SecurityScore::from($state);
    $pts = array_column($score['protections'], 'pts', 'key');

    // 25 / 30 / 15 / 15 of 85 → 29.4 · 35.3 · 17.6 · 17.6, rounded so the four still make exactly 100
    expect($pts)->toBe(['password' => 29, 'two_factor' => 35, 'recovery_codes' => 18, 'passkey' => 18])
        ->and($score['num'])->toBe(29)->and($score['available'])->toBe(71)
        ->and($score['on'])->toBe(1)->and($score['total'])->toBe(4)->and($score['tier'])->toBe('Basic')
        ->and($score['caption'])->toBe('Turn on two-factor to strengthen your account.');
});

it('names the next real step in the caption, and lists only what is still off', function () {
    $state = array_map(fn (): array => ['done' => true, 'offered' => true], SecurityScore::WEIGHTS);
    $state['passkey']['done'] = false;
    $score = SecurityScore::from($state);

    expect($score['num'])->toBe(85)->and($score['tier'])->toBe('Strong')
        ->and($score['caption'])->toBe('Add a passkey for a faster, safer sign-in.')
        ->and(array_column(array_filter($score['protections'], fn (array $p): bool => ! $p['done']), 'key'))->toBe(['passkey']);
});

// ── THE BACK OFFICE PAGE ─────────────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ shows the back office its derived score — ring, tier and the four tiles — in three columns', function () {
    $s = seedScenario(1);
    $html = sxPage('app.security', operatorUser($s, [], 'Property Owner'));

    // password + login alerts (on by default) of the five it can turn on here — login approval waits for email
    expect($html)->toContain('<span class="vy-sx-ring-n">33</span>')
        ->toContain('<span class="vy-sx-tier">Basic</span>')
        ->toContain('<span class="vy-sx-tile-n">2/5</span><span class="vy-sx-tile-l">Protections on</span>')
        ->toContain('<span class="vy-sx-tile-n">67</span><span class="vy-sx-tile-l">Points available</span>')
        ->toContain('<span class="vy-sx-tile-l">Trusted devices</span>')
        ->toContain('<span class="vy-sx-tile-l">Connected apps</span>')
        ->toContain('<div class="vy-sx-cols">');
});

it('⚠️ lists each protection still off with its points and a way to the real control', function () {
    $s = seedScenario(1);
    $html = sxPage('app.security', operatorUser($s, [], 'Property Owner'));

    expect($html)->toContain('data-protection="two_factor"')->toContain('<span class="vy-sx-pts">+33 pts</span>')
        ->toContain('data-protection="passkey"')->toContain('href="#sx-passkeys"')
        ->not->toContain('data-protection="password"');
});

it('⚠️⚠️ gives the owner page the SAME score for the same settings — one rule, never two', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s, 0);
    $staff = operatorUser($s, [], 'Property Owner');

    $ownerHtml = sxPage('owner.security', $owner);
    expect($ownerHtml)->toContain('<span class="vy-sx-ring-n">33</span>')
        ->toContain('<span class="vy-sx-tile-n">2/5</span><span class="vy-sx-tile-l">Protections on</span>')
        ->toContain('<div class="vy-sx-cols">')
        ->and(sxPage('app.security', $staff))->toContain('<span class="vy-sx-ring-n">33</span>');
});

// ── SIGN-IN PROBLEMS — real lockouts on your own address, never invented ─────────────────────────────────────────

it('⚠️⚠️ shows no warning when nothing happened — never a sample "suspicious login"', function () {
    $s = seedScenario(1);

    expect(sxPage('app.security', operatorUser($s, [], 'Property Owner')))->not->toContain('<section class="vy-sx-alert')
        ->and(sxPage('owner.security', ownerRoleUser($s, 0)))->not->toContain('<section class="vy-sx-alert')->not->toContain('Suspicious login');
});

it('⚠️⚠️ warns about sign-ins refused on YOUR address in the last 90 days — device, IP and time, no invented place', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, ['email' => 'sx-me-'.Str::lower(Str::random(6)).'@example.com'], 'Property Owner');
    $other = operatorUser($s, [], 'Front Desk');

    sxLockout(strtoupper($me->email), 2, '82.14.7.9');        // the typed case does not matter
    sxLockout($me->email, 95, '10.0.0.95');                   // older than the 90 days the log keeps
    sxLockout((string) $other->email, 1, '10.0.0.66');        // someone else's
    sxLockout($me->email, 1, '10.0.0.77', 'admin');           // the platform console's sign-in, not this one

    $html = sxPage('app.security', $me);
    expect($html)->toContain('<section class="vy-sx-alert')->toContain('Chrome on Windows')->toContain('82.14.7.9')
        ->toContain('Secure my account')->toContain('This was me')
        ->not->toContain('10.0.0.95')->not->toContain('10.0.0.66')->not->toContain('10.0.0.77')
        // the other three are outside it — 95 days old, someone else's, the console's — so the card counts ONE refusal
        ->not->toContain('more attempt');
});

it('⚠️⚠️ "This was me" hides that warning for you only, is recorded, and a NEW refusal shows again', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, [], 'Property Owner');
    sxLockout((string) $me->email, 1, '82.14.7.9');

    test()->actingAs($me)->from(route('app.security'))->post(route('app.security.alerts.seen'))
        ->assertRedirect(route('app.security'))->assertSessionHas('security_notice');
    expect($me->fresh()->security_alerts_seen_at)->not->toBeNull()
        ->and(AuditEvent::query()->where('action', 'user.security_alert_dismissed')->exists())->toBeTrue()
        ->and(sxPage('app.security', $me->fresh()))->not->toContain('<section class="vy-sx-alert');

    test()->travel(1)->minutes();
    sxLockout((string) $me->email, 0, '82.14.7.10');
    expect(sxPage('app.security', $me->fresh()))->toContain('<section class="vy-sx-alert')->toContain('82.14.7.10')->not->toContain('82.14.7.9');
});

it('lets an owner see and dismiss the same warning on the owner page', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s, 0);
    sxLockout((string) $owner->email, 3, '82.14.7.11');

    expect(sxPage('owner.security', $owner))->toContain('<section class="vy-sx-alert')->toContain('82.14.7.11');
    test()->actingAs($owner)->from(route('owner.security'))->post(route('owner.security.alerts.seen'))
        ->assertRedirect(route('owner.security'));
    expect(sxPage('owner.security', $owner->fresh()))->not->toContain('<section class="vy-sx-alert');
});

// ── THE OWNER PAGE'S OWN DEFECTS ─────────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ gives every owner password box the shared field with its eye — no bare, near-invisible box', function () {
    $src = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents(resource_path('views/livewire/owner/owner-security.blade.php')));

    // 🖥 before: five bare `type="password" class="vy-input"` boxes, 184 px wide, 70% white with a white border on a white card
    expect($src)->not->toContain('type="password"')->toContain('<x-password-field');
});

it('⚠️⚠️ shows a wrong owner password once, in the dialog it came from — not under every field (D-OWNER-SEC-ERRORS)', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s, 0);
    $owner->forceFill(['password' => 'owner-pass-9'])->save();

    // posted with the hidden fields the dialog's own form carries — a marker removed from the view must change the request
    $form = sxHidden(sxPage('owner.security', $owner), route('owner.security.sessions.signout'));
    expect($form)->toBe(['_form' => 'signout']);
    test()->actingAs($owner)->from(route('owner.security'))
        ->post(route('owner.security.sessions.signout'), $form + ['current_password' => 'wrong'])
        ->assertRedirect(route('owner.security'));
    $html = sxPage('owner.security', $owner->fresh());

    expect(substr_count($html, e(__('validation.current_password'))))->toBe(1)
        ->and($html)->toContain('signOutOpen: true');
});

it('⚠️⚠️ refuses to switch login approval ON while email is not being sent — nobody could approve the sign-in', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s, 0);
    $owner->forceFill(['password' => 'owner-pass-9', 'require_login_approval' => false])->save();

    // the test mailer discards every message, as production's does today
    test()->actingAs($owner)->from(route('owner.security'))
        ->post(route('owner.security.login-approval'), ['enabled' => '1', 'current_password' => 'owner-pass-9'])
        ->assertRedirect(route('owner.security'))->assertSessionHas('security_error');
    expect($owner->fresh()->require_login_approval)->toBeFalse()
        // the approval note's own sentence — the login-alerts row also says email isn't sent, so the bare phrase proves nothing
        ->and(sxPage('owner.security', $owner->fresh()))->toContain('so an approval link couldn’t reach you. This stays off until it is.');

    // switching it OFF always works — the way out is never blocked
    $owner->forceFill(['require_login_approval' => true])->save();
    test()->actingAs($owner)->post(route('owner.security.login-approval'), ['enabled' => '0', 'current_password' => 'owner-pass-9'])
        ->assertRedirect();
    expect($owner->fresh()->require_login_approval)->toBeFalse();

    // and with email really going out, it switches on
    sxMailOn();
    test()->actingAs($owner)->post(route('owner.security.login-approval'), ['enabled' => '1', 'current_password' => 'owner-pass-9'])
        ->assertRedirect();
    expect($owner->fresh()->require_login_approval)->toBeTrue();
});

it('⚠️ says how many recovery codes are left once two-factor is on', function () {
    $s = seedScenario(1);
    $me = operatorUser($s, [], 'Property Owner');
    $me->forceFill([
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_recovery_codes' => [['code' => 'aaaa-bbbb', 'used_at' => null], ['code' => 'cccc-dddd', 'used_at' => now()->toIso8601String()]],
        'two_factor_confirmed_at' => now(),
    ])->save();

    expect(sxPage('app.security', $me->fresh()))->toContain('1 of 2 recovery codes left');
});

it('⚠️ keeps every Security dialog under the dialog contract — the census finds the kit’s dialogs', function () {
    // the census discovers a dialog by its backdrop class; without `vy-sx-scrim` in it, every new dialog here would escape
    // the role / name / Escape / focus-trap checks while they all stayed green
    $census = collect(vyDialogCensus());

    expect($census->where('file', 'livewire/operator/operator-security.blade.php')->count())->toBeGreaterThanOrEqual(6)
        ->and($census->where('file', 'livewire/owner/owner-security.blade.php')->count())->toBeGreaterThanOrEqual(6)
        ->and($census->where('file', 'livewire/partials/two-factor-off-dialog.blade.php')->count())->toBe(1)
        ->and($census->where('file', 'components/auth/passkey-remove-dialog.blade.php')->count())->toBe(1);
});
