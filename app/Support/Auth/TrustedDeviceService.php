<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * TRUSTED DEVICES (Wave B · GATE 4) — the "remember this device, skip 2FA here" mechanism. A trusted browser
 * holds a random bearer token in the httpOnly `vy_td` cookie; only its SHA-256 hash is stored ({@see TrustedDevice}).
 *
 * SECURITY posture:
 *   • the cookie is a bearer secret → stored only as a hash; a leaked DB row cannot reconstruct it;
 *   • it is bound to a user_id, hard-expires ({@see TRUST_DAYS}), and is revocable — a stolen cookie is bounded;
 *   • it ONLY lets a browser skip the 2FA CHALLENGE — the password is still required to log in;
 *   • recognition rotates nothing but `last_used_at`; issuing/forgetting return a Cookie the caller queues.
 */
class TrustedDeviceService
{
    /** The cookie name the browser holds (encrypted at rest by Laravel's EncryptCookies). */
    public const COOKIE = 'vy_td';

    /** How long a trust lasts before the device must pass 2FA again. */
    private const TRUST_DAYS = 30;

    /**
     * Issue a trust for THIS browser after a successful 2FA challenge: persist the token hash + metadata and
     * return the cookie to queue on the response. Old expired/revoked rows for the user are left for the audit
     * trail; a fresh token is always minted (never reused).
     */
    public function issue(User $user, Request $request): SymfonyCookie
    {
        $token = Str::random(48);
        $expiresAt = Carbon::now()->addDays(self::TRUST_DAYS);

        TrustedDevice::create([
            'user_id' => (string) $user->getKey(),
            'token_hash' => hash('sha256', $token),
            'label' => $this->labelFor($request),
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'last_used_at' => Carbon::now(),
            'expires_at' => $expiresAt,
        ]);

        // httpOnly + sameSite=lax; minutes for the cookie lifetime. Laravel encrypts the value in transit/at rest.
        return Cookie::make(self::COOKIE, $token, self::TRUST_DAYS * 24 * 60, null, null, $request->isSecure(), true, false, 'lax');
    }

    /**
     * Does THIS browser hold a LIVE trust for this user? Reads the cookie, hashes it, matches a non-expired,
     * non-revoked row for the user, and touches `last_used_at`. A missing/forged/expired cookie → false.
     */
    public function recognizes(User $user, Request $request): bool
    {
        $token = $request->cookie(self::COOKIE);
        if (! is_string($token) || $token === '') {
            return false;
        }

        $device = TrustedDevice::query()
            ->where('user_id', (string) $user->getKey())
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($device === null) {
            return false;
        }

        $device->forceFill(['last_used_at' => Carbon::now()])->saveQuietly();

        return true;
    }

    /** A cookie that expires the trust on THIS browser (used when the device's own trust is revoked). */
    public function forgetCookie(): SymfonyCookie
    {
        return Cookie::forget(self::COOKIE);
    }

    /**
     * The user's LIVE trusted devices (self-only), newest-first, with a "this device" flag for the current
     * browser so the Security page can label it.
     *
     * @return Collection<int, TrustedDevice>
     */
    public function forUser(User $user, Request $request): Collection
    {
        $current = $request->cookie(self::COOKIE);
        $currentHash = is_string($current) && $current !== '' ? hash('sha256', $current) : null;

        return TrustedDevice::query()
            ->where('user_id', (string) $user->getKey())
            ->whereNull('revoked_at')
            ->where('expires_at', '>', Carbon::now())
            ->orderByDesc('last_used_at')
            ->get()
            ->each(fn (TrustedDevice $d) => $d->setAttribute('is_current', $currentHash !== null && $d->token_hash === $currentHash));
    }

    /**
     * Revoke ONE of the user's trusted devices by id (self-only — the where(user_id) makes a cross-user id a
     * no-op). Returns the device if it was the CURRENT browser (so the caller can also forget the cookie).
     */
    public function revoke(User $user, string $deviceId, Request $request): ?TrustedDevice
    {
        $device = TrustedDevice::query()
            ->where('user_id', (string) $user->getKey())
            ->whereNull('revoked_at')
            ->find($deviceId);

        if ($device === null) {
            return null;
        }

        $device->forceFill(['revoked_at' => Carbon::now()])->save();

        $current = $request->cookie(self::COOKIE);
        $isCurrent = is_string($current) && $current !== '' && $device->token_hash === hash('sha256', $current);

        return $isCurrent ? $device : null;
    }

    /** Revoke ALL of the user's trusted devices (e.g. on "sign out everywhere" / a password change). */
    public function revokeAll(User $user): void
    {
        TrustedDevice::query()
            ->where('user_id', (string) $user->getKey())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => Carbon::now()]);
    }

    /** A short, human device label from the user-agent (browser + OS) — no fingerprinting, just a friendly name. */
    private function labelFor(Request $request): string
    {
        $ua = (string) $request->userAgent();

        $browser = match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'OPR') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Browser',
        };

        $os = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') || str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'device',
        };

        return $browser.' on '.$os;
    }
}
