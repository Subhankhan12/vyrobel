<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * SUPER ADMIN · GATE 0 — THE ONE, SAFE cross-tenant read path for the /admin realm.
 *
 * The platform panel sits ABOVE the tenancy and must read across every organization. It does so ONLY through
 * this wrapper, which:
 *   1. is ADMIN-GUARD-GATED — it refuses (throws) unless a platform admin is authenticated on the `admin`
 *      guard, so the deliberate scope bypass can never be triggered from a tenant realm;
 *   2. uses the EXISTING, tested {@see TenantContext::runAsSuperAdmin()} closure — the SAME audited, temporary,
 *      finally-restored bypass the app already uses (provisioning, webhooks). We invent NO new scope bypass;
 *   3. AUDITS the access — every cross-tenant read is recorded to the platform action ledger (who + IP + UA).
 *
 * THE NON-LEAK GUARANTEE is inherited from `runAsSuperAdmin`: it restores the previous tenant context in a
 * `finally`, so a subsequent request (a tenant request) is scoped exactly as before — the suspension is
 * temporary and local to this closure. Gate 0's isolation tests prove it end-to-end.
 */
class PlatformQuery
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly PlatformAuditLogger $audit,
    ) {}

    /**
     * Run a read across ALL organizations (the tenant global scope suspended for the closure only).
     *
     * @template TReturn
     *
     * @param  Closure():TReturn  $callback
     * @param  string  $action  the audit action label for this read (e.g. "platform.count_organizations")
     * @param  array<string, mixed>  $meta
     * @return TReturn
     */
    public function read(string $action, Closure $callback, array $meta = []): mixed
    {
        // 🔒 The bypass is triggerable ONLY from the authenticated admin realm. Fail closed everywhere else, so a
        // tenant request can never reach the cross-tenant read path even if it somehow called this service.
        if (! Auth::guard('admin')->check()) {
            throw new RuntimeException('PlatformQuery::read() is available only to an authenticated platform admin.');
        }

        $this->audit->record($action, $meta);

        // The EXISTING runAsSuperAdmin closure: suspends the org scope, runs, and RESTORES context in a finally.
        return $this->tenant->runAsSuperAdmin($callback);
    }

    /**
     * Run an audited cross-tenant WRITE (e.g. suspend a customer, assign a plan). Semantically distinct from
     * {@see read()} but the SAME security envelope: admin-guard-gated, wrapped in the existing
     * `runAsSuperAdmin` closure (scope suspended + restored in a `finally`), and audited. Use this for every
     * platform mutation that touches a tenant-scoped row so the bypass is never opened outside the admin realm.
     *
     * @template TReturn
     *
     * @param  Closure():TReturn  $callback
     * @param  array<string, mixed>  $meta
     * @return TReturn
     */
    public function mutate(string $action, Closure $callback, array $meta = [], ?string $targetType = null, ?string $targetId = null): mixed
    {
        if (! Auth::guard('admin')->check()) {
            throw new RuntimeException('PlatformQuery::mutate() is available only to an authenticated platform admin.');
        }

        $this->audit->record($action, $meta, $targetType, $targetId);

        return $this->tenant->runAsSuperAdmin($callback);
    }
}
