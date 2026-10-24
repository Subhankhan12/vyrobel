<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Contracts\Auth\SocialIdentityProvider;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Livewire\Concerns\PreparesTwoFactor;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\IpAllowRule;
use App\Models\User;
use App\Support\Auth\SignInCredentials;
use App\Support\Auth\SocialProviders;
use App\Support\Security\ActiveSessions;
use App\Support\Security\SecurityOverview;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator SECURITY (Auth) — every back-office user's own account security. It lives here, not on {@see OperatorSettings},
 * because `/app/settings` is gated `can:properties.manage` (managers only) whereas EVERY back-office user must be able to
 * change their own password / manage their own 2FA — so this page is behind `auth` + EnsureBackOfficeAccess (#26) and NO
 * fine-grained permission.
 *
 * The change-password write is the guarded POST → {@see ChangePasswordController} (the user is resolved from `auth()`, the
 * current password is verified, the hash+rotate is the shared reset path). The 2FA section is the shared
 * {@see PreparesTwoFactor} trait — real TOTP enrollment (secret + QR, confirm-before-activate) + one-time recovery codes +
 * login-time enforcement (invariant #31), encrypted at rest.
 *
 * Honesty (invariant #19): active sessions, trusted devices and the IP allowlist are real (ActiveSessions,
 * TrustedDeviceService, EnforceIpAllowlist); connected apps is the one honest empty state — no staff OAuth exists.
 *
 * ⚠️⚠️ 2026-10-24 (the founder's design): the score, passkeys, trusted devices, recovery codes and sign-in problems come from
 * {@see SecurityOverview} — the SAME reads the owner page makes, so one account reads the same on both pages. This page adds
 * only what is its own: sign-in accounts, sessions with a per-device sign-out, the IP allowlist.
 * 2026-10-05: each password form is marked (`_form`) so its error shows in its own dialog, and that dialog reopens with it.
 */
#[Layout('components.layouts.operator')]
class OperatorSecurity extends Component
{
    use FormatsOperatorData, PreparesTwoFactor;

    public function render(): View
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $overview = SecurityOverview::for($user, request(), false);
        $sessions = ActiveSessions::forUser((string) $user->getKey());

        $ipRules = IpAllowRule::query()->orderBy('created_at')->get()
            ->map(fn (IpAllowRule $r): array => ['id' => (string) $r->getKey(), 'ip' => (string) $r->ip_address, 'label' => (string) ($r->label ?? '')])->all();

        // ⚠️ CONNECTED SIGN-IN ACCOUNTS — the user's OWN linked providers, self-only by the same user-scoped read as passkeys.
        //    `canUnlink` is computed HERE only so the control can explain itself; the refusal that actually protects the
        //    account is enforced in the unlink route.
        $connections = $user->identities()->orderBy('created_at')->get()->map(fn ($i): array => [
            'id' => (string) $i->getKey(),
            'provider' => (string) $i->provider,
            'label' => SocialProviders::label((string) $i->provider),
            'email' => (string) ($i->provider_email ?? ''),
            'last_used' => $i->last_used_at !== null ? CarbonImmutable::parse($i->last_used_at)->diffForHumans() : 'not used yet',
            'can_unlink' => SignInCredentials::mayUnlink($user, (string) $i->getKey()),
        ])->all();

        $linked = array_column($connections, 'provider');
        $x = SocialProviders::resolve('twitter');
        // the dialog a refused form came from, opened again with its error inside it (`_form`, set by each form)
        $errored = session()->has('errors') ? old('_form') : null;

        return view('livewire.operator.operator-security', $overview + [
            'viewer' => $this->viewer(),
            'connections' => $connections,
            'hasPassword' => $user->hasPassword(),
            'twoFactor' => $this->twoFactorPanel('app.security.2fa'),
            // a sign-in method can be connected only when it is switched on here and not connected already
            'connectable' => array_values(array_filter([
                app(SocialIdentityProvider::class)->enabled() && ! in_array('google', $linked, true) ? ['key' => 'google', 'label' => 'Google', 'route' => 'app.security.connections.link'] : null,
                $x !== null && $x->enabled() && ! in_array('twitter', $linked, true) ? ['key' => 'twitter', 'label' => 'X', 'route' => 'app.security.connections.twitter'] : null,
            ])),
            'errored' => is_string($errored) ? $errored : null,
            'erroredDevice' => $errored === 'revoke' && is_string(old('device_id')) ? (string) old('device_id') : null,
            'pkErrored' => SecurityOverview::erroredPasskey($errored, $overview['passkeys']),
            'canManageIp' => $user->can('settings.manage'),
            'sessions' => $sessions,
            'ipRules' => $ipRules,
            'currentIp' => (string) (request()->ip() ?? ''),
        ]);
    }
}
