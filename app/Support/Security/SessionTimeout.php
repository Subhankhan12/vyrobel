<?php

declare(strict_types=1);

namespace App\Support\Security;

use App\Http\Middleware\EnforceSessionTimeout;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * SESSION TIMEOUT — a person's own "sign me out after this long without using Vyrobel" (2026-10-24, the founder's design).
 *
 * Off (null) is the default and means what it always meant: the session ends after `session.lifetime` minutes without a
 * request (2 hours today), because that is how sessions are stored. A shorter choice is enforced by
 * {@see EnforceSessionTimeout} on every staff and owner request.
 *
 * ⚠️ WHAT COUNTS AS ACTIVITY. An open back-office page asks the server things by itself — the bell's pulse, the Inbox's stamp,
 * team chat's poll — every few seconds. Those are not the person, so they never reset the clock ({@see isBackground()});
 * otherwise a tab left open would keep a "15 minutes" session alive for ever, and the setting would be a promise it does not
 * keep. (The same is true of the 2-hour default, which the store refreshes on every request — said on the page.)
 */
final class SessionTimeout
{
    /** The choices, in minutes. */
    public const OPTIONS = [15, 30, 60];

    /** The session key holding the time of the person's last own request. */
    public const KEY = 'vy.security.last_seen';

    /** Requests a page makes by itself, which never count as the person being active. */
    private const BACKGROUND_ROUTES = ['app.pulse', 'app.inbox.live', 'app.inbox.team.poll'];

    public static function minutesFor(User $user): ?int
    {
        $minutes = $user->session_timeout_minutes;

        return is_int($minutes) && in_array($minutes, self::OPTIONS, true) ? $minutes : null;
    }

    public static function isBackground(Request $request): bool
    {
        return $request->routeIs(...self::BACKGROUND_ROUTES)
            // the Inbox re-reading itself (`live=1`) reads nothing and is not the person
            || ($request->routeIs('app.inbox') && $request->query('live') === '1');
    }

    /** "2 hours" — the store's own lifetime, said in words. */
    public static function defaultLabel(): string
    {
        $minutes = (int) config('session.lifetime', 120);

        return $minutes % 60 === 0 ? ($minutes / 60).' '.($minutes === 60 ? 'hour' : 'hours') : $minutes.' minutes';
    }

    public static function label(?int $minutes): string
    {
        return match ($minutes) {
            null => 'Off — '.self::defaultLabel().' (the default)',
            60 => '1 hour',
            default => $minutes.' minutes',
        };
    }
}
