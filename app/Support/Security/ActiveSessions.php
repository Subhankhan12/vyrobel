<?php

declare(strict_types=1);

namespace App\Support\Security;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
