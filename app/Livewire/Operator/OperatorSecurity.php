<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Livewire\Concerns\PreparesTwoFactor;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\IpAllowRule;
use App\Models\User;
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

        return view('livewire.operator.operator-security', [
            'viewer' => $this->viewer(),
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
