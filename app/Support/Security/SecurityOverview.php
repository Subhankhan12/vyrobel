<?php

declare(strict_types=1);

namespace App\Support\Security;

use App\Models\LoginApproval;
use App\Models\Passkey;
use App\Models\User;
use App\Services\NotificationPreferences;
use App\Support\Auth\LoginApprovalService;
use App\Support\Auth\TrustedDeviceService;
use App\Support\Mail\MailSafetyGuard;
use App\Support\Platform\DeviceLabel;
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
     *               approval: array{shown: bool, on: bool, available: bool}, problems: array<string, mixed>,
     *               alerts: array{key: string, on: bool, always_on: bool, email: bool}, timeout: array{minutes: ?int},
     *               waiting: list<array{id: string, device: string, ip: ?string, when: string}>, passwordChanged: ?string}
     */
    public static function for(User $user, Request $request, bool $approvalHere): array
    {
        $twoFactorOn = $user->hasTwoFactorEnabled();
        $codes = self::recoveryCodes($user);
        $passkeys = self::passkeys($user);
        $approval = self::loginApproval($user, $approvalHere);
        $alerts = self::loginAlerts($user);

        return [
            'score' => SecurityScore::from([
                'password' => ['done' => $user->hasPassword(), 'offered' => true],
                'two_factor' => ['done' => $twoFactorOn, 'offered' => true],
                'recovery_codes' => ['done' => $twoFactorOn && $codes['left'] > 0, 'offered' => true],
                'passkey' => ['done' => $passkeys !== [], 'offered' => true],
                // ⚠️ offered only where an approval can reach the person — on while email is off is a sign-in nobody can
                //    approve, not a protection, so it earns nothing (the page warns instead)
                'login_approval' => ['done' => $approval['on'] && $approval['available'], 'offered' => $approval['available']],
                'login_alerts' => ['done' => $alerts['on'], 'offered' => true],
            ]),
            'twoFactorOn' => $twoFactorOn,
            'recoveryCodes' => $codes,
            'passkeys' => $passkeys,
            'trusted' => self::trustedDevices($user, $request),
            'approval' => $approval,
            'problems' => SignInProblems::for($user),
            'alerts' => $alerts,
            'timeout' => ['minutes' => SessionTimeout::minutesFor($user)],
            'waiting' => $approval['shown'] ? self::waitingApprovals($user) : [],
            'passwordChanged' => $user->password_changed_at !== null ? CarbonImmutable::parse($user->password_changed_at)->diffForHumans() : null,
        ];
    }

    /**
     * Login alerts (Part 3, NewDeviceSignIn): which preference governs them for this person, whether it is on, whether it can be
     * switched (an owner's is always on — the owner settings promise it), and whether email would carry it today.
     *
     * @return array{key: string, on: bool, always_on: bool, email: bool}
     */
    public static function loginAlerts(User $user): array
    {
        $key = NewDeviceSignIn::eventKeyFor($user);
        $prefs = app(NotificationPreferences::class);

        return [
            'key' => $key,
            'on' => $prefs->allows($user, $key, 'in_app'),
            'always_on' => NotificationPreferences::isAlwaysOn($key),
            'email' => MailSafetyGuard::sendingNow() && $prefs->allows($user, $key, 'email'),
        ];
    }

    /**
     * The sign-ins waiting for this person's approval, as the card shows them — the browser and IP they came from, how long ago.
     *
     * @return list<array{id: string, device: string, ip: ?string, when: string}>
     */
    public static function waitingApprovals(User $user): array
    {
        return app(LoginApprovalService::class)->waiting($user)
            ->map(fn (LoginApproval $a): array => [
                'id' => (string) $a->getKey(),
                'device' => DeviceLabel::from($a->user_agent),
                'ip' => $a->ip !== null ? (string) $a->ip : null,
                'when' => $a->created_at !== null ? CarbonImmutable::parse($a->created_at)->diffForHumans() : __('php/support/security/security_overview.just_now'),
            ])->values()->all();
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
                'label' => (string) ($p->label ?? __('php/support/security/security_overview.passkey')),
                'last_used' => $p->last_used_at !== null ? CarbonImmutable::parse($p->last_used_at)->diffForHumans() : __('php/support/security/security_overview.not_used_yet'),
            ])->values()->all();
    }

    /** @return list<array{id: string, label: string, ip: string, last_used: ?string, expires: ?string, current: bool}> */
    public static function trustedDevices(User $user, Request $request): array
    {
        return app(TrustedDeviceService::class)->forUser($user, $request)
            ->map(fn ($d): array => [
                'id' => (string) $d->getKey(),
                'label' => (string) ($d->label ?? $d->user_agent ?? __('php/support/security/security_overview.trusted_device')),
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
