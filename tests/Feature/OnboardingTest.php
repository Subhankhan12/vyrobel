<?php

use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// PHASE 7a · Step 2 — the public self-signup wizard. It provisions a brand-new operator org atomically (via
// TenantProvisioningService) and logs the founder in; hardened + neutral on the public surface.

/** A valid signup POST body. */
function signupPayload(array $overrides = []): array
{
    return array_merge([
        'org_name' => 'Seaside Group',
        'location' => 'Faro',
        'property_type' => 'villa',
        'currency' => 'EUR',
        'property_name' => 'Seaside Villas',
        'name' => 'Ada Okafor',
        'email' => 'ada@newco.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ], $overrides);
}

// ── 1. ✅ END-TO-END — signup creates a real org, logs the founder in, and they can operate it ──────
it('provisions a new org, logs the founder in, and lets them operate it', function () {
    $response = $this->post('/signup', signupPayload());

    // Founder is authenticated and landed on the Portfolio with a real welcome.
    $this->assertAuthenticated();
    $response->assertRedirect('/app/portfolio');

    $org = Organization::query()->where('name', 'Seaside Group')->firstOrFail();
    expect((string) $org->status)->toBe('active')
        ->and(User::query()->where('email', 'ada@newco.test')->exists())->toBeTrue();

    // The founder can actually operate the org — the #26 back-office gate + their provisioned Property Owner
    // role let them into /app, and the Portfolio honestly shows the 1 property "Finish setup to go live".
    $this->get('/app/portfolio')
        ->assertOk()
        ->assertSee('Seaside Villas')
        ->assertSee('Finish setup to go live')     // real state — 0 units yet, not a faked "ready"
        ->assertSee('Welcome to Vyrobel');

    // They hold the manager-gated screens (proves the working RBAC state through the real gate).
    $this->get('/app/settings')->assertOk();       // can:properties.manage
    $this->get('/app/roles')->assertOk();          // can:users.manage
});

// ── 2. 🔒 NO ENUMERATION — a duplicate email fails NEUTRALLY, and nothing is created ────────────────
it('handles a duplicate email neutrally and creates nothing', function () {
    User::factory()->create(['email' => 'taken@newco.test', 'status' => 'active']);
    $orgsBefore = Organization::query()->count();

    $response = $this->post('/signup', signupPayload(['email' => 'taken@newco.test']));

    $response->assertSessionHasErrors('email');
    // The message must NOT confirm the account exists (no "already been taken").
    $message = session('errors')->first('email');
    expect($message)->not->toContain('taken')->and($message)->not->toContain('exists');

    // No org was provisioned, and the request is unauthenticated.
    expect(Organization::query()->count())->toBe($orgsBefore);
    $this->assertGuest();
});

// ── 3. 🪤 VALIDATION — bad input creates nothing (atomic surface stays clean) ───────────────────────
it('rejects invalid input without provisioning anything', function () {
    $orgsBefore = Organization::query()->count();

    $this->post('/signup', signupPayload(['property_type' => 'not_a_type', 'currency' => 'XYZ', 'email' => 'not-an-email', 'password_confirmation' => 'mismatch']))
        ->assertSessionHasErrors(['property_type', 'currency', 'email', 'password']);

    expect(Organization::query()->count())->toBe($orgsBefore);
    $this->assertGuest();
});

// ── 4. 🔒 ISOLATION / UNREGRESSED — a signup never touches an existing tenant ───────────────────────
it('creates an isolated org without disturbing an existing tenant', function () {
    $existing = seedScenario(1);
    $existingCount = Property::query()->withoutGlobalScopes()->count();

    $this->post('/signup', signupPayload())->assertRedirect('/app/portfolio');

    // Exactly one property was added; the existing org's property is untouched.
    expect(Property::query()->withoutGlobalScopes()->count())->toBe($existingCount + 1)
        ->and(Property::query()->withoutGlobalScopes()->whereKey($existing['property']->id)->exists())->toBeTrue();

    // The founder belongs only to the new org (not the existing one).
    $founder = User::query()->where('email', 'ada@newco.test')->firstOrFail();
    expect($founder->memberships()->pluck('organization_id')->all())
        ->not->toContain((string) $existing['org']->id);
});

// ── 5. an authenticated user is bounced from the signup page ───────────────────────────────────────
it('redirects an already-authenticated user away from signup', function () {
    $this->post('/signup', signupPayload());   // now authenticated as the founder
    $this->get('/signup')->assertRedirect('/app/dashboard');
});
