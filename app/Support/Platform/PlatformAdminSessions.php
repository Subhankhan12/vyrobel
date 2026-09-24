<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Http\Middleware\TrackPlatformAdminSession;
use App\Models\PlatformAdmin;
use App\Models\PlatformAdminSession;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * ADMIN 01 · PHASE 1 — THE one writer of {@see PlatformAdminSession}: start, check, end, force-end.
 *
 * ⚠️⚠️ WHY THIS WORKS WHERE THE FRAMEWORK'S TABLE DOES NOT: production stores sessions as FILES, so nothing can
 * enumerate or delete them from here. Instead every admin session carries a random key in its OWN session data;
 * only its SHA-256 is stored in our table; and {@see TrackPlatformAdminSession} looks the row up on every
 * console request. Ending a row is therefore enforced on the very next request, whatever the session driver.
 *
 * ⚠️ A session that predates this table (signed in before the deploy) has no key. It is ADOPTED on its next
 * request — a fresh key, a fresh row — rather than thrown out, so shipping this cannot sign the founder out.
 */
class PlatformAdminSessions
{
    /** The session-data key holding this session's raw tracking key. */
    public const SESSION_KEY = 'platform_admin.session_key';

    /** A session is only "seen" at most once a minute — one write per minute, not one per request. */
    private const TOUCH_EVERY_SECONDS = 60;

    /** Begin tracking a freshly authenticated session (call AFTER the session has been regenerated). */
    public function start(Request $request, PlatformAdmin $admin): PlatformAdminSession
    {
        $key = Str::random(64);
        $request->session()->put(self::SESSION_KEY, $key);

        return PlatformAdminSession::query()->create([
            'platform_admin_id' => $admin->getKey(),
            'session_key_hash' => hash('sha256', $key),
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'started_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Check the current request's session. Returns NULL when it may continue, or the reason it must end
     * (`forced` / `revoked` / `mismatch`). Adopts an untracked session; touches a live one (throttled).
     */
    public function check(Request $request, PlatformAdmin $admin): ?string
    {
        $key = $request->session()->get(self::SESSION_KEY);

        if (! is_string($key) || $key === '') {
            $this->start($request, $admin);

            return null;
        }

        $row = PlatformAdminSession::query()->where('session_key_hash', hash('sha256', $key))->first();

        if ($row === null) {
            // The key exists but its row does not — adopt rather than refuse (a restored DB, a new table).
            $request->session()->forget(self::SESSION_KEY);
            $this->start($request, $admin);

            return null;
        }

        // ⚠️ A key that belongs to a DIFFERENT admin is never honoured — whatever produced it, it is not this
        //    person's session to continue.
        if ($row->platform_admin_id !== $admin->getKey()) {
            return 'mismatch';
        }

        if ($row->ended_at !== null) {
            return (string) ($row->ended_reason ?? 'forced');
        }

        if ($row->last_seen_at === null || $row->last_seen_at->lt(now()->subSeconds(self::TOUCH_EVERY_SECONDS))) {
            $row->forceFill(['last_seen_at' => now()])->save();
        }

        return null;
    }

    /** End THIS request's session (a normal sign-out). */
    public function endCurrent(Request $request, string $reason = 'signed_out'): void
    {
        $key = $request->session()->get(self::SESSION_KEY);

        if (is_string($key) && $key !== '') {
            PlatformAdminSession::query()
                ->where('session_key_hash', hash('sha256', $key))
                ->whereNull('ended_at')
                ->update(['ended_at' => now(), 'ended_reason' => $reason]);
        }

        $request->session()->forget(self::SESSION_KEY);
    }

    /** End EVERY live session of one admin — force sign-out, or revocation. Returns how many were ended. */
    public function endAllFor(PlatformAdmin $admin, string $reason, ?PlatformAdmin $by = null): int
    {
        return PlatformAdminSession::query()
            ->where('platform_admin_id', $admin->getKey())
            ->whereNull('ended_at')
            ->update(['ended_at' => now(), 'ended_reason' => $reason, 'ended_by' => $by?->getKey()]);
    }

    /** End every live session of an admin EXCEPT the current one — used when they change their own password. */
    public function endOthersFor(PlatformAdmin $admin, Request $request): int
    {
        $current = $this->currentHash($request);

        return PlatformAdminSession::query()
            ->where('platform_admin_id', $admin->getKey())
            ->whereNull('ended_at')
            ->when($current !== null, fn ($q) => $q->where('session_key_hash', '!=', $current))
            ->update(['ended_at' => now(), 'ended_reason' => 'password_changed', 'ended_by' => $admin->getKey()]);
    }

    /**
     * Sessions still open for an admin: not ended, and seen within the session lifetime. ⚠️ "Seen" is updated at
     * most once a minute, so "last seen" is accurate to the minute, not the second.
     *
     * @return Collection<int, PlatformAdminSession>
     */
    public function activeFor(PlatformAdmin $admin): Collection
    {
        return PlatformAdminSession::query()
            ->where('platform_admin_id', $admin->getKey())
            ->whereNull('ended_at')
            ->where('last_seen_at', '>=', now()->subMinutes((int) config('session.lifetime', 120)))
            ->orderByDesc('last_seen_at')
            ->get();
    }

    /** The raw key of the current request's session, for "this is you" in a list. */
    public function currentHash(Request $request): ?string
    {
        $key = $request->session()->get(self::SESSION_KEY);

        return is_string($key) && $key !== '' ? hash('sha256', $key) : null;
    }
}
