<?php

declare(strict_types=1);

use App\Models\Owner;
use App\Models\TrustedDevice;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Owner account area · Gate 2 — the Security page.
 *
 * Per-USER (password / 2FA / sessions / export work for sub-users too); DEACTIVATE is PRIMARY-OWNER-ONLY (#42),
 * reversible + NON-DESTRUCTIVE. The protection list is DERIVED from true state (no fake score). Real sessions.
 */
function securityCast(): array
{
    $s = seedScenario(1);
    $owner = ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    return [
        'owner' => $owner,
        'accountant' => ownerSubUser($s, $ownerId, OwnerTeamRole::ACCOUNTANT),
        'view' => ownerSubUser($s, $ownerId, OwnerTeamRole::VIEW_ONLY),
        'scenario' => $s,
    ];
}

// ── Wave B — the Security page lists the user's real trusted devices (skip-2FA), self-only ─────────
it('lists the owner\'s real trusted devices on the Security page', function () {
    $c = securityCast();
    $owner = $c['owner'];

    // A real live trust + a revoked one (the revoked must NOT appear).
    TrustedDevice::create([
        'user_id' => (string) $owner->getKey(), 'token_hash' => hash('sha256', 'live-tok'),
        'label' => 'Chrome on macOS', 'expires_at' => now()->addDays(20), 'last_used_at' => now(),
    ]);
    TrustedDevice::create([
        'user_id' => (string) $owner->getKey(), 'token_hash' => hash('sha256', 'dead-tok'),
        'label' => 'Firefox on Linux', 'expires_at' => now()->addDays(20), 'revoked_at' => now(),
    ]);

    $this->actingAs($owner)->get('/owner/security')->assertOk()
        ->assertSee('Trusted devices')
        ->assertSee('Chrome on macOS')       // the live trust shows
        ->assertDontSee('Firefox on Linux'); // the revoked one does not
});

// ── 1. The page renders for owner AND sub-user; deactivate is primary-owner-only ─────────────────────
it('renders Security for the owner and a sub-user; deactivate only for the primary owner', function () {
    $c = securityCast();

    foreach (['owner', 'accountant'] as $k) {
        $res = $this->actingAs($c[$k])->get('/owner/security');
        $res->assertOk();
        assertNoRawBlade($res);
        $res->assertSee('Account security');
        $res->assertSee('Change password');
        $res->assertSee('Two-factor authentication');
        $res->assertSee('Active sessions');
    }

    $this->actingAs($c['owner'])->get('/owner/security')->assertSee('Deactivate account');
    $this->actingAs($c['accountant'])->get('/owner/security')->assertDontSee('Deactivate account');
});

// ── 2. The security SCORE is REAL — DERIVED from true protection state, never a fabricated number ─────
it('shows a real derived security score from true state (2FA off by default)', function () {
    $c = securityCast();

    // A fresh owner: only the password protection is on (2FA off) → score 40/100 = "Basic", 1 of 3 on.
    $this->actingAs($c['owner'])->get('/owner/security')
        ->assertSee('Account security')
        ->assertSee('2/5')                                       // Protections on — DERIVED (password + login alerts, which are always on for an owner; 2026-10-24: five offered — SecurityScore)
        ->assertSee('Basic')                                     // score 40 (<60) → Basic tier, from real state
        ->assertSee('Boost your score')
        ->assertSee('Turn on two-factor to strengthen your account.')   // the honest nudge (2FA off)
        ->assertDontSee('Suspicious login');                     // no fabricated security events
});

// ── 3. Active sessions — "sign out others" is password-gated + really deletes the rows ───────────────
it('signs out other sessions only with the correct password, and really deletes those rows', function () {
    $c = securityCast();
    $user = $c['owner'];
    $user->forceFill(['password' => 'sess-pass-1234'])->save();

    foreach (['other-a', 'other-b'] as $id) {
        DB::table('sessions')->insert([
            'id' => $id, 'user_id' => $user->id, 'ip_address' => '1.2.3.4',
            'user_agent' => 'Chrome', 'payload' => 'x', 'last_activity' => time(),
        ]);
    }

    // wrong password → nothing removed
    $this->actingAs($user)->post('/owner/security/sessions/sign-out-others', ['current_password' => 'nope'])
        ->assertSessionHasErrors('current_password');
    expect(DB::table('sessions')->whereIn('id', ['other-a', 'other-b'])->count())->toBe(2);

    // correct password → the other rows are gone
    $this->actingAs($user)->post('/owner/security/sessions/sign-out-others', ['current_password' => 'sess-pass-1234'])
        ->assertRedirect();
    expect(DB::table('sessions')->whereIn('id', ['other-a', 'other-b'])->count())->toBe(0);
});

// ── 4. DEACTIVATE — primary-owner-only, password-gated, reversible + NON-DESTRUCTIVE ─────────────────
it('refuses deactivate to a sub-user (#42)', function () {
    $c = securityCast();

    $this->actingAs($c['accountant'])->post('/owner/security/deactivate', ['current_password' => 'x'])->assertForbidden();
    $this->actingAs($c['view'])->post('/owner/security/deactivate', ['current_password' => 'x'])->assertForbidden();
});

it('pauses login on deactivate, keeps all data, and reactivation restores access', function () {
    $c = securityCast();
    $user = $c['owner'];
    $user->forceFill(['password' => 'owner-pass-1234'])->save();

    // wrong password → no change
    $this->actingAs($user)->post('/owner/security/deactivate', ['current_password' => 'nope'])
        ->assertSessionHasErrors('current_password');
    expect($user->fresh()->status)->toBe('active');

    // correct password → paused + signed out
    $this->actingAs($user)->post('/owner/security/deactivate', ['current_password' => 'owner-pass-1234'])
        ->assertRedirect(route('login'));

    $fresh = $user->fresh();
    expect($fresh->status)->toBe('disabled');
    expect($fresh->trashed())->toBeFalse();                                   // NOT deleted — non-destructive
    expect(Owner::query()->where('user_id', $user->id)->exists())->toBeTrue(); // financial/party data kept

    // a disabled account cannot sign in (login is active-only)
    $this->post('/login', ['email' => $user->email, 'password' => 'owner-pass-1234'])->assertSessionHasErrors();
    $this->assertGuest();

    // reactivation restores access
    $user->forceFill(['status' => 'active'])->save();
    $this->post('/login', ['email' => $user->email, 'password' => 'owner-pass-1234']);
    $this->assertAuthenticatedAs($user->fresh());
});

// ── 5. "Request my data" — a real owner-scoped download containing the acting user's profile ─────────
it('exports the owner data as an owner-scoped JSON download', function () {
    $c = securityCast();

    $res = $this->actingAs($c['owner'])->get('/owner/security/export');

    $res->assertOk();
    $res->assertHeader('content-type', 'application/json');
    expect($res->streamedContent())->toContain((string) $c['owner']->email);
});

// ── 6. The SHARED two-factor panel must not carry one page's layout width ────────────────────────
// The partial renders on BOTH /app/security and /owner/security. It hardcoded `max-width: 560px` — the
// operator page's column — so on the owner page, where every other card is full-width, the 2FA card
// rendered as a lone half-width outlier (560px beside eleven 1184px cards). The constraint belongs to the
// caller: the operator include wraps it, the owner include lets it fill. A shared partial cannot carry a
// layout constant that only one shell's design uses.
it('keeps the shared two-factor panel free of a caller-specific width', function () {
    $c = securityCast();

    $owner = (string) $this->actingAs($c['owner'])->get('/owner/security')->assertOk()->getContent();

    // The panel's own card must not constrain itself…
    expect($owner)->not->toContain('class="vy-card vy-fade" style="margin-bottom: 16px; max-width: 560px;"')
        // …and the owner page must still render the real panel.
        ->and($owner)->toContain('Two-factor authentication');
});
