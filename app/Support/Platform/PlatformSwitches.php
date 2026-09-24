<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Models\PlatformAdmin;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * ADMIN 01 · PHASE 1 — THE PLATFORM KILL SWITCHES: stored in the database, read per request, flipped from the
 * console without a deploy.
 *
 * ⚠️⚠️ WHY THE DATABASE: production caches its config, so a switch in `.env` or `config/` could only change with
 * a deploy (KNOWN-LIMITS L14 — measured). The cache here is 30 seconds and is BUSTED ON WRITE, so a flip takes
 * effect on the very next request of the process that wrote it, and within 30 s everywhere else.
 *
 * ⚠️⚠️ EVERY SWITCH DEFAULTS TO TODAY'S BEHAVIOUR — ON:
 *   · a MISSING ROW is ON, so an empty table after a deploy changes nothing;
 *   · an UNREADABLE table (the query throws) is ALSO ON, logged at warning. A kill switch that fails to read
 *     must never become a public outage that nobody is watching for. The failure is visible in the log and on
 *     the health board, not in a closed signup form.
 *
 * ⚠️⚠️ NO SWITCH TOUCHES `/admin/*`. They are read only by the public signup and social sign-in paths — a switch
 * that could lock the console out of the place that unflips it would be a trap.
 *
 * ⚠️ SIGN-UP AND SIGN-IN ARE SEPARATE ON PURPOSE. Turning off SIGN-IN with a provider locks out every user whose
 * only way in is that provider; the console shows {@see lockoutCount()} and demands a second confirmation first.
 */
final class PlatformSwitches
{
    public const SIGNUP = 'signup.enabled';

    public const SIGNUP_GOOGLE = 'signup.google.enabled';

    public const SIGNUP_TWITTER = 'signup.twitter.enabled';

    public const SIGNIN_GOOGLE = 'signin.google.enabled';

    public const SIGNIN_TWITTER = 'signin.twitter.enabled';

    private const CACHE_KEY = 'platform:switches:v1';

    private const CACHE_SECONDS = 30;

    /**
     * Every switch that exists, with what it controls. ⚠️ An unknown key is an exception, never a silent "on".
     *
     * @return array<string, array{label: string, controls: string, provider: ?string, kind: string}>
     */
    public static function catalogue(): array
    {
        return [
            self::SIGNUP => ['label' => 'New sign-ups (all sources)', 'controls' => 'Whether anyone can create a new Vyrobel account and organisation — by email, Google or X.', 'provider' => null, 'kind' => 'signup'],
            self::SIGNUP_GOOGLE => ['label' => 'Sign-up with Google', 'controls' => 'Whether a new account can be created by signing in with Google.', 'provider' => 'google', 'kind' => 'signup'],
            self::SIGNUP_TWITTER => ['label' => 'Sign-up with X', 'controls' => 'Whether a new account can be created by signing in with X.', 'provider' => 'twitter', 'kind' => 'signup'],
            self::SIGNIN_GOOGLE => ['label' => 'Sign-in with Google', 'controls' => 'Whether an existing account can sign in with Google.', 'provider' => 'google', 'kind' => 'signin'],
            self::SIGNIN_TWITTER => ['label' => 'Sign-in with X', 'controls' => 'Whether an existing account can sign in with X.', 'provider' => 'twitter', 'kind' => 'signin'],
        ];
    }

    public static function enabled(string $key): bool
    {
        self::assertKnown($key);

        $value = self::values()[$key] ?? null;

        return $value === null ? true : (bool) $value;   // ⚠️ missing row → ON
    }

    /** New accounts may be created — optionally through a specific provider. */
    public static function signupAllowed(?string $provider = null): bool
    {
        if (! self::enabled(self::SIGNUP)) {
            return false;
        }

        return match ($provider) {
            null => true,
            'google' => self::enabled(self::SIGNUP_GOOGLE),
            'twitter' => self::enabled(self::SIGNUP_TWITTER),
            default => true,   // a provider with no switch of its own is governed by the global switch alone
        };
    }

    /** Existing accounts may sign in through this provider. */
    public static function signinAllowed(string $provider): bool
    {
        return match ($provider) {
            'google' => self::enabled(self::SIGNIN_GOOGLE),
            'twitter' => self::enabled(self::SIGNIN_TWITTER),
            default => true,
        };
    }

    /** Flip a switch. Busts the cache so this process sees it on its very next read. */
    public static function set(string $key, bool $on, ?PlatformAdmin $by): void
    {
        self::assertKnown($key);

        PlatformSetting::query()->updateOrCreate(['key' => $key], ['value' => $on, 'updated_by' => $by?->getKey()]);

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * ⚠️⚠️ HOW MANY USERS TURNING OFF SIGN-IN WITH THIS PROVIDER WOULD LOCK OUT: accounts with NO password whose
     * ONLY linked identity is this provider. Counted from the real `users` and `user_identities` rows.
     */
    public static function lockoutCount(string $provider): int
    {
        return User::query()
            ->whereNull('password')
            ->whereHas('identities', fn ($q) => $q->where('provider', $provider))
            ->whereDoesntHave('identities', fn ($q) => $q->where('provider', '!=', $provider))
            ->count();
    }

    /** @return array<string, array{on: bool, updated_by: ?string, updated_at: ?string}> for the console */
    public static function states(): array
    {
        $rows = PlatformSetting::query()->whereIn('key', array_keys(self::catalogue()))->get()->keyBy('key');

        $out = [];
        foreach (array_keys(self::catalogue()) as $key) {
            $row = $rows->get($key);
            $out[$key] = [
                'on' => $row === null || $row->value === null ? true : (bool) $row->value,
                'updated_by' => $row?->updated_by,
                'updated_at' => $row?->updated_at?->toDateTimeString(),
            ];
        }

        return $out;
    }

    /** @return array<string, mixed> key => stored value (only rows that exist) */
    private static function values(): array
    {
        try {
            // ⚠️ Through the MODEL, so the `array` (JSON) cast is applied: a stored JSON `false` must come back
            //    as boolean false. Read raw, it would be the string "false" — and (bool) "false" is TRUE.
            return Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, static fn (): array => PlatformSetting::query()
                ->whereIn('key', array_keys(self::catalogue()))
                ->get()
                ->mapWithKeys(static fn (PlatformSetting $row): array => [$row->key => $row->value])
                ->all());
        } catch (Throwable $e) {
            // ⚠️ FAIL TOWARDS TODAY'S BEHAVIOUR (ON), LOUDLY IN THE LOG — never towards a closed front door.
            Log::warning('Platform switches could not be read; every switch is treated as ON.', ['exception' => $e->getMessage()]);

            return [];
        }
    }

    private static function assertKnown(string $key): void
    {
        if (! array_key_exists($key, self::catalogue())) {
            throw new InvalidArgumentException("Unknown platform switch [{$key}].");
        }
    }
}
