<?php

use App\Models\User;
use App\Services\OAuthClientRegistrar;
use App\Support\OAuth\OAuthScopes;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

// OAuth2 · Passport · Phase 1 — the client + scope model (additive; least-privilege, read-first).

// ── The scope catalog is least-privilege, read-first, and maps to real owner caps ─────────────────
it('defines exactly the three least-privilege read scopes, mapped to owner capabilities', function () {
    expect(OAuthScopes::all())->toBe(['read:bookings', 'read:statements', 'read:units']);

    // Each scope maps to a real owner-team capability (the "no over-grant" mapping).
    expect(OAuthScopes::capabilityFor('read:bookings'))->toBe(OwnerTeamRole::CAP_CALENDAR)
        ->and(OAuthScopes::capabilityFor('read:statements'))->toBe(OwnerTeamRole::CAP_FINANCIALS)
        ->and(OAuthScopes::capabilityFor('read:units'))->toBe(OwnerTeamRole::CAP_CALENDAR)
        ->and(OAuthScopes::capabilityFor('write:anything'))->toBeNull();   // unknown → fail closed

    // Passport knows only these scopes (no write / client-credentials scope leaked in).
    expect(Passport::scopeIds())->toBe(['read:bookings', 'read:statements', 'read:units']);
});

// ── A CONFIDENTIAL client registers with a secret; a PUBLIC client is PKCE (no secret) ────────────
it('registers a confidential authorization-code client with a secret', function () {
    $client = app(OAuthClientRegistrar::class)->register('Acme Analytics', ['https://acme.test/callback'], confidential: true);

    expect($client->exists)->toBeTrue()
        ->and($client->name)->toBe('Acme Analytics')
        ->and($client->redirect_uris)->toContain('https://acme.test/callback')
        ->and($client->confidential())->toBeTrue()
        ->and($client->plainSecret)->not->toBeNull();   // shown ONCE at creation
});

it('registers a public client with NO secret (PKCE required)', function () {
    $client = app(OAuthClientRegistrar::class)->register('Acme Mobile', ['https://acme.test/mobile'], confidential: false);

    expect($client->confidential())->toBeFalse()
        ->and($client->secret)->toBeNull();   // a public client carries no secret — it must use PKCE
});

// ── The platform-admin command registers a client (the real admin mechanism) ──────────────────────
it('registers a client through the platform-admin command', function () {
    $this->artisan('oauth:register-client', ['name' => 'CLI App', 'redirect' => 'https://cli.test/cb'])
        ->assertExitCode(0);

    expect(Client::query()->where('name', 'CLI App')->exists())->toBeTrue();
});

// ── A token carries ONLY its granted scopes (scope-limiting at the token level) ────────────────────
it('issues a token that carries only its granted scopes', function () {
    $user = User::factory()->create();

    Passport::actingAs($user, ['read:bookings']);

    // The token can do exactly what it was granted — never more.
    expect($user->tokenCan('read:bookings'))->toBeTrue()
        ->and($user->tokenCan('read:statements'))->toBeFalse()
        ->and($user->tokenCan('read:units'))->toBeFalse();
});
