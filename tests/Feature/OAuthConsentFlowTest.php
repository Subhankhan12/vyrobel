<?php

use App\Models\AuditEvent;
use App\Models\User;
use App\Services\OAuthClientRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use Laravel\Passport\Token;

uses(RefreshDatabase::class);

// OAuth2 · Passport · Phase 2 — the authorization-code + PKCE flow + the branded consent screen.
// Mutation-proven: a denied consent issues NO token; an approved consent issues a token with ONLY the
// requested scopes; PKCE is enforced for a public client.

/** base64url(sha256(verifier)) — the PKCE code challenge. */
function pkceChallenge(string $verifier): string
{
    return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
}

/** A public (PKCE) client + a logged-in user; returns the client + the authorize query params. */
function beginAuthorize(string $verifier, string $scope = 'read:bookings'): array
{
    $client = app(OAuthClientRegistrar::class)->register('Acme Insights', ['https://acme.test/callback'], confidential: false);
    $user = User::factory()->create();
    test()->actingAs($user);

    $query = http_build_query([
        'client_id' => $client->getKey(),
        'redirect_uri' => 'https://acme.test/callback',
        'response_type' => 'code',
        'scope' => $scope,
        'code_challenge' => pkceChallenge($verifier),
        'code_challenge_method' => 'S256',
    ]);

    return [$client, $user, $query];
}

// ── The consent screen shows exactly the requested scopes ─────────────────────────────────────────
it('shows the branded consent screen with exactly the requested scopes', function () {
    [$client, $user, $query] = beginAuthorize('ver-'.str_repeat('a', 60), 'read:bookings read:units');

    $this->get('/oauth/authorize?'.$query)
        ->assertOk()
        ->assertSee('Acme Insights')
        ->assertSee('Read your bookings', false)     // the human scope description
        ->assertSee('Read your units', false)
        ->assertDontSee('Read your statements', false); // NOT requested → not shown (no over-grant surprise)

    expect(session()->has('authToken'))->toBeTrue();
});

// ── DENY → no token issued ────────────────────────────────────────────────────────────────────────
it('issues NO token when the owner denies consent', function () {
    [$client, $user, $query] = beginAuthorize('ver-'.str_repeat('b', 60));

    $this->get('/oauth/authorize?'.$query)->assertOk();
    $authToken = session()->get('authToken');

    $this->delete('/oauth/authorize', ['auth_token' => $authToken])
        ->assertRedirect();   // back to the app with error=access_denied, NO code

    expect(Token::query()->count())->toBe(0);   // 🔒 nothing granted
});

// ── APPROVE + PKCE → a token carrying ONLY the requested scope ────────────────────────────────────
it('issues a token with only the requested scope after approval (PKCE)', function () {
    $verifier = 'ver-'.str_repeat('c', 60);
    [$client, $user, $query] = beginAuthorize($verifier, 'read:bookings');

    $this->get('/oauth/authorize?'.$query)->assertOk();
    $authToken = session()->get('authToken');

    // Approve → redirect to the app's callback with an auth code.
    $approve = $this->post('/oauth/authorize', ['auth_token' => $authToken])->assertRedirect();
    parse_str((string) parse_url($approve->headers->get('Location'), PHP_URL_QUERY), $cb);
    expect($cb['code'] ?? null)->not->toBeNull();

    // Exchange the code (+ PKCE verifier) for a token.
    $token = $this->post('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $client->getKey(),
        'redirect_uri' => 'https://acme.test/callback',
        'code' => $cb['code'],
        'code_verifier' => $verifier,
    ])->assertOk()->json();

    expect($token['access_token'] ?? null)->not->toBeNull()
        ->and($token['token_type'] ?? null)->toBe('Bearer');

    // The issued token carries ONLY read:bookings — never a scope the owner didn't approve.
    $issued = Token::query()->where('user_id', $user->id)->latest('created_at')->firstOrFail();
    expect($issued->scopes)->toBe(['read:bookings']);

    // The grant is audited (#20) — which app + which scopes, never a token value.
    $audit = AuditEvent::query()->where('action', 'owner.connected_app_token_issued')->latest()->first();
    expect($audit)->not->toBeNull()
        ->and($audit->after['scopes'] ?? null)->toBe(['read:bookings'])
        ->and($audit->after['app'] ?? null)->toBe('Acme Insights');
});

// ── PKCE is ENFORCED — a wrong verifier is rejected (a public client can't skip PKCE) ─────────────
it('rejects the token exchange when the PKCE verifier is wrong', function () {
    $verifier = 'ver-'.str_repeat('d', 60);
    [$client, $user, $query] = beginAuthorize($verifier, 'read:bookings');

    $this->get('/oauth/authorize?'.$query)->assertOk();
    $approve = $this->post('/oauth/authorize', ['auth_token' => session()->get('authToken')])->assertRedirect();
    parse_str((string) parse_url($approve->headers->get('Location'), PHP_URL_QUERY), $cb);

    // A WRONG verifier → the exchange fails (PKCE holds); no token.
    $this->post('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $client->getKey(),
        'redirect_uri' => 'https://acme.test/callback',
        'code' => $cb['code'],
        'code_verifier' => 'the-wrong-verifier-'.str_repeat('x', 50),
    ])->assertStatus(400);

    expect(Token::query()->count())->toBe(0);
});
