<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

/**
 * The owner-facing "Connected apps" backend (Wave D · OAuth · Phase 3) — lists the third-party apps a USER has
 * authorized and REVOKES one. SELF-ONLY by construction: every query is pinned to the given user's own tokens,
 * so a user can only ever see + revoke their OWN grants (never another user's).
 *
 * ⚠️ Revoke is IMMEDIATE: setting `revoked = true` makes Passport's token guard reject the access token on the
 * very next request (it checks the flag every time), and the linked refresh tokens are revoked too, so the app
 * can neither call the API nor mint a new token. Mutation-proven in the Phase 3 tests.
 */
class OAuthConnectionService
{
    /**
     * The apps this user has authorized — grouped by client from their LIVE (non-revoked, non-expired) tokens.
     *
     * @return list<array{client_id: string, name: string, scopes: list<string>, authorized_at: string|null}>
     */
    public function connectionsFor(User $user): array
    {
        return Token::query()
            ->where('user_id', $user->getKey())
            ->where('revoked', false)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->with('client')
            ->get()
            ->groupBy('client_id')
            ->map(function (Collection $group): array {
                /** @var Token $first */
                $first = $group->first();

                // Union of the scopes across this client's tokens, as a clean list<string>.
                $scopes = [];
                foreach ($group as $token) {
                    foreach ((array) ($token->scopes ?? []) as $scope) {
                        $scopes[] = (string) $scope;
                    }
                }
                $scopes = array_values(array_unique($scopes));
                sort($scopes);

                // The earliest token for this app = when the owner first authorized it (as a date string).
                $earliest = $group->sortBy('created_at')->first();
                $authorizedAt = $earliest instanceof Token && $earliest->created_at instanceof CarbonInterface
                    ? $earliest->created_at->toDateString()
                    : null;

                return [
                    'client_id' => (string) $first->client_id,
                    'name' => (string) ($first->client->name ?? 'Unknown app'),
                    'scopes' => $scopes,
                    'authorized_at' => $authorizedAt,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Revoke ALL of this user's tokens for one client — immediately. SELF-ONLY: scoped to `$user`'s tokens, so a
     * client_id from another user is a no-op. Revokes the linked refresh tokens too. Returns the number revoked.
     */
    public function revoke(User $user, string $clientId): int
    {
        $tokenIds = Token::query()
            ->where('user_id', $user->getKey())
            ->where('client_id', $clientId)
            ->pluck('id');

        if ($tokenIds->isEmpty()) {
            return 0;
        }

        RefreshToken::query()->whereIn('access_token_id', $tokenIds->all())->update(['revoked' => true]);

        return Token::query()->whereIn('id', $tokenIds->all())->update(['revoked' => true]);
    }
}
