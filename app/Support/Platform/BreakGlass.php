<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Exceptions\PlatformWriteRefused;
use App\Http\Middleware\EnterBreakGlassSession;
use App\Http\Middleware\RefuseWritesDuringBreakGlass;
use App\Models\BreakGlassSession;
use App\Models\Organization;
use App\Models\PlatformAdmin;
use App\Models\User;
use App\Notifications\Operator\SupportAccessNotification;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * ⚠️⚠️ ADMIN 01 · PHASE 3 — BREAK-GLASS: THE ONE AUDITED EXCEPTION TO "AN ADMIN CANNOT ENTER /app".
 *
 * REQUIRES a written reason, a fresh 2FA code (step-up) and a chosen organisation. GRANTS a read-only look at that
 * organisation's back office for {@see minutes()} minutes, then ends by itself. VISIBLE (a banner on every page),
 * AUDITED (start, every page, end), and TOLD (the organisation's back-office users get an in-app notice afterwards
 * — who, when, why; in-app because mail is dark).
 *
 * ⚠️⚠️ HOW IT ENTERS /app WITHOUT BECOMING A TENANT USER. The admin stays signed in as themselves. For each /app
 * request, {@see EnterBreakGlassSession} borrows the VIEW of one real back-office user of that organisation
 * (`setUser` — for this request only; nothing is written to the web session, so it is never a login) and sets the
 * tenant. The borrowed user must be ACTIVE and VERIFIED: the `verified` gate is not bypassed, it is satisfied.
 *
 * ⚠️⚠️ READ-ONLY, TWICE, EACH LAYER TESTED ALONE:
 *   1. {@see RefuseWritesDuringBreakGlass} refuses every non-GET request from a browser holding a break-glass
 *      session — /app, Livewire updates, anything outside /admin (the brief's rule, mutation-proved).
 *   2. {@see EnterBreakGlassSession} runs each GET inside {@see PlatformWriteGuard::readOnly()}, so a page that
 *      tries to WRITE while being viewed is stopped at the connection — which also means nothing is ever recorded
 *      in the tenant's own history as if their user had done it.
 *
 * ⚠️ SHIPPED DORMANT (`config/platform.php`): while off, every entry point is a 404 and a leftover key is cleared.
 */
final class BreakGlass
{
    /** The browser-session key: [id, key]. The DB holds only the key's SHA-256. */
    public const SESSION_KEY = 'platform.break_glass';

    /** The request attribute an open session is exposed on, for the banner and the gates. */
    public const ATTRIBUTE = 'break_glass';

    /** Set on the request when the connection refused a write during a break-glass page — audited after it. */
    public const WRITE_REFUSED = 'break_glass_write_refused';

    public function __construct(
        private readonly PlatformAuditLogger $audit,
        private readonly TenantContext $tenant,
    ) {}

    public static function enabled(): bool
    {
        return (bool) config('platform.break_glass.enabled', false);
    }

    public static function minutes(): int
    {
        return max(1, (int) config('platform.break_glass.minutes', 30));
    }

    /** Is this exception — or anything it wraps — the read-only connection guard refusing a write? */
    public static function isWriteRefusal(Throwable $e): bool
    {
        for ($cur = $e; $cur !== null; $cur = $cur->getPrevious()) {
            if ($cur instanceof PlatformWriteRefused) {
                return true;
            }
        }

        return false;
    }

    public static function hasStash(Request $request): bool
    {
        return $request->hasSession() && $request->session()->has(self::SESSION_KEY);
    }

    /**
     * The organisation's back-office user whose VIEW is borrowed: active, verified, holding a back-office role —
     * the highest role first (config order), then the longest-standing member. Null when there is none.
     */
    public function viewingUserFor(Organization $organization): ?User
    {
        foreach ((array) config('authorization.back_office_roles', []) as $role) {
            $id = DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->join('users', 'users.id', '=', 'model_has_roles.model_id')
                ->join('organization_user', function ($join) use ($organization): void {
                    $join->on('organization_user.user_id', '=', 'users.id')
                        ->where('organization_user.organization_id', '=', $organization->id);
                })
                ->where('model_has_roles.organization_id', $organization->id)
                ->where('model_has_roles.model_type', (new User)->getMorphClass())
                ->where('roles.name', $role)
                ->where('organization_user.status', 'active')
                ->where('users.status', User::SIGN_IN_STATUS)
                ->whereNotNull('users.email_verified_at')
                ->orderBy('organization_user.created_at')
                ->value('users.id');

            if ($id !== null) {
                return User::query()->find($id);
            }
        }

        return null;
    }

    /** Open a session and bind it to THIS browser session. The caller has checked the reason and the 2FA code. */
    public function start(PlatformAdmin $admin, Organization $organization, User $viewing, string $reason, Request $request): BreakGlassSession
    {
        $key = Str::random(64);

        $session = BreakGlassSession::create([
            'platform_admin_id' => $admin->id,
            'target_organization_id' => $organization->id,
            'viewing_user_id' => $viewing->id,
            'reason' => $reason,
            'session_key_hash' => hash('sha256', $key),
            'started_at' => now(),
            'expires_at' => now()->addMinutes(self::minutes()),
        ]);

        $request->session()->put(self::SESSION_KEY, ['id' => $session->id, 'key' => $key]);

        return $session;
    }

    /**
     * The OPEN session bound to this browser session and this admin — or null. ⚠️ An expired session is ENDED here
     * (and the organisation told) the moment anyone looks for it: expiry never depends on the scheduler running.
     */
    public function current(Request $request): ?BreakGlassSession
    {
        $stash = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;
        if (! is_array($stash) || ! is_string($stash['id'] ?? null) || ! is_string($stash['key'] ?? null)) {
            return null;
        }

        $admin = Auth::guard('admin')->user();
        $session = BreakGlassSession::query()->find($stash['id']);

        $bound = $session !== null
            && $admin instanceof PlatformAdmin
            && $session->platform_admin_id === $admin->id
            && hash_equals($session->session_key_hash, hash('sha256', $stash['key']));

        if (! $bound || $session->ended_at !== null) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        if (! $session->expires_at->isFuture()) {
            $this->end($session, 'expired');
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        if (! $admin->may(PlatformAdminRole::SUPPORT_USE)) {
            $this->end($session, 'revoked');
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        return $session;
    }

    /**
     * End a session — manually, at expiry, or because the admin lost the right to it — audit it, and tell the
     * organisation. Idempotent: an ended session is never ended (or announced) twice.
     */
    public function end(BreakGlassSession $session, string $reason): void
    {
        if ($session->ended_at !== null) {
            return;
        }

        // An expired session ended AT its expiry, not whenever someone next noticed.
        $endedAt = $reason === 'expired' ? $session->expires_at : now();
        $session->forceFill(['ended_at' => $endedAt, 'ended_reason' => $reason])->save();

        $this->audit->record(
            'platform.break_glass.end',
            ['ended_reason' => $reason, 'pages_viewed' => $session->pages_viewed],
            'organization',
            $session->target_organization_id,
            actor: PlatformAdmin::query()->find($session->platform_admin_id),
        );

        $this->notifyTenant($session);
    }

    /** End every open session whose time is up — the scheduled sweep, for a session nobody touched again. */
    public function expireOverdue(): int
    {
        $overdue = BreakGlassSession::query()->whereNull('ended_at')->where('expires_at', '<=', now())->get();

        foreach ($overdue as $session) {
            $this->end($session, 'expired');
        }

        return $overdue->count();
    }

    /**
     * ⚠️ TOLD — every active back-office user of the organisation gets an in-app notice: who, when, why. It is an
     * always-on notification (it cannot be switched off), and it names the Vyrobel admin, never hides behind "support".
     */
    private function notifyTenant(BreakGlassSession $session): void
    {
        if ($session->tenant_notified_at !== null) {
            return;
        }

        // ⚠️ I18N-01 — the fallback (an admin since removed) is worded in each RECIPIENT's language, at the moment they are told.
        $name = PlatformAdmin::query()->whereKey($session->platform_admin_id)->value('name');
        $roles = (array) config('authorization.back_office_roles', []);

        $this->tenant->forOrganization($session->target_organization_id, function () use ($session, $name, $roles): void {
            $ids = DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->join('organization_user', function ($join) use ($session): void {
                    $join->on('organization_user.user_id', '=', 'model_has_roles.model_id')
                        ->where('organization_user.organization_id', '=', $session->target_organization_id);
                })
                ->where('model_has_roles.organization_id', $session->target_organization_id)
                ->where('model_has_roles.model_type', (new User)->getMorphClass())
                ->whereIn('roles.name', $roles)
                ->where('organization_user.status', 'active')
                ->distinct()
                ->pluck('model_has_roles.model_id');

            foreach (User::query()->whereIn('id', $ids)->get() as $user) {
                $adminName = $name !== null ? (string) $name : __('php/support/platform/break_glass.unnamed_admin', [], $user->preferredLocale());
                $user->notify(new SupportAccessNotification($adminName, $session));
            }
        });

        $session->forceFill(['tenant_notified_at' => now()])->save();
    }
}
