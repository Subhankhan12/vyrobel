<?php

declare(strict_types=1);

namespace App\Support\TwoFactor;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP two-factor authentication via `pragmarx/google2fa` (the TOTP maths) + `bacon/bacon-qr-code` (the QR
 * SVG) — NO hand-rolled crypto. Orchestrates enrollment (secret → confirm), one-time recovery codes, and
 * disable on a {@see User} whose `two_factor_secret` / `two_factor_recovery_codes` are ENCRYPTED casts.
 *
 * ⚠️ The CALLER always passes the SELF-resolved auth user (never a request id) — this service has no notion
 * of "which user"; it only mutates the one it is handed (the self-only guard lives at the controller).
 *
 * The active-vs-pending distinction is load-bearing (anti-lockout): {@see startEnrollment()} stores a secret
 * with `two_factor_confirmed_at = null`, which {@see User::hasTwoFactorEnabled()} treats as NOT active — so a
 * generated-but-unconfirmed secret never gates login. Only {@see confirm()} (a valid 6-digit code) activates.
 */
class TwoFactorService
{
    private const RECOVERY_CODE_COUNT = 8;

    public function __construct(private Google2FA $engine) {}

    /** Begin enrollment: a fresh encrypted secret, UNCONFIRMED (does not gate login until confirmed). */
    public function startEnrollment(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => $this->engine->generateSecretKey(),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_enabled' => false,
        ])->save();
    }

    /** The manual-entry secret for the current (typically unconfirmed) enrollment, or null. */
    public function secretKey(User $user): ?string
    {
        return $user->two_factor_secret !== null ? (string) $user->two_factor_secret : null;
    }

    /** An inline SVG QR for the current secret's otpauth URL, or null when there is no secret. */
    public function qrCodeSvg(User $user): ?string
    {
        $secret = $user->two_factor_secret;
        if ($secret === null) {
            return null;
        }

        $url = $this->engine->getQRCodeUrl(
            (string) config('app.name', 'Vyrobel'),
            (string) $user->email,
            (string) $secret,
        );

        $renderer = new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($url);
    }

    /**
     * Confirm enrollment with a 6-digit code. On success: mark confirmed + generate one-time recovery codes,
     * and return the PLAINTEXT codes to show ONCE. On an invalid code: null — 2FA is NOT activated.
     *
     * @return list<string>|null
     */
    public function confirm(User $user, string $code): ?array
    {
        $secret = $user->two_factor_secret;
        if ($secret === null || ! $this->verify((string) $secret, $code)) {
            return null;
        }

        $codes = $this->newRecoveryCodes();
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_enabled' => true,   // display mirror only — hasTwoFactorEnabled() is the real gate
            'two_factor_recovery_codes' => $this->wrapCodes($codes),
        ])->save();

        return $codes;
    }

    /**
     * Replace the recovery codes (invalidating the old set), returning the new PLAINTEXT codes to show once.
     *
     * @return list<string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->newRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $this->wrapCodes($codes)])->save();

        return $codes;
    }

    /** Turn 2FA off entirely — clears the secret, recovery codes and confirmation. */
    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_enabled' => false,
        ])->save();
    }

    /** Verify a TOTP code against a secret (the library's ±1 time-window tolerance). */
    public function verify(string $secret, string $code): bool
    {
        return $this->engine->verifyKey($secret, $code);
    }

    /**
     * @param  list<string>  $codes
     * @return list<array{code: string, used_at: null}>
     */
    private function wrapCodes(array $codes): array
    {
        return array_map(fn (string $code): array => ['code' => $code, 'used_at' => null], $codes);
    }

    /** @return list<string> */
    private function newRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $codes[] = Str::upper(Str::random(5)).'-'.Str::upper(Str::random(5));
        }

        return $codes;
    }
}
