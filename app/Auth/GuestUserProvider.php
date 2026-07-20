<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\Guest;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * The GUEST realm's user provider (Phase 4a · Sub-gate 1). Two deliberate departures from the stock
 * {@see EloquentUserProvider}, both forced by how a Guest differs from a User:
 *
 *   1. LOOKUP BY BLIND INDEX. `guests.email` is encrypted (non-deterministic → not queryable), so a guest is
 *      retrieved by {@see Guest::emailHash()} on `email_hash`, never by the plaintext email. Only a CLAIMED
 *      account (password set) is returned for login — an unclaimed booking-row can't authenticate.
 *   2. NO TENANT SCOPE. Guest auth runs BEFORE any tenant context is set (login is pre-tenant), and a guest's org
 *      is derived FROM their row afterwards — so every query here strips the {@see OrganizationScope}. This is the
 *      one legitimate cross-tenant read: it is auth, keyed on a secret hash, returning exactly one guest.
 *
 * It NEVER touches the `web` (operator/owner) provider — a Guest is not a User and this realm is separate.
 */
class GuestUserProvider extends EloquentUserProvider
{
    /** A guest by primary key — the per-request rehydrate from the session id (also pre-tenant). */
    public function retrieveById($identifier): ?Authenticatable
    {
        return Guest::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->whereKey($identifier)
            ->first();
    }

    /** A guest by "remember me" token — id + a constant-time token compare. */
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        $guest = Guest::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->whereKey($identifier)
            ->first();

        if ($guest === null) {
            return null;
        }

        $remember = (string) $guest->getRememberToken();

        return $remember !== '' && hash_equals($remember, (string) $token) ? $guest : null;
    }

    /** Persist a rotated remember-token WITHOUT the tenant scope (login is pre-tenant, so $user->save() can't run). */
    public function updateRememberToken(Authenticatable $user, $token): void
    {
        Guest::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->whereKey($user->getAuthIdentifier())
            ->update([$user->getRememberTokenName() => $token]);

        $user->setRememberToken($token);
    }

    /**
     * Retrieve a CLAIMED guest account by the blind-index hash of the credential email (never the plaintext).
     * Returns null for an unknown or unclaimed email; the password is checked by the inherited validateCredentials.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $email = $credentials['email'] ?? null;
        if (! is_string($email) || trim($email) === '') {
            return null;
        }

        return Guest::query()
            ->withoutGlobalScope(OrganizationScope::class)
            ->where('email_hash', Guest::emailHash($email))
            ->whereNotNull('password')   // only a claimed account can log in
            ->first();
    }
}
