<?php

declare(strict_types=1);

namespace App\Support\Security;

use App\Models\Passkey;
use App\Models\User;
use App\Support\Auth\TrustedDeviceService;
use App\Support\Mail\MailSafetyGuard;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * What BOTH Security pages show about the signed-in person — the back office's and the owner portal's (2026-10-24). Each
 * page added its own copy of these reads (passkeys, trusted devices, the score), and two copies of one concept drift (§N.8f):
 * the owner page scored three protections while the back office scored none. Each page now adds only what is its own (the
 * IP allowlist and sign-in accounts; connected apps and Deactivate).
 *
 * Self-only: every read is keyed on the person passed in, never a request parameter.
 */
final class SecurityOverview
{
    /**
     * @param  bool  $approvalHere  whether this page offers login approval at all
     * @return array{score: array<string, mixed>, twoFactorOn: bool, recoveryCodes: array{left: int, total: int},
     *               passkeys: list<array{id: string, label: string, last_used: string}>,
     *               trusted: list<array{id: string, label: string, ip: string, last_used: ?string, expires: ?string, current: bool}>,
     *               approval: array{shown: bool, on: bool, available: bool}, problems: array<string, mixed>}
     */
    public static function for(User $user, Request $request, bool $approvalHere): array
    {
        $twoFactorOn = $user->hasTwoFactorEnabled();
        $codes = self::recoveryCodes($user);
        $passkeys = self::passkeys($user);
        $approval = self::loginApproval($user, $approvalHere);

        return [
            'score' => SecurityScore::from([
                'password' => ['done' => $user->hasPassword(), 'offered' => true],
                'two_factor' => ['done' => $twoFactorOn, 'offered' => true],
                'recovery_codes' => ['done' => $twoFactorOn && $codes['left'] > 0, 'offered' => true],
                'passkey' => ['done' => $passkeys !== [], 'offered' => true],
                // ⚠️ offered only where an approval can reach the person — on while email is off is a sign-in nobody can
                //    approve, not a protection, so it earns nothing (the page warns instead)
                'login_approval' => ['done' => $approval['on'] && $approval['available'], 'offered' => $approval['available']],
                'login_alerts' => ['done' => false, 'offered' => false],
            ]),
            'twoFactorOn' => $twoFactorOn,
            'recoveryCodes' => $codes,
            'passkeys' => $passkeys,
            'trusted' => self::trustedDevices($user, $request),
            'approval' => $approval,
            'problems' => SignInProblems::for($user),
        ];
    }

    /**
     * Login approval on this page: shown at all, on, and whether it can work — its approval link is an email, so it can
     * be switched ON only while email really goes out (MailSafetyGuard::sendingNow). Switching it OFF is always allowed.
     *
     * @return array{shown: bool, on: bool, available: bool}
     */
    public static function loginApproval(User $user, bool $approvalHere): array
    {
        return [
            'shown' => $approvalHere,
            'on' => $user->requiresLoginApproval(),
            'available' => $approvalHere && MailSafetyGuard::sendingNow(),
        ];
    }

    /** @return array{left: int, total: int} */
    public static function recoveryCodes(User $user): array
    {
        // the cast decrypts to [{code, used_at}]; anything else (a hand-written fixture, a corrupted value) is no codes
        $codes = $user->hasTwoFactorEnabled() ? $user->two_factor_recovery_codes : null;
        $codes = is_array($codes) ? array_values(array_filter($codes, 'is_array')) : [];

        return [
            'left' => count(array_filter($codes, fn (array $c): bool => ($c['used_at'] ?? null) === null)),
            'total' => count($codes),
        ];
    }

    /** @return list<array{id: string, label: string, last_used: string}> */
    public static function passkeys(User $user): array
    {
        return Passkey::query()
            ->where('user_id', (string) $user->getKey())
            ->orderByDesc('last_used_at')
            ->get()
            ->map(fn (Passkey $p): array => [
                'id' => (string) $p->id,
                'label' => (string) ($p->label ?? 'Passkey'),
                'last_used' => $p->last_used_at !== null ? CarbonImmutable::parse($p->last_used_at)->diffForHumans() : 'not used yet',
            ])->values()->all();
    }

    /** @return list<array{id: string, label: string, ip: string, last_used: ?string, expires: ?string, current: bool}> */
    public static function trustedDevices(User $user, Request $request): array
    {
        return app(TrustedDeviceService::class)->forUser($user, $request)
            ->map(fn ($d): array => [
                'id' => (string) $d->getKey(),
                'label' => (string) ($d->label ?? $d->user_agent ?? 'Trusted device'),
                'ip' => (string) ($d->ip ?? ''),
                'last_used' => $d->last_used_at !== null ? CarbonImmutable::parse($d->last_used_at)->diffForHumans() : null,
                'expires' => $d->expires_at !== null ? CarbonImmutable::parse($d->expires_at)->diffForHumans() : null,
                'current' => (bool) $d->getAttribute('is_current'),
            ])->values()->all();
    }

    /**
     * The passkey whose remove was refused (a wrong password), so its dialog reopens for it — only one of THIS person's,
     * found among their own (the posted id is never trusted on its own).
     *
     * @param  list<array{id: string, label: string, last_used: string}>  $passkeys
     * @return array{id: string, label: string}|null
     */
    public static function erroredPasskey(mixed $errored, array $passkeys): ?array
    {
        $id = old('_passkey');
        if ($errored !== 'passkey-remove' || ! is_string($id)) {
            return null;
        }
        foreach ($passkeys as $p) {
            if ($p['id'] === $id) {
                return ['id' => $p['id'], 'label' => $p['label']];
            }
        }

        return null;
    }
}
