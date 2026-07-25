<?php

declare(strict_types=1);

use App\Models\Owner;
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

// ── 1. The page renders for owner AND sub-user; deactivate is primary-owner-only ─────────────────────
it('renders Security for the owner and a sub-user; deactivate only for the primary owner', function () {
    $c = securityCast();

    foreach (['owner', 'accountant'] as $k) {
        $res = $this->actingAs($c[$k])->get('/owner/security');
        $res->assertOk();
        assertNoRawBlade($res);
        $res->assertSee('Account protection');
        $res->assertSee('Change password');
        $res->assertSee('Two-factor authentication');
        $res->assertSee('Active sessions');
    }

    $this->actingAs($c['owner'])->get('/owner/security')->assertSee('Deactivate account');
    $this->actingAs($c['accountant'])->get('/owner/security')->assertDontSee('Deactivate account');
});

// ── 2. The protection checklist reflects TRUE state (no fake score) ──────────────────────────────────
it('derives the protection checklist from real state (2FA off by default)', function () {
    $c = securityCast();

    $this->actingAs($c['owner'])->get('/owner/security')
        ->assertSee('Add a second layer beyond your password.') // 2FA is off → the honest nudge
        ->assertDontSee('/100')                                  // never a fabricated score
        ->assertDontSee('Suspicious login');
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
