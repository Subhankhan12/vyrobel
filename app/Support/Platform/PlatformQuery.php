<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Models\Scopes\OrganizationScope;
use App\Models\Scopes\OwnerScope;
use App\Support\Owner\OwnerContext;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * THE ONE DOOR — the only place in the product where tenant isolation is deliberately lifted.
 *
 * The platform console sits ABOVE the tenancy and must read across every organisation. It does so ONLY here:
 *
 *   1. ⚠️ ADMIN-GUARD-GATED — both methods refuse (throw) unless a platform admin is authenticated on the
 *      `admin` guard, so the lift can never be triggered from a tenant realm.
 *   2. ⚠️⚠️ BOTH REFUSAL SCOPES ARE LIFTED, NOT ONE. {@see OrganizationScope} via the existing, tested
 *      `TenantContext::runAsSuperAdmin()`, AND {@see OwnerScope} via `OwnerContext::liftForPlatformConsole()`.
 *      Before Phase 1 only the first was lifted, so an admin also signed into an owner account in the same
 *      browser had every owner-scoped read (Unit, statements, payouts) silently narrowed to that one owner.
 *      A test signed in as BOTH proves the lift (PlatformDoorTest).
 *   3. ⚠️⚠️ `read()` IS READ-ONLY, ENFORCED AT THE CONNECTION ({@see PlatformWriteGuard}) — any write statement
 *      inside it throws before executing. It used to be read-only by name only.
 *   4. EVERY ACCESS IS AUDITED to the append-only platform ledger — and every WRITE must carry a written
 *      reason, or `mutate()` refuses to run.
 *
 * Both lifts restore in a `finally`, so the next request (or the rest of this one) is scoped exactly as before.
 * ⚠️ `withoutGlobalScope(s)` appears nowhere else in the admin realm — PlatformDoorGuardTest fails if it does.
 */
class PlatformQuery
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly OwnerContext $owners,
        private readonly PlatformAuditLogger $audit,
    ) {}

    /**
     * Run a READ across all organisations and all owners. Any write statement inside the closure is refused.
     *
     * @template TReturn
     *
     * @param  Closure():TReturn  $callback
     * @param  string  $action  the audit label for this read (e.g. "platform.customers.list")
     * @param  array<string, mixed>  $meta
     * @return TReturn
     */
    public function read(string $action, Closure $callback, array $meta = []): mixed
    {
        $this->assertAdmin('read');

        // ⚠️ The audit row is written BEFORE the read-only section opens — it is the one write a read makes.
        $this->audit->record($action, $meta);

        return PlatformWriteGuard::readOnly(fn () => $this->lifted($callback));
    }

    /**
     * Run an audited cross-tenant WRITE — a deliberate admin action, never a side effect of a read.
     *
     * ⚠️⚠️ A WRITTEN REASON IS REQUIRED. It is recorded on the audit row with the actor, IP and user agent. An
     * empty reason throws before anything is written, so no code path can perform an unexplained admin write.
     *
     * @template TReturn
     *
     * @param  Closure():TReturn  $callback
     * @param  array<string, mixed>  $meta
     * @return TReturn
     */
    public function mutate(string $action, string $reason, Closure $callback, array $meta = [], ?string $targetType = null, ?string $targetId = null): mixed
    {
        $this->assertAdmin('mutate');

        if (trim($reason) === '') {
            throw new InvalidArgumentException("PlatformQuery::mutate('{$action}') refused: every admin write needs a written reason.");
        }

        $this->audit->record($action, $meta, $targetType, $targetId, reason: $reason);

        // ⚠️ ADMIN 01 · PHASE 2 — the ledger row above is written BEFORE the action runs, and it can never be
        //    edited. So an action that throws gets a second row saying so: otherwise the ledger claims a
        //    suspension, a plan change or an archive that never happened.
        try {
            return $this->lifted($callback);
        } catch (Throwable $e) {
            $this->audit->record($action.'.failed', ['exception' => $e::class], $targetType, $targetId, reason: $reason);

            throw $e;
        }
    }

    /**
     * @template TReturn
     *
     * @param  Closure():TReturn  $callback
     * @return TReturn
     */
    private function lifted(Closure $callback): mixed
    {
        return $this->tenant->runAsSuperAdmin(
            fn () => $this->owners->liftForPlatformConsole($callback),
        );
    }

    private function assertAdmin(string $method): void
    {
        if (! Auth::guard('admin')->check()) {
            throw new RuntimeException("PlatformQuery::{$method}() is available only to an authenticated platform admin.");
        }
    }
}
