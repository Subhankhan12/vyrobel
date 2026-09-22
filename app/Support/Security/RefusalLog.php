<?php

declare(strict_types=1);

namespace App\Support\Security;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Scopes\OrganizationScope;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
 *   · ABORT  — a deliberate `abort(403|404)` inside a MATCHED route. Logged (added 2026-09-22).
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
 * class, the method. Never a name, an email, or a request body.
 *
 * ⚠️⚠️ THE IP IS NEVER STORED — but a rotating HASH of it is, so the intrusion half can still see "the same
 * source, forty times in a minute" without ever holding an address. Founder ruling 2026-09-22; the full
 * design, and precisely what the signal CANNOT see, is on {@see ipCorrelator()}.
 */
final class RefusalLog
{
    public const CHANNEL_MESSAGE = 'security.refusal';

    /** The refusing layer. */
    public const LAYER_SCOPE = 'tenant-scope';

    public const LAYER_POLICY = 'policy';

    /**
     * ⚠️ A DELIBERATE `abort()` INSIDE A CONTROLLER OR MIDDLEWARE — a third refusing layer, added 2026-09-22.
     * 🔬 25 `abort(404)` and 19 `abort(403)` sites throw `HttpException` with NO `$previous`, so the first
     * version of this log could not see them at all — which is why the owner-portal statement, payout and
     * unit refusals were unattributable, on the one surface where an owner sees money.
     * ⚠️⚠️ THE DISCRIMINATOR IS `$request->route()`: a deliberate abort happens INSIDE a matched route, while
     * an unrouted 404 never matched one. So a genuinely missing path still stays silent, and is still
     * distinguished by its absence from this log.
     */
    public const LAYER_ABORT = 'abort';

    /**
     * ⚠️⚠️ THE SALT ROTATES DAILY, AND THAT IS THE WHOLE PRIVACY DESIGN. See {@see ipCorrelator()}.
     */
    private const IP_SALT_CACHE_PREFIX = 'security.refusal.ip-salt.';

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

        // ⚠️ `class_exists()` alone is the right guard: the exception types this `class-string<Model>`, so a
        //    `is_string()`/`=== ''` pair is redundant to PHPStan — and an empty string fails `class_exists`
        //    anyway, so the runtime behaviour is identical for an exception built without `setModel()`.
        if (! class_exists($class)) {
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

    /**
     * A deliberate `abort(403|404)` from inside a matched route. Logged because it IS a refusal decision;
     * an unrouted 404 has no matched route and never reaches here.
     */
    public static function deliberateAbort(int $status, Request $request): void
    {
        if ($request->route() === null) {
            return; // no matched route → this is an absence, not a refusal. Silent, by design.
        }

        self::write(self::LAYER_ABORT, $request, ['status' => $status]);
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
            // ⚠️ NOT the address — see ipCorrelator(). Correlation without identification.
            'ip_correlator' => self::ipCorrelator($request),
        ];

        Log::warning(self::CHANNEL_MESSAGE, $context + array_filter($extra, static fn ($v) => $v !== null));
    }

    /**
     * ⚠️⚠️ A CORRELATOR, NOT AN ADDRESS — founder ruling 2026-09-22.
     *
     * ⚠️ THE PROBLEM: without something per-source, the intrusion half of this log cannot see "the same
     * prober, forty times in a minute" — and a logged-out prober has no user id to pin. ⚠️ THE COST of the
     * obvious answer: an IP is personal data under GDPR, and this product will sign an Article 28 agreement.
     *
     * ⚠️ THE RULING: hash it with a **rotating salt**. HMAC-SHA256 truncated to 16 hex characters, keyed by a
     * random salt that lives in the CACHE under a DATE-KEYED name, so it rotates **daily** with no scheduler
     * and no migration — the old key simply stops being read and ages out of the store.
     *
     * ⚠️⚠️ WHAT THE SIGNAL CAN SEE: a burst of refusals sharing one correlator **inside a single day**.
     * ⚠️⚠️ WHAT IT CANNOT: identify anybody · reverse to an address · or link the same source **across a
     * rotation** — yesterday's correlator and today's are unrelated by construction, so a slow prober
     * spread over days is invisible to it. ⚠️ Both halves are written down deliberately: a security control
     * whose limits are unrecorded gets over-trusted.
     */
    private static function ipCorrelator(Request $request): ?string
    {
        $ip = (string) $request->ip();

        if ($ip === '') {
            return null;
        }

        try {
            $salt = Cache::remember(
                self::IP_SALT_CACHE_PREFIX.CarbonImmutable::now()->toDateString(),
                CarbonImmutable::now()->addDays(2),
                static fn (): string => bin2hex(random_bytes(32)),
            );
        } catch (Throwable) {
            return null; // ⚠️ no salt, no correlator — never fall back to the raw address
        }

        return substr(hash_hmac('sha256', $ip, (string) $salt), 0, 16);
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
