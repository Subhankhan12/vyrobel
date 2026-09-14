<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Contracts\Auth\SocialIdentityProvider;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Livewire\Concerns\PreparesTwoFactor;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\IpAllowRule;
use App\Models\Passkey;
use App\Models\User;
use App\Support\Auth\SignInCredentials;
use App\Support\Auth\TrustedDeviceService;
use App\Support\Security\ActiveSessions;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator SECURITY (Auth) — an account-security page with TWO real features: change-password and two-factor
 * auth (TOTP). It lives here, not on {@see OperatorSettings}, because `/app/settings` is gated
 * `can:properties.manage` (managers only) whereas EVERY back-office user must be able to change their own
 * password / manage their own 2FA — so this page is behind `auth` + EnsureBackOfficeAccess (#26) and NO
 * fine-grained permission.
 *
 * The change-password write is the guarded POST → {@see ChangePasswordController} (the user is resolved from
 * `auth()`, the current password is verified, the hash+rotate is the shared reset path). The 2FA panel is the
 * shared {@see PreparesTwoFactor} trait — real TOTP enrollment (secret + QR, confirm-before-activate) +
 * one-time recovery codes + login-time enforcement (invariant #31), encrypted at rest. NOT a stub.
 *
 * Honesty (invariant #19): the design's active-sessions, trusted-devices, connected-apps and IP-rules panels
 * ARE still unbacked subsystems (docs/AUTH-INVENTORY.md) — shown here as an honest "Soon", never faked. (2FA is
 * no longer among them — it shipped 2026-07-18.)
 */
#[Layout('components.layouts.operator')]
class OperatorSecurity extends Component
{
    use FormatsOperatorData, PreparesTwoFactor;

    public function render(): View
    {
        $user = auth()->user();
        $sessions = ActiveSessions::forUser((string) ($user?->getKey() ?? ''));

        // #104b GATE 1 — trusted devices (the SAME shared #66 TrustedDeviceService the owner page uses — the
        // model is USER-scoped, so an operator with 2FA who trusted a device gets a real row) + the REAL
        // IP-allowlist (tenant-scoped; an empty list is the honest "No IP restrictions" state).
        $trusted = $user instanceof User
            ? app(TrustedDeviceService::class)->forUser($user, request())->map(fn ($d): array => [
                'id' => (string) $d->getKey(),
                'label' => (string) ($d->label ?? $d->user_agent ?? 'Trusted device'),
                'current' => (bool) $d->getAttribute('is_current'),
                'last_used' => $d->last_used_at !== null ? CarbonImmutable::parse($d->last_used_at)->diffForHumans() : null,
            ])->all()
            : [];

        $ipRules = IpAllowRule::query()->orderBy('created_at')->get()
            ->map(fn (IpAllowRule $r): array => ['id' => (string) $r->getKey(), 'ip' => (string) $r->ip_address, 'label' => (string) ($r->label ?? '')])->all();

        // D24-2 — the operator's OWN passkeys. ⚠️ The identical user-scoped read the owner page makes: the
        // `Passkey` model belongs to a USER, so an operator who registers one gets a real row and sees only
        // their own. No owner concept is involved anywhere in the subsystem.
        $passkeys = $user instanceof User
            ? Passkey::query()
                ->where('user_id', (string) $user->getKey())
                ->orderByDesc('last_used_at')
                ->get()
                ->map(fn (Passkey $p): array => [
                    'id' => (string) $p->id,
                    'label' => (string) ($p->label ?? 'Passkey'),
                    'last_used' => $p->last_used_at !== null ? CarbonImmutable::parse($p->last_used_at)->diffForHumans() : 'not used yet',
                ])->all()
            : [];

        // ⚠️ CONNECTED SIGN-IN ACCOUNTS — the user's OWN linked providers, self-only by the same user-scoped
        //    read as passkeys above. `canUnlink` is computed HERE only so the control can explain itself; the
        //    refusal that actually protects the account is enforced in the unlink route.
        $connections = $user instanceof User
            ? $user->identities()->orderBy('created_at')->get()->map(fn ($i): array => [
                'id' => (string) $i->getKey(),
                'provider' => (string) $i->provider,
                'email' => (string) ($i->provider_email ?? ''),
                'last_used' => $i->last_used_at !== null ? CarbonImmutable::parse($i->last_used_at)->diffForHumans() : 'not used yet',
                'can_unlink' => SignInCredentials::mayUnlink($user, (string) $i->getKey()),
            ])->all()
            : [];

        return view('livewire.operator.operator-security', [
            'viewer' => $this->viewer(),
            'passkeys' => $passkeys,
            'connections' => $connections,
            'socialEnabled' => app(SocialIdentityProvider::class)->enabled(),
            'hasPassword' => $user instanceof User && $user->hasPassword(),
            'twoFactor' => $this->twoFactorPanel('app.security.2fa'),
            'sessions' => $sessions,
            'trusted' => $trusted,
            'ipRules' => $ipRules,
            // Overview counts — real counts of the four lists (connected-apps is honestly 0: no operator OAuth).
            'counts' => ['sessions' => count($sessions), 'apps' => 0, 'trusted' => count($trusted), 'ip' => count($ipRules)],
            'currentIp' => (string) (request()->ip() ?? ''),
        ]);
    }
}
