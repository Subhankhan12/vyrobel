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
use App\Support\Auth\SocialProviders;
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
 * Honesty (invariant #19): active sessions, trusted devices and the IP allowlist are real (ActiveSessions,
 * TrustedDeviceService, EnforceIpAllowlist); connected apps is the one honest empty state — no staff OAuth exists.
 *
 * ⚠️⚠️ 2026-10-05 (the founder: "this page design is not good. Fix it."): rebuilt as two named groups of equal cards —
 * Signing in (password · two-factor · passkeys · sign-in accounts) and Devices and access (sessions · trusted devices ·
 * IP allowlist · connected apps) — under one header, every dialog on the shared card. Each password form is marked
 * (`_form`) so its error shows in that form only, and the dialog it came from reopens with it.
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
                'label' => SocialProviders::label((string) $i->provider),
                'email' => (string) ($i->provider_email ?? ''),
                'last_used' => $i->last_used_at !== null ? CarbonImmutable::parse($i->last_used_at)->diffForHumans() : 'not used yet',
                'can_unlink' => SignInCredentials::mayUnlink($user, (string) $i->getKey()),
            ])->all()
            : [];

        $twoFactor = $this->twoFactorPanel('app.security.2fa');
        $linked = array_column($connections, 'provider');
        $x = SocialProviders::resolve('twitter');
        // the dialog a refused form came from, opened again with its error inside it (`_form`, set by each form)
        $errored = session()->has('errors') ? old('_form') : null;

        return view('livewire.operator.operator-security', [
            'viewer' => $this->viewer(),
            'passkeys' => $passkeys,
            'connections' => $connections,
            'socialEnabled' => app(SocialIdentityProvider::class)->enabled(),
            'hasPassword' => $user instanceof User && $user->hasPassword(),
            'twoFactor' => $twoFactor,
            'twoFactorOn' => $twoFactor['state'] === 'enabled',
            // a sign-in method can be connected only when it is switched on here and not connected already
            'connectable' => array_values(array_filter([
                app(SocialIdentityProvider::class)->enabled() && ! in_array('google', $linked, true) ? ['key' => 'google', 'label' => 'Google', 'route' => 'app.security.connections.link'] : null,
                $x !== null && $x->enabled() && ! in_array('twitter', $linked, true) ? ['key' => 'twitter', 'label' => 'X', 'route' => 'app.security.connections.twitter'] : null,
            ])),
            'errored' => is_string($errored) ? $errored : null,
            'erroredDevice' => $errored === 'revoke' && is_string(old('device_id')) ? (string) old('device_id') : null,
            'canManageIp' => (bool) $user?->can('settings.manage'),
            'sessions' => $sessions,
            'trusted' => $trusted,
            'ipRules' => $ipRules,
            // Overview counts — real counts of the four lists (connected-apps is honestly 0: no operator OAuth).
            'counts' => ['sessions' => count($sessions), 'apps' => 0, 'trusted' => count($trusted), 'ip' => count($ipRules)],
            'currentIp' => (string) (request()->ip() ?? ''),
        ]);
    }
}
