<?php

declare(strict_types=1);

namespace App\Support\Security;

use App\Models\LoginActivity;
use App\Models\User;
use App\Support\Platform\DeviceLabel;
use Carbon\CarbonImmutable;

/**
 * SIGN-IN PROBLEMS — sign-ins refused on this person's own address, read from the real sign-in log (2026-10-24).
 *
 * The log (`login_activity`, written by RecordAuthActivity) records a LOCKOUT each time the staff/owner sign-in refuses an
 * address after five wrong passwords in a minute (LoginRequest); it keeps the address that was typed, the IP and the browser,
 * for LoginActivity::RETENTION_DAYS. Nothing else is invented on top: no place name (there is no location service), no
 * "suspicious" verdict — the person decides whether it was them.
 *
 * ⚠️ ONLY the staff and owners' own sign-in (`web`). A wrong password at the platform console's sign-in with the same address
 * is an attempt on the console, never on this account's sign-in. ⚠️ The address matches regardless of case through the
 * column's case-insensitive collation (the same match OrganizationSignIns relies on).
 *
 * "This was me" stamps `users.security_alerts_seen_at`; only refusals AFTER it are shown, so a new one shows again.
 */
final class SignInProblems
{
    /**
     * @return array{count: int, window_days: int, latest: array{at: CarbonImmutable, device: string, ip: ?string}|null}
     */
    public static function for(User $user): array
    {
        $email = (string) $user->email;
        $since = CarbonImmutable::now()->subDays(LoginActivity::RETENTION_DAYS);
        $seen = $user->security_alerts_seen_at;
        if ($seen !== null && CarbonImmutable::parse($seen)->greaterThan($since)) {
            $since = CarbonImmutable::parse($seen);
        }

        $query = fn () => LoginActivity::query()
            ->where('guard', 'web')
            // lockouts only — the card says sign-in was paused, which is what a lockout is (the review: a `failed` row,
            // should one ever be written for this sign-in, would be one wrong password, not a pause)
            ->where('event', 'lockout')
            ->where('identifier', $email)
            ->where('occurred_at', '>', $since);

        $count = $email === '' ? 0 : $query()->count();
        $row = $count > 0 ? $query()->orderByDesc('occurred_at')->first() : null;
        $zone = is_string($user->timezone) && $user->timezone !== '' ? $user->timezone : (string) config('app.timezone');

        return [
            'count' => $count,
            'window_days' => LoginActivity::RETENTION_DAYS,
            'latest' => $row === null ? null : [
                'at' => CarbonImmutable::parse($row->occurred_at)->setTimezone($zone),
                'device' => DeviceLabel::from($row->user_agent),
                'ip' => $row->ip !== null ? (string) $row->ip : null,
            ],
        ];
    }
}
