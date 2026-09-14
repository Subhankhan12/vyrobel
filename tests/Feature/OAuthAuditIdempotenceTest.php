<?php

use App\Models\AuditEvent;
use App\Models\Owner;
use App\Models\User;
use App\Services\OAuthClientRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Token;

uses(RefreshDatabase::class);

/**
 * F-41 — `AuditOAuthTokenGranted` IS IDEMPOTENT BY DESIGN, NOT BY LUCK.
 *
 * ⚠️⚠️ WHY A REGISTRATION COUNT PROVES NOTHING HERE. FIX 19 removed the DUPLICATE REGISTRATION that was
 * delivering `AccessTokenCreated` to this listener twice, and the double write stopped — so counting
 * registrations now reports "one" and looks like a pass. But the listener still called `record()`
 * unconditionally: the deduplication was doing the guard's job. **This test REPLAYS the event instead** —
 * two dispatches of the SAME grant must leave exactly ONE audit row, whatever the bus does.
 *
 * ⚠️ A SECOND GRANT IS NOT A REPLAY. The guard is keyed on `tokenId`, so a genuinely different token must
 * still be audited — asserted below, because a guard that swallows real grants is worse than none.
 *
 * ⚠️ AUDIT ROWS ARE NEVER PRUNED ({@see RetentionPolicy}), so every row this file writes is rolled back by
 * `RefreshDatabase` rather than left behind.
 */
function oaiOwnerUser(): User
{
    $s = seedScenario(1);
    $user = operatorUser($s, [], 'Hotel Manager');

    Owner::query()->create([
        'organization_id' => $s['org']->id,
        'name' => 'Replay Owner',
        'type' => 'individual',
        'status' => 'active',
        'payout_currency' => 'EUR',
        'email' => 'replay-owner@example.test',
        'user_id' => $user->getKey(),
    ]);

    return $user;
}

/** Dispatch the event for a REAL token row, the way Passport does at the token exchange. */
function oaiDispatch(User $user, string $clientId, string $tokenId): void
{
    event(new AccessTokenCreated($tokenId, (string) $user->getKey(), $clientId));
}

it('writes ONE audit row when the same token grant is delivered twice', function () {
    $user = oaiOwnerUser();
    $client = app(OAuthClientRegistrar::class)->register('Replay App', ['https://replay.test/cb'], confidential: true);

    $tokenId = (string) Str::ulid();
    Token::query()->forceCreate([
        'id' => $tokenId, 'user_id' => (string) $user->getKey(), 'client_id' => $client->getKey(),
        'scopes' => ['read:bookings'], 'revoked' => false,
    ]);

    // ⚠️ TWO DISPATCHES OF THE SAME GRANT — a replay, not two grants.
    oaiDispatch($user, (string) $client->getKey(), $tokenId);
    oaiDispatch($user, (string) $client->getKey(), $tokenId);

    $rows = AuditEvent::withoutGlobalScopes()->where('action', 'owner.connected_app_token_issued')->get();

    expect($rows)->toHaveCount(1, 'a replayed grant must not append a second permanent audit row');
    expect($rows->first()->after['app'] ?? null)->toBe('Replay App');
});

it('still audits a genuinely DIFFERENT token grant', function () {
    $user = oaiOwnerUser();
    $client = app(OAuthClientRegistrar::class)->register('Replay App', ['https://replay.test/cb'], confidential: true);

    foreach (['a', 'b'] as $_) {
        $tokenId = (string) Str::ulid();
        Token::query()->forceCreate([
            'id' => $tokenId, 'user_id' => (string) $user->getKey(), 'client_id' => $client->getKey(),
            'scopes' => ['read:bookings'], 'revoked' => false,
        ]);
        oaiDispatch($user, (string) $client->getKey(), $tokenId);
    }

    // ⚠️ THE GUARD MUST NOT SWALLOW REAL GRANTS — two distinct tokens are two auditable events.
    expect(AuditEvent::withoutGlobalScopes()->where('action', 'owner.connected_app_token_issued')->count())->toBe(2);
});
