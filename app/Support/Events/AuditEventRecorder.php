<?php

declare(strict_types=1);

namespace App\Support\Events;

use App\Models\AuditEvent;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;

/**
 * The single reusable writer for the security/compliance audit trail (convention #7).
 * WHO did WHAT, with before/after and request metadata. Write-once.
 *
 * Downstream consumers come later; for now every meaningful state change just records here
 * (alongside a {@see DomainEventRecorder} business event).
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
