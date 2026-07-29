<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Owner;
use App\Models\OwnerUser;
use App\Support\Events\AuditEventRecorder;
use App\Support\Tenancy\TenantContext;
use Laravel\Passport\Client;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Token;

/**
 * Audits an OAuth token issuance (Wave D · Passport · Phase 2) — the moment a third-party app obtains access on
 * an owner's behalf (#20). The token exchange (POST /oauth/token) is SESSION-LESS, so {@see AuditEventRecorder}'s
 * auto-fill (actor from auth(), org from TenantContext) is unavailable here — the listener resolves the actor
 * (the token's user) + the org (the user's owner→org) itself and passes them EXPLICITLY. Records WHICH client +
 * scopes (never a secret/token value). A client-credentials token (no user) is skipped — we do not issue those.
 */
class AuditOAuthTokenGranted
{
    public function __construct(
        private AuditEventRecorder $audit,
        private TenantContext $tenant,
    ) {}

    public function handle(AccessTokenCreated $event): void
    {
        if ($event->userId === null) {
            return; // no owner-bound user → nothing owner-scoped to audit
        }

        // All look-ups are platform-level here (no request tenant) — resolve under super-admin.
        [$appName, $scopes, $organizationId] = $this->tenant->runAsSuperAdmin(function () use ($event): array {
            $client = Client::query()->find($event->clientId);
            $token = Token::query()->find($event->tokenId);

            $ownerOrg = Owner::query()->where('user_id', $event->userId)->value('organization_id')
                ?? OwnerUser::query()
                    ->where('owner_users.user_id', $event->userId)->where('owner_users.status', 'active')
                    ->join('owners', 'owners.id', '=', 'owner_users.owner_id')->value('owners.organization_id');

            return [
                $client->name ?? 'Unknown app',
                is_array($token?->scopes) ? array_values($token->scopes) : [],
                $ownerOrg !== null ? (string) $ownerOrg : null,
            ];
        });

        $this->audit->record(
            action: 'owner.connected_app_token_issued',
            subject: null,
            before: null,
            after: ['client_id' => $event->clientId, 'app' => $appName, 'scopes' => $scopes],
            actorType: 'user',
            actorId: $event->userId,
            organizationId: $organizationId,
        );
    }
}
