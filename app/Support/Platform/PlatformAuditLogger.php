<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Models\PlatformAdmin;
use App\Models\PlatformAdminAction;
use App\Support\Events\AuditEventRecorder;
use Illuminate\Support\Facades\Auth;

/**
 * SUPER ADMIN · GATE 0 — writes the immutable {@see PlatformAdminAction} ledger: the record of what the
 * platform operator did (logins, cross-tenant reads, and later every admin mutation). Captures the acting
 * admin + IP + user-agent from the current request, so the highest-privilege surface is fully accountable.
 *
 * Deliberately isolated from the tenant {@see AuditEventRecorder} — a platform action is a
 * platform event, never a tenant one; keeping the two ledgers apart means this addition disturbs nothing in
 * the tenant audit trail.
 */
class PlatformAuditLogger
{
    /**
     * Record a platform-admin action. The actor defaults to the currently-authenticated platform admin (the
     * `admin` guard); pass one explicitly for a non-request context (e.g. a console command).
     *
     * @param  array<string, mixed>  $meta  small, non-PII context (counts, route, target descriptor)
     */
    public function record(string $action, array $meta = [], ?string $targetType = null, ?string $targetId = null, ?PlatformAdmin $actor = null): PlatformAdminAction
    {
        $admin = $actor ?? $this->currentAdmin();
        $request = request();

        return PlatformAdminAction::create([
            'platform_admin_id' => $admin?->getKey(),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'meta' => $meta === [] ? null : $meta,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }

    private function currentAdmin(): ?PlatformAdmin
    {
        $admin = Auth::guard('admin')->user();

        return $admin instanceof PlatformAdmin ? $admin : null;
    }
}
