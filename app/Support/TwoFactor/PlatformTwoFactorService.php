<?php

declare(strict_types=1);

namespace App\Support\TwoFactor;

use App\Models\PlatformAdmin;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * SUPER ADMIN · GATE 0 — TOTP two-factor for PLATFORM ADMINS, on the same proven primitives as the tenant
 * {@see TwoFactorService} (google2fa maths + bacon QR — NO hand-rolled crypto).
 *
 * This is a DELIBERATE, self-contained parallel of {@see TwoFactorService} rather than a refactor of it: the
 * shared service is tightly typed to {@see User}, and Gate 0's absolute constraint is to disturb
 * NOTHING in the tenant realms. Keeping the admin 2FA fully additive here means the tenant 2FA path (and its
 * tests) are byte-for-byte untouched. (A future refactor could extract a model-agnostic TOTP core shared by
 * both — deliberately out of scope for this isolation-first gate.)
 *
 * For platform admins 2FA is MANDATORY: the enrollment secret is UNCONFIRMED until a valid code confirms it
 * ({@see confirm()}), and {@see PlatformAdmin::hasTwoFactorEnabled()} gates the whole /admin area — so a
 * seeded admin is forced to enrol on first login.
 */
class PlatformTwoFactorService
{
    private const RECOVERY_CODE_COUNT = 8;

    public function __construct(private Google2FA $engine) {}

    /** Begin enrollment: a fresh encrypted secret, UNCONFIRMED (does not satisfy the mandatory-2FA gate yet). */
    public function startEnrollment(PlatformAdmin $admin): void
    {
        $admin->forceFill([
            'two_factor_secret' => $this->engine->generateSecretKey(),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_enabled' => false,
        ])->save();
    }

    /** The manual-entry secret for the current (typically unconfirmed) enrollment, or null. */
    public function secretKey(PlatformAdmin $admin): ?string
    {
        return $admin->two_factor_secret !== null ? (string) $admin->two_factor_secret : null;
    }

    /** An inline SVG QR for the current secret's otpauth URL, or null when there is no secret. */
    public function qrCodeSvg(PlatformAdmin $admin): ?string
    {
        $secret = $admin->two_factor_secret;
        if ($secret === null) {
            return null;
        }

        $url = $this->engine->getQRCodeUrl(
            (string) config('app.name', 'Vyrobel').' Admin',
            (string) $admin->email,
            (string) $secret,
        );

        $renderer = new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($url);
    }

    /**
     * Confirm enrollment with a 6-digit code → mark confirmed + generate one-time recovery codes (returned
     * PLAINTEXT to show once). Invalid code → null (2FA NOT activated).
     *
     * @return list<string>|null
     */
    public function confirm(PlatformAdmin $admin, string $code): ?array
    {
        $secret = $admin->two_factor_secret;
        if ($secret === null || ! $this->verify((string) $secret, $code)) {
            return null;
        }

        $codes = $this->newRecoveryCodes();
        $admin->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_enabled' => true,
            'two_factor_recovery_codes' => $this->wrapCodes($codes),
        ])->save();

        return $codes;
    }

    /** Verify a TOTP code against a secret (the library's ±1 window tolerance). */
    public function verify(string $secret, string $code): bool
    {
        return $this->engine->verifyKey($secret, $code);
    }

    /**
     * Verify a LOGIN-CHALLENGE input: a 6-digit TOTP OR an unused recovery code (single-use — consumed).
     */
    public function verifyChallenge(PlatformAdmin $admin, string $input): bool
    {
        $secret = $admin->two_factor_secret;

        $totp = preg_replace('/\D/', '', $input) ?? '';
        if ($secret !== null && strlen($totp) === 6 && $this->verify((string) $secret, $totp)) {
            return true;
        }

        return $this->consumeRecoveryCode($admin, $input);
    }

    private function consumeRecoveryCode(PlatformAdmin $admin, string $input): bool
    {
        $normalized = Str::upper(trim($input));
        $codes = $admin->two_factor_recovery_codes;

        if ($codes === null) {
            return false;
        }

        foreach ($codes as $i => $entry) {
            if ($entry['used_at'] === null && hash_equals($entry['code'], $normalized)) {
                $codes[$i]['used_at'] = now()->toIso8601String();
                $admin->forceFill(['two_factor_recovery_codes' => $codes])->save();

                return true;
            }
        }

        return false;
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
