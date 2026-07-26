<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Models\AuditEvent;
use App\Models\LoginActivity;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * SUPER ADMIN · BUILD 2 · GATE 1 — reads for the platform Security & Activity view.
 *
 * Two feeds:
 *   1. LOGIN ACTIVITY (the security signal) — from {@see LoginActivity}, a PLATFORM table (not tenant-scoped),
 *      so it is read directly (admin-realm-gated by the route). Privacy: 90-day retention, security purpose.
 *   2. ACCOUNT EVENTS (the business audit trail) — from the tenant {@see AuditEvent} ledger; the platform admin
 *      sees a cross-tenant slice. These reads run INSIDE {@see PlatformQuery::read()} (the caller wraps them),
 *      so the cross-tenant access is audited + scope-restored.
 *
 * 🔒 Real events only (#39); honest empty states.
 */
class PlatformActivity
{
    /**
     * Recent auth security signals (login / failed / lockout), newest first.
     *
     * @return Collection<int, LoginActivity>
     */
    public function recentLogins(int $limit = 40)
    {
        return LoginActivity::query()->orderByDesc('occurred_at')->limit($limit)->get();
    }

    /** How many failed/lockout signals in the last 24h — the "someone's probing" summary. */
    public function recentFailureCount(): int
    {
        return LoginActivity::query()
            ->whereIn('event', ['failed', 'lockout'])
            ->where('occurred_at', '>=', now()->subDay())
            ->count();
    }

    /**
     * Recent account/business events across ALL orgs (the audit trail slice). Run inside PlatformQuery::read.
     *
     * @return list<array{action: string, actor_type: string, organization: string, created_at: ?Carbon}>
     */
    public function recentAccountEvents(int $limit = 40): array
    {
        $events = AuditEvent::query()->orderByDesc('created_at')->limit($limit)->get();

        return $this->present($events);
    }

    /**
     * A single customer's recent audit slice. Run inside PlatformQuery::read.
     *
     * @return list<array{action: string, actor_type: string, organization: string, created_at: ?Carbon}>
     */
    public function auditSliceForOrg(string $organizationId, int $limit = 15): array
    {
        $events = AuditEvent::query()
            ->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $this->present($events);
    }

    /**
     * @param  Collection<int, AuditEvent>  $events
     * @return list<array{action: string, actor_type: string, organization: string, created_at: ?Carbon}>
     */
    private function present($events): array
    {
        $orgNames = Organization::query()
            ->whereIn('id', $events->pluck('organization_id')->filter()->unique()->all())
            ->pluck('name', 'id');

        $rows = [];
        foreach ($events as $event) {
            $rows[] = [
                'action' => (string) $event->action,
                'actor_type' => (string) $event->actor_type,
                'organization' => $event->organization_id !== null ? (string) ($orgNames[$event->organization_id] ?? '—') : '—',
                'created_at' => $event->created_at,
            ];
        }

        return $rows;
    }
}
