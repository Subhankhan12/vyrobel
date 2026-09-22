<?php

declare(strict_types=1);

namespace App\Support\Security;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Scopes\OrganizationScope;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ⚠️⚠️ THE REFUSAL LOG — attribution in a channel the public cannot see.
 *
 * ⚠️⚠️ THE PROBLEM IT SOLVES, AND WHY THE OBVIOUS FIX IS THE WRONG ONE. 🔬 Measured 2026-09-21 (TEST 01B):
 * a cross-tenant id, a garbage ULID and a route that does not exist all answer **404 with the same 5,653
 * bytes**. So a working tenant scope and an absent route are indistinguishable from outside — which is
 * exactly how `/app` stayed broken for months behind a 404 that looked like hardening working.
 *
 * ⚠️⚠️ THE OBVIOUS REPAIR IS TO MAKE "NOT YOURS" LOOK DIFFERENT FROM "NOT FOUND". **DO NOT.** That identical
 * response is deliberate security: a distinguishable one confirms THE RESOURCE EXISTS, turning an id guess
 * into an enumeration oracle. The property wanted is not a different response — it is **attributability in
 * a channel the public cannot see**. The page stays byte-identical; the RECORD distinguishes.
 *
 * ⚠️⚠️ AND IT IS NOT ONLY A VERIFIABILITY TOOL. A log of scope refusals is an **intrusion signal**: repeated
 * refusals from one session is somebody walking ids, and today nothing anywhere would notice. That is the
 * stronger half of the argument for building it.
 *
 * ⚠️⚠️ IT DOES NOT LOG ALL 404s — THAT WOULD BE NOISE, AND NOISE GETS MUTED. An entry is written ONLY when a
 * refusing layer actually refused something that exists:
 *   · SCOPE  — the row EXISTS, and the tenant scope hid it. Logged.
 *   · POLICY — a permission check said no. Logged.
 *   · ABSENT — no such row, or no such route. **SILENT, deliberately.**
 * ⚠️ So a genuinely missing route stays out of the log, and is distinguished **by its absence from it**.
 * ⚠️ That absence is the signal — the inverse of the witness method, which is fitting.
 *
 * ⚠️⚠️ IT LOGS AT `warning`, NOT `info` OR `debug`, AND THAT IS LOAD-BEARING. 🔬 Production runs
 * `LOG_LEVEL=warning`; measured 2026-09-21, `Log::debug` produced **0 hits** and `Log::warning` **1**. A
 * record written below the threshold is a record nobody has. This project has already been bitten once by a
 * level filter silently swallowing the thing that was meant to be recorded — every outbound email.
 *
 * ⚠️ NO PII. Identifiers only — ULIDs for the actor, the tenant and the requested row; the route, the model
 * class, the method. Never a name, an email, or a request body. ⚠️ **The IP is deliberately NOT recorded**:
 * it is personal data, and the brief that commissioned this named the fields without it. ⚠️ That weakens the
 * intrusion signal (a logged-out prober has no user id to pin) — a trade worth restating to the founder
 * rather than quietly reversing here.
 */
final class RefusalLog
{
    public const CHANNEL_MESSAGE = 'security.refusal';

    /** The refusing layer, and the only three values this log ever carries. */
    public const LAYER_SCOPE = 'tenant-scope';

    public const LAYER_POLICY = 'policy';

    /**
     * A route-model binding (or an explicit findOrFail) came up empty.
     *
     * ⚠️ THE DISCRIMINATION IS THE WHOLE POINT, and it costs exactly one extra query on a path that is
     * already an error: does the row exist when the ORGANISATION scope alone is lifted? If yes, a tenant
     * boundary refused a real row — that is the event worth recording. If no, the id is simply not a row
     * and nothing is written.
     *
     * ⚠️ It strips ONLY {@see OrganizationScope}, never `withoutGlobalScopes()` — that would also strip the
     * soft-delete scope, and this project has a recorded trap for exactly that. A soft-deleted row of MY OWN
     * is "gone", not "someone else's", and must not be reported as a tenant refusal.
     */
    public static function modelNotFound(ModelNotFoundException $e, Request $request): void
    {
        $class = $e->getModel();

        if (! is_string($class) || $class === '' || ! class_exists($class)) {
            return;
        }

        // Only tenant-scoped models can produce a tenant refusal. Anything else is an absence.
        if (! in_array(BelongsToOrganization::class, class_uses_recursive($class), true)) {
            return;
        }

        $ids = array_values(array_filter(array_map('strval', (array) $e->getIds())));

        if ($ids === []) {
            return;
        }

        try {
            /** @var Model $probe */
            $probe = new $class;
            $existsElsewhere = $probe->newQuery()
                ->withoutGlobalScope(OrganizationScope::class)
                ->whereKey($ids)
                ->exists();
        } catch (Throwable) {
            // ⚠️ The instrument must never become the outage. If the probe cannot run, write nothing —
            //    a missing entry reads as "absent", which is the safe direction for a log nobody is paged by.
            return;
        }

        if (! $existsElsewhere) {
            return; // genuinely absent — silent, by design
        }

        self::write(self::LAYER_SCOPE, $request, [
            'model' => $class,
            'requested_ids' => $ids,
        ]);
    }

    /** A permission / policy check refused. Always attributable — no existence probe needed. */
    public static function authorizationDenied(Throwable $e, Request $request): void
    {
        self::write(self::LAYER_POLICY, $request, [
            'reason' => $e->getMessage() !== '' ? $e->getMessage() : null,
        ]);
    }

    /**
     * @param  array<string,mixed>  $extra
     */
    private static function write(string $layer, Request $request, array $extra = []): void
    {
        $user = Auth::user();

        $context = [
            'layer' => $layer,
            'method' => $request->getMethod(),
            'path' => '/'.ltrim($request->path(), '/'),
            'route' => $request->route()?->getName(),
            // ⚠️ IDENTIFIERS ONLY — never a name, an email, or a body. See the class docblock.
            'actor_id' => $user?->getAuthIdentifier() !== null ? (string) $user->getAuthIdentifier() : null,
            'actor_guard' => self::activeGuard(),
            'tenant_id' => self::tenantId(),
        ];

        Log::warning(self::CHANNEL_MESSAGE, $context + array_filter($extra, static fn ($v) => $v !== null));
    }

    private static function activeGuard(): ?string
    {
        foreach (['web', 'owner', 'guest', 'admin', 'api'] as $guard) {
            try {
                if (Auth::guard($guard)->check()) {
                    return $guard;
                }
            } catch (Throwable) {
                continue; // a guard that is not configured is not an error here
            }
        }

        return null;
    }

    private static function tenantId(): ?string
    {
        try {
            $context = app(TenantContext::class);

            return $context->hasContext() ? (string) $context->id() : null;
        } catch (Throwable) {
            return null;
        }
    }
}
