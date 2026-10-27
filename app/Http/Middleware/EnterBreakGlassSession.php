<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\BreakGlassSession;
use App\Models\Organization;
use App\Models\PlatformAdmin;
use App\Models\User;
use App\Support\Platform\BreakGlass;
use App\Support\Platform\PlatformAuditLogger;
use App\Support\Platform\PlatformWriteGuard;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * ⚠️⚠️ ADMIN 01 · PHASE 3 — BREAK-GLASS INTO /app. First in the /app group. For a browser holding an OPEN session
 * ({@see BreakGlass::current()}): the organisation's chosen back-office user's VIEW is borrowed for this request
 * only (`setUser`, never a login), the tenant is set, the page runs READ-ONLY at the database connection, the page
 * is audited, and the banner is put on it. For everyone else it does nothing at all.
 *
 * ⚠️ Expired, ended, foreign or tampered → the key is cleared and the admin is sent back to the console; the
 * request never reaches /app as anyone.
 */
class EnterBreakGlassSession
{
    public function __construct(
        private readonly BreakGlass $breakGlass,
        private readonly PlatformAuditLogger $audit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! BreakGlass::hasStash($request)) {
            return $next($request);
        }

        if (! BreakGlass::enabled()) {
            $request->session()->forget(BreakGlass::SESSION_KEY);

            return $next($request);
        }

        $session = $this->breakGlass->current($request);
        $organization = $session !== null ? Organization::query()->find($session->target_organization_id) : null;
        $viewing = $session !== null ? User::query()->find($session->viewing_user_id) : null;

        if ($session === null || $organization === null || $viewing === null) {
            if ($session !== null) {
                $this->breakGlass->end($session, 'invalid');
                $request->session()->forget(BreakGlass::SESSION_KEY);
            }

            return redirect()->route('admin.break-glass.index')->with('admin_notice', __('php/http/middleware/enter_break_glass_session.session_ended'));
        }

        // ⚠️ THE BORROWED VIEW — this request only. Nothing is written to the web session: it is not a login.
        Auth::guard('web')->setUser($viewing);
        Auth::shouldUse('web');
        app(TenantContext::class)->set((string) $organization->id);
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
        $request->attributes->set(BreakGlass::ATTRIBUTE, $session);

        // ⚠️⚠️ LAYER 2 — the page runs read-only at the connection. A page that tries to write while it is being
        //    viewed is stopped, so nothing lands in the tenant's history as if their own user had done it.
        $response = PlatformWriteGuard::readOnly(fn () => $next($request));

        // ⚠️ SESSIONS 01 — hand the default guard BACK before the session is saved. With the `database` session driver
        //    the row's `user_id` is whatever the default guard says at save time (`auth.driver` is a singleton, so it is
        //    forgotten too): left on `web`, the ADMIN's browser session would be filed under the VIEWED hotel user, and
        //    that user's Security page would list a session from a device they have never seen.
        Auth::shouldUse('admin');
        app()->forgetInstance('auth.driver');

        // Outside the read-only scope: the platform's own records.
        $admin = Auth::guard('admin')->user() instanceof PlatformAdmin ? Auth::guard('admin')->user() : null;
        $path = '/'.ltrim($request->path(), '/');
        $session->increment('pages_viewed');
        $this->audit->record('platform.break_glass.page', ['path' => $path, 'status' => $response->getStatusCode()], 'organization', (string) $organization->id, actor: $admin);

        if ($request->attributes->get(BreakGlass::WRITE_REFUSED) === true) {
            $this->audit->record('platform.break_glass.write_refused', ['layer' => 'connection', 'method' => $request->method(), 'path' => $path], 'organization', (string) $organization->id, actor: $admin);
        }

        return $this->withBanner($response, $session, $organization, $viewing);
    }

    /** ⚠️ VISIBLE — the banner goes on EVERY html page of the session, whatever layout the page uses. */
    private function withBanner(Response $response, BreakGlassSession $session, Organization $organization, User $viewing): Response
    {
        $type = (string) $response->headers->get('Content-Type', '');
        $content = $response->getContent();

        if (! str_contains($type, 'text/html') || ! is_string($content) || ! preg_match('/<body\b[^>]*>/i', $content, $m, PREG_OFFSET_CAPTURE)) {
            return $response;
        }

        $banner = view('admin.break-glass.banner', [
            'session' => $session,
            'organization' => $organization,
            'viewing' => $viewing,
        ])->render();

        $at = $m[0][1] + strlen($m[0][0]);
        $response->setContent(substr($content, 0, $at).$banner.substr($content, $at));

        return $response;
    }
}
