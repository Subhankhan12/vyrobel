<?php

declare(strict_types=1);

namespace App\Support\Security;

use App\Http\Middleware\EndSessionsOnPasswordChange;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

/**
 * THE ONE definition of a user's active sessions (#96) — read from the `sessions` table (DB session driver),
 * flagging the CURRENT session and describing each device from its user-agent honestly. Shared by the owner
 * Security page (#66) and the operator Profile/Security surfaces so they can never drift. Self-only by
 * construction (the caller passes their OWN user id).
 */
final class ActiveSessions
{
    /**
     * @return list<array{current: bool, device: string, ip: string, last_active: Carbon}>
     */
    public static function forUser(string $userId): array
    {
        $currentId = session()->getId();

        return DB::table('sessions')
            ->where('user_id', $userId)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($row): array => [
                'current' => $row->id === $currentId,
                'device' => self::describeUserAgent((string) ($row->user_agent ?? '')),
                'ip' => (string) ($row->ip_address ?? '—'),
                'last_active' => Carbon::createFromTimestamp((int) $row->last_activity),
            ])
            ->values()
            ->all();
    }

    /** An honest device label from a user-agent — "Chrome · Mac", "Unknown device" when absent (never invented). */
    /**
     * Terminate a user's sessions — the ONE place a session is killed server-side (auth pass, item 3).
     *
     * A password change rotated the hash and the remember-token, which kills "remember me" COOKIES — but an
     * attacker holding a live server-side session was completely unaffected, so a reset did not actually lock
     * them out. That is the whole point of resetting a compromised password.
     *
     * Deletes the rows from the `sessions` table (database driver), so they cannot be restored. Pass the
     * current session id to KEEP it — the in-app change-password flow must not log you out of the browser
     * you are typing in; a reset passes null, because the user is signed in fresh immediately afterwards.
     *
     * @return int how many sessions were terminated
     */
    public static function terminateFor(string $userId, ?string $keepSessionId = null): int
    {
        $query = DB::table('sessions')->where('user_id', $userId);

        if ($keepSessionId !== null) {
            $query->where('id', '!=', $keepSessionId);
        }

        return $query->delete();
    }

    /**
     * ⚠️⚠️ SESSIONS 01 (L16) — "Sign out all other sessions", the ONE implementation (owner Security and operator
     * Profile both call it; the operator one used to carry its own copy of the query). Two layers, because either
     * alone has failed on this host before:
     *   1. the ROWS — every other `sessions` row of this user is deleted (real on the `database` driver; on the
     *      `file` driver production ran until 2026-09-25 this deleted nothing and said "no other sessions");
     *   2. the HASH — Laravel's `logoutOtherDevices()` re-hashes the SAME password, so every other session's stored
     *      hash stops matching and {@see EndSessionsOnPasswordChange} signs it out on its next
     *      request, whatever the driver. THIS session stores the new hash at the end of the request, so it stays in.
     * Then the remember-token is rotated, so a "remember me" cookie on those devices is dead too.
     *
     * The caller has ALREADY confirmed `$currentPassword` (`current_password` rule) — `logoutOtherDevices` needs the
     * plaintext to re-hash it, and throws on a wrong one.
     *
     * @return int how many session ROWS were deleted (the count the user is told)
     */
    public static function signOutOthers(Request $request, User $user, string $currentPassword): int
    {
        $deleted = self::terminateFor((string) $user->getKey(), $request->session()->getId());

        // ⚠️ Layer 2 exists only on Laravel's SESSION guard. If `web` were ever configured as something else, this
        //    layer would silently not exist — so it refuses loudly instead of pretending.
        $guard = Auth::guard('web');
        if (! $guard instanceof SessionGuard) {
            throw new LogicException('Signing out other sessions needs the `web` guard to be a session guard; it is '.$guard::class.'.');
        }
        $guard->logoutOtherDevices($currentPassword);

        $user->forceFill(['remember_token' => Str::random(60)])->save();

        return $deleted;
    }

    public static function describeUserAgent(string $ua): string
    {
        $browser = match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Browser',
        };
        $os = match (true) {
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Mac OS') => 'Mac',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Linux') => 'Linux',
            default => '',
        };

        return $ua === '' ? 'Unknown device' : trim($browser.($os !== '' ? ' · '.$os : ''));
    }
}
