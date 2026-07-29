<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;

uses(RefreshDatabase::class);

// OAuth2 · Passport · Phase 4 — the Security "Connected apps" surface (honest "Coming soon" → REAL).

/** An owner-linked portal user (so /owner/security resolves the owner realm). */
function connectedAppsOwner(array $s): User
{
    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);

    return $user;
}

// ── An authorized app appears with its exact scopes + a revoke; no longer "Coming soon" ───────────
it('lists an authorized app with its scopes on the Security page', function () {
    $s = seedScenario(1);
    $user = connectedAppsOwner($s);

    // A live token for this user under a named client → a real connected app.
    app(ClientRepository::class)->createPersonalAccessGrantClient('Acme Insights');
    $user->createToken('Acme Insights', ['read:bookings', 'read:units']);

    $this->actingAs($user)->get('/owner/security')
        ->assertOk()
        ->assertSee('Connected apps')
        ->assertSee('Acme Insights')
        ->assertSee('read:bookings')          // the exact scopes granted
        ->assertSee('read:units')
        ->assertSee('connected-apps/revoke', false);   // a real revoke form
});

// ── No apps → honest empty state (never a fabricated entry) ───────────────────────────────────────
it('shows an honest empty state when no apps are connected', function () {
    $s = seedScenario(1);
    $user = connectedAppsOwner($s);

    $this->actingAs($user)->get('/owner/security')
        ->assertOk()
        ->assertSee('No connected apps');
});
