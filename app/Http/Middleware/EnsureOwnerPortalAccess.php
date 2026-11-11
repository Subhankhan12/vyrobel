<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\Scopes\OwnerScope;
use App\Models\User;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerPortalAccess;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * F-23 — THE POSITIVE "THIS PERSON IS AN OWNER" GATE THE OWNER PORTAL NEVER HAD.
 *
 * ⚠️⚠️ THIS IS NOT A LEAK FIX, AND SAYING SO MATTERS. The owner SCOPE already failed closed on data:
 * {@see SetOwnerScopeForPortal} activates an EMPTY owner set for a user with no links, so
 * {@see OwnerScope} returns nothing. 🔬 That is exactly why an operator who walked into
 * `/owner` saw every figure at €0.00, empty lists, and a 2-line CSV carrying a watermark naming them.
 * NOTHING ESCAPED. What was wrong is that the product RENDERED AN OWNER'S PORTAL FOR SOMEONE IT DOES NOT
 * BELONG TO — greeted them by name and offered them "Balance & withdrawals". That is a correctness and
 * trust defect, and this middleware fixes that and only that.
 *
 * ⚠️ THE ASYMMETRY IS THE ARGUMENT. {@see EnsureBackOfficeAccess} (invariant #26) correctly bounces an
 * OWNER out of `/app` and sends them to their own home. The reverse was simply never built. This is the
 * mirror image of that middleware, deliberately down to its shape:
 *
 *   · an affirmative check (are you an owner?), never a blocklist of who is excluded — the `auth:guest`
 *     pattern, where being on the guard IS the gate;
 *   · a REDIRECT, not a dead end, for someone who has a real home elsewhere;
 *   · `abort(403)` only for an actor with nowhere legitimate to go.
 *
 * ⚠️ ONE DEFINITION (§N.8f). The "is this person owner-linked" question is answered by
 * {@see OwnerContext::actorIsOwnerLinked()} — memoised per request and documented as reusing the SAME two
 * link sources as the scope middleware (`owners.user_id` and an ACTIVE `owner_users` row). A third copy of
 * that resolution on a security boundary is exactly the drift this codebase keeps removing.
 *
 * ⚠️ 403, NOT 404. Phase 14 established that a 404 withholds existence and a 403 confirms it — right for
 * the GUEST realm, where one guest must not learn another's booking exists. Here the resource is not
 * secret: `/owner` is a documented, publicly-known part of the product, and the actor is an AUTHENTICATED
 * colleague in the same organisation. Withholding its existence from them would protect nothing and
 * mislead them further, which is the very defect being fixed. #26 aborts 403 for the same reason.
 *
 * ⚠️⚠️ DEC-o (2026-11-12) — AND A LINK TO AN OWNER WHOSE PORTAL IS SWITCHED OFF IS NOT ENOUGH. {@see OwnerPortalAccess}: the primary owner and
 * their team are refused together with a plain page (a back-office person is sent to /app instead — they keep their login). The answer to
 * "is the actor linked" still comes from {@see OwnerContext::actorIsOwnerLinked()} unchanged — the owner scope depends on it staying true.
 *
 * ⚠️ TWO OWNER ROUTES DELIBERATELY SIT OUTSIDE THIS GATE, and must: `owner.account.email.verify` (a signed
 * link opened from a NEW inbox, by someone who may not be signed in at all) and `owner.team.invite.accept`
 * (reached by someone who is NOT YET an owner — a pending `owner_users` row is not `active`, so gating it
 * would make an invitation impossible to accept).
 */
class EnsureOwnerPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // `auth` runs first, so this is belt-and-braces — but never assume it when failing closed.
        if (! $user instanceof User) {
            abort(403);
        }

        $linked = app(OwnerContext::class)->actorIsOwnerLinked();

        // ⚠️ DEC-o — linked is not enough: at least one owner they belong to must have its portal ON. ({@see OwnerPortalAccess})
        if ($linked && OwnerPortalAccess::hasOpenLink($user)) {
            return $next($request);
        }

        // A back-office operator has a real home. Send them there rather than to a dead end — the exact
        // courtesy #26 extends to an owner who lands on `/app`. (It is also what a person who works for the operator AND is an
        // owner whose portal is off gets: they keep their login and their back office.)
        /** @var list<string> $backOffice */
        $backOffice = config('authorization.back_office_roles', []);

        if ($backOffice !== [] && $user->hasAnyRole($backOffice)) {
            return redirect()->route('app.dashboard');
        }

        if ($linked) {
            return $this->refuseSwitchedOff($request);
        }

        abort(403);
    }

    /**
     * ⚠️ DEC-o — an owner (or one of their team) whose portal an operator switched off: a plain page that says so, what is kept,
     * and who to ask — never a blank 403, and nothing of the portal behind it. A JSON caller gets the same words as a 403.
     */
    private function refuseSwitchedOff(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => __('php/http/middleware/ensure_owner_portal_access.portal_off_json')], 403);
        }

        $organizationId = app(TenantContext::class)->id();
        $organization = $organizationId !== null ? Organization::withTrashed()->find($organizationId) : null;

        return response()->view('errors.layout', [
            'code' => 403,
            'title' => __('php/http/middleware/ensure_owner_portal_access.title'),
            'message' => $organization !== null
                ? __('php/http/middleware/ensure_owner_portal_access.message_named', ['organization' => (string) $organization->name])
                : __('php/http/middleware/ensure_owner_portal_access.message'),
            'actions' => 'errors.partials.owner-portal-off-actions',
        ], 403);
    }
}
