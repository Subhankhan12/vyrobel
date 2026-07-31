<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\IpAllowRule;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * #104b GATE 1 — the operator Security "IP allowlist" enforcement. When the acting org has ≥1 allowlist rule,
 * ONLY a request whose IP matches a rule (exact or CIDR, via Symfony IpUtils) may reach the operator back office
 * (/app). Zero rules ⇒ no restriction (the honest, opt-in "No IP restrictions" state) — the allowlist NEVER
 * blocks unless an admin genuinely configured it.
 *
 * ORDERING: runs on the /app route group AFTER {@see EnsureBackOfficeAccess} (and the web group's
 * SetTenantForWebRequest), so {@see TenantContext} is resolved. FAIL-OPEN with no tenant context (an
 * unauthenticated / no-org request never reaches here anyway; if it somehow does, never a spurious lock — the
 * same posture as OperatorWorkspace). Scoped to /app ONLY — not /owner, not /guest, not /login (so a locked-out
 * admin can still reach login + their owner portal).
 *
 * ⚠️ PRODUCTION DEPLOYMENT NOTE: {@see Request::ip()} returns the direct socket peer unless TrustProxies is
 * configured. With no trusted proxy set (the current, safe default) a spoofed `X-Forwarded-For` is IGNORED — so
 * there is no bypass. BUT behind a real reverse proxy / load balancer, every request would appear to come from
 * the proxy's IP, which would make an allowlist either lock everyone out or match nothing. Before enabling the
 * allowlist in a proxied deployment, configure trusted proxies (bootstrap/app.php → `trustProxies`) so
 * `Request::ip()` resolves the real client address. Until deployment (pending), this is a documented prerequisite.
 */
class EnforceIpAllowlist
{
    public function handle(Request $request, Closure $next): Response
    {
        $ctx = app(TenantContext::class);

        // No tenant context → fail open (never a spurious lock).
        if ($ctx->id() === null) {
            return $next($request);
        }

        /** @var list<string> $rules */
        $rules = IpAllowRule::query()->pluck('ip_address')->map(fn ($ip): string => (string) $ip)->all();

        // Opt-in: an org with no rules has no restriction (the honest empty state).
        if ($rules === []) {
            return $next($request);
        }

        $ip = (string) ($request->ip() ?? '');
        if ($ip !== '' && IpUtils::checkIp($ip, $rules)) {
            return $next($request);
        }

        abort(403, 'Your IP address is not on this workspace’s allowlist.');
    }
}
