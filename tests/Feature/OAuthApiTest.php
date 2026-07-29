<?php

use App\Models\AuditEvent;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerUser;
use App\Models\User;
use App\Services\OAuthConnectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

// OAuth2 · Passport · Phase 3 — the scoped, TENANT + OWNER-isolated read API + revoke.
// ⚠️ The security crux: a token sees ONLY its owner's data, ONLY its granted scope — proven both ways —
// can never over-grant beyond the owner's role, and a revoked token is rejected immediately.

/** A portal user linked to an Owner (primary) that owns unit[$i]. Returns [user, owner]. */
function apiOwner(array $s, int $i): array
{
    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    $s['units'][$i]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);

    return [$user, $owner];
}

// ── A scoped token reads ONLY its owner's data (owner isolation, fail closed) ──────────────────────
it('returns only the token owner’s bookings, never another owner’s', function () {
    $s = seedScenario(2);
    [$userA] = apiOwner($s, 0);
    [$userB] = apiOwner($s, 1);
    $resA = book($s, ['unit_id' => $s['units'][0]->id]);
    $resB = book($s, ['unit_id' => $s['units'][1]->id]);

    Passport::actingAs($userA, ['read:bookings'], 'api');
    $refs = collect($this->getJson('/api/owner/bookings')->assertOk()->json('data'))->pluck('reference');

    expect($refs)->toContain($resA->reference)      // 🔒 A's own booking…
        ->and($refs)->not->toContain($resB->reference);  // 🔒 …never B's
});

// ── SCOPE isolation — a read:bookings token CANNOT read statements (wrong scope → 403) ─────────────
it('rejects a token that lacks the endpoint’s scope', function () {
    $s = seedScenario(1);
    [$userA] = apiOwner($s, 0);

    Passport::actingAs($userA, ['read:bookings'], 'api');   // NO read:statements
    $this->getJson('/api/owner/statements')->assertForbidden();

    // …and the same token CAN reach the endpoint it IS scoped for.
    $this->getJson('/api/owner/bookings')->assertOk();
});

// ── NO OVER-GRANT — a token can never exceed the owner's ROLE ──────────────────────────────────────
it('refuses a scope the owner’s role cannot back, but allows one it can', function () {
    $s = seedScenario(1);
    [$primary, $owner] = apiOwner($s, 0);
    // An ACCOUNTANT sub-user: has financials.view (statements) but NOT calendar.view (bookings/units).
    $accountant = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $accountant->id, 'status' => 'active']);
    OwnerUser::create(['owner_id' => $owner->id, 'user_id' => $accountant->id, 'role' => 'accountant', 'status' => 'active']);

    // read:bookings needs calendar.view → the accountant role can't back it → 403 (no over-grant).
    Passport::actingAs($accountant, ['read:bookings'], 'api');
    $this->getJson('/api/owner/bookings')->assertForbidden();

    // read:statements needs financials.view → the accountant DOES hold it → 200 (the gate is precise, not blanket).
    Passport::actingAs($accountant, ['read:statements'], 'api');
    $this->getJson('/api/owner/statements')->assertOk();
});

// ── A non-owner token reaches NO owner data (fail closed) ──────────────────────────────────────────
it('refuses a token whose user is not linked to any owner', function () {
    seedScenario(1);
    $stranger = User::factory()->create();   // a real user, but not an owner

    Passport::actingAs($stranger, ['read:bookings'], 'api');
    $this->getJson('/api/owner/bookings')->assertForbidden();
});

// ── The units endpoint never leaks arrival secrets (#33) ──────────────────────────────────────────
it('returns unit facts but never door codes or Wi-Fi', function () {
    $s = seedScenario(1);
    [$userA] = apiOwner($s, 0);
    $s['units'][0]->update(['door_code' => '9999', 'wifi_password' => 'hunter2', 'code' => 'U-101']);

    Passport::actingAs($userA, ['read:units'], 'api');
    $body = $this->getJson('/api/owner/units')->assertOk()->assertSee('U-101')->getContent();

    expect($body)->not->toContain('9999')->and($body)->not->toContain('hunter2');
});

// ── ⚠️ REVOKE is IMMEDIATE — a revoked token is rejected on the next request ──────────────────────
it('rejects a revoked token immediately', function () {
    $s = seedScenario(1);
    [$userA] = apiOwner($s, 0);

    // A REAL token (personal-access grant) — a usable Bearer backed by a DB row we can revoke. Creating the
    // personal-access client registers it (oauth_personal_access_clients), so createToken finds it.
    app(ClientRepository::class)->createPersonalAccessGrantClient('Test PAC');
    $result = $userA->createToken('Acme', ['read:units']);
    $jwt = $result->accessToken;
    $clientId = (string) $result->token->client_id;

    // It works…
    $this->withToken($jwt)->getJson('/api/owner/units')->assertOk();

    // …the owner disconnects it → every token for that client is revoked (proven at the DB)…
    $revoked = app(OAuthConnectionService::class)->revoke($userA, $clientId);
    expect($revoked)->toBeGreaterThan(0)
        ->and($result->token->fresh()->revoked)->toBeTrue();

    // …and the SAME token is now rejected (401), immediately. (forgetGuards simulates the fresh guard a real
    // next HTTP request gets — the test harness would otherwise reuse the guard's cached user from above.)
    $this->app['auth']->forgetGuards();
    $this->withToken($jwt)->getJson('/api/owner/units')->assertUnauthorized();
});

// ── The owner's revoke is a self-only, re-authed, AUDITED (#20) controller POST ───────────────────
it('revokes a connected app through the audited owner controller (self-only, re-auth)', function () {
    $s = seedScenario(1);
    [$userA] = apiOwner($s, 0);
    app(ClientRepository::class)->createPersonalAccessGrantClient('Test PAC');
    $result = $userA->createToken('Acme', ['read:units']);
    $clientId = (string) $result->token->client_id;

    $this->actingAs($userA)->post('/owner/security/connected-apps/revoke', [
        'client_id' => $clientId,
        'current_password' => 'password',   // re-auth (the factory password)
    ])->assertRedirect();

    expect($result->token->fresh()->revoked)->toBeTrue();
    expect(AuditEvent::query()->where('action', 'owner.connected_app_revoked')->exists())->toBeTrue();

    // Wrong password → refused (re-auth holds); nothing revoked.
    $result2 = $userA->createToken('Acme2', ['read:units']);
    $this->actingAs($userA)->post('/owner/security/connected-apps/revoke', [
        'client_id' => (string) $result2->token->client_id,
        'current_password' => 'wrong-password',
    ])->assertSessionHasErrors('current_password');
    expect($result2->token->fresh()->revoked)->toBeFalse();
});
