<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PlatformAdmin;
use App\Support\Platform\BreakGlass;
use App\Support\Platform\PlatformAuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ⚠️⚠️ ADMIN 01 · PHASE 3 — LAYER 1 OF BREAK-GLASS READ-ONLY: EVERY NON-GET REQUEST FROM A BROWSER HOLDING A
 * BREAK-GLASS SESSION IS REFUSED. Appended to the whole `web` group, so it covers /app, /livewire/update (every
 * component action), /owner — anything — except the admin's own console under /admin, where ending the session
 * lives. ⚠️ It keys on the session merely EXISTING, not on it being valid: a stale or tampered key refuses too.
 * Every refusal is audited. ⚠️ Mutation-proved: remove this and a POST during break-glass reddens a test.
 *
 * For every other browser it does nothing — nobody but an admin who started break-glass ever holds the key.
 */
class RefuseWritesDuringBreakGlass
{
    public function __construct(private readonly PlatformAuditLogger $audit) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || ! BreakGlass::hasStash($request) || $request->is('admin', 'admin/*')) {
            return $next($request);
        }

        $admin = Auth::guard('admin')->user();
        $this->audit->record(
            'platform.break_glass.write_refused',
            ['method' => $request->method(), 'path' => '/'.ltrim($request->path(), '/')],
            actor: $admin instanceof PlatformAdmin ? $admin : null,
        );

        $message = 'This is a read-only Vyrobel support session — nothing can be changed.';

        if ($request->expectsJson() || $request->header('X-Livewire') !== null) {
            return response()->json(['message' => $message], 403);
        }

        return response()->view('errors.layout', [
            'code' => 403,
            'title' => 'Read-only support session',
            'message' => $message.' End the session from the banner to leave it.',
        ], 403);
    }
}
