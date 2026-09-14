<?php

declare(strict_types=1);

namespace App\Support\Events;

use App\Listeners\DispatchNotificationsForDomainEvent;
use App\Models\AuditEvent;
use App\Services\PropertyScopeService;
use App\Services\RoleAssignmentService;
use App\Services\RolePermissionService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;

/**
 * The single reusable writer for the security/compliance audit trail (convention #7).
 * WHO did WHAT, with before/after and request metadata. Write-once.
 *
 * Downstream consumers come later; for now every meaningful state change just records here
 * (alongside a {@see DomainEventRecorder} business event).
 *
 * ⚠️⚠️ THE ONE DOCUMENTED EXCEPTION — THE ACCESS-CONTROL FAMILY IS AUDIT-ONLY, ON PURPOSE.
 * {@see RolePermissionService}, {@see PropertyScopeService} and
 * {@see RoleAssignmentService} each write an `audit_events` row and **no** `domain_events`
 * row. That looks like an omission and someone will eventually "fix" it, so the reason is here:
 *
 *   · `audit_events` is the SECURITY/COMPLIANCE trail — who did what, with before/after. A role change is
 *     exactly that, and the table is never pruned, so the history survives.
 *   · `domain_events` is the BUSINESS outbox, for downstream consumers of business facts (a reservation
 *     confirmed, a statement finalized, a payout paid). Nothing consumes an access change, and inventing a
 *     consumer-less event type would add a row nobody reads.
 *
 * ⚠️ AND THE REASON PREVIOUSLY RECORDED FOR THIS WAS WRONG (corrected 2026-08-31). It said a domain event
 * "would also fire a notification". It would not: {@see DispatchNotificationsForDomainEvent}
 * matches an allow-list and RETURNS EARLY for any type outside it. So if the decision is ever revisited,
 * revisit it on the reason above — not on a coupling that does not exist.
 */
class AuditEventRecorder
{
    public function __construct(
        protected TenantContext $tenant,
    ) {}

    /**
     * @param  array<string,mixed>|null  $before
     * @param  array<string,mixed>|null  $after
     */
    public function record(
        string $action,
        ?Model $subject = null,
        ?array $before = null,
        ?array $after = null,
        ?string $actorType = null,
        ?string $actorId = null,
        ?string $organizationId = null,
    ): AuditEvent {
        $request = request();
        $user = auth()->user();

        $actorType ??= $user !== null ? 'user' : 'system';
        $actorId ??= $user?->getAuthIdentifier() !== null ? (string) $user->getAuthIdentifier() : null;

        return AuditEvent::create([
            'organization_id' => $organizationId ?? $this->tenant->id(),
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject !== null ? (string) $subject->getKey() : null,
            'before' => $before,
            'after' => $after,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
