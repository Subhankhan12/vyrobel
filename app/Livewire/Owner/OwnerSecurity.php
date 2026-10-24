<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Concerns\PreparesTwoFactor;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\User;
use App\Services\OAuthConnectionService;
use App\Support\OAuth\OAuthScopes;
use App\Support\Owner\OwnerContext;
use App\Support\Security\ActiveSessions;
use App\Support\Security\SecurityOverview;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner "SECURITY" (owner account area · Gate 2) — the acting login's OWN security, on the owner design system. READ view;
 * every write is a guarded controller POST (never a Livewire action).
 *
 * Per-USER (auth-resolved), so accountant/view-only SUB-USERS manage their OWN password/2FA/sessions here. The ONE
 * primary-owner-only action is DEACTIVATE (route-gated by EnsurePrimaryOwner, #42).
 *
 * ⚠️⚠️ 2026-10-24 (the founder's design): the score, passkeys, trusted devices, recovery codes, login approval and sign-in
 * problems come from {@see SecurityOverview} — the SAME reads the back office's Security page makes, so one account reads the
 * same on both. The score is App\Support\Security\SecurityScore (it was password 40 + 2FA 40 + recovery codes 20 here, and
 * nothing on the other page). This page adds only what is its own: connected apps (OAuth #72) and Deactivate.
 * ⚠️ Login approval is OFFERED only while email really goes out — its approval link is an email (OwnerSecurityController).
 */
#[Layout('components.layouts.owner')]
class OwnerSecurity extends Component
{
    use FormatsOwnerData, PreparesTwoFactor;

    public function render(): View
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $overview = SecurityOverview::for($user, request(), true);
        $errored = session()->has('errors') ? old('_form') : null;

        return view('livewire.owner.owner-security', $overview + [
            'viewer' => $this->viewer(),
            'twoFactor' => $this->twoFactorPanel('owner.security.2fa'),
            'hasPassword' => $user->hasPassword(),
            'isPrimaryOwner' => app(OwnerContext::class)->isPrimaryOwner(),
            'lastLogin' => $user->last_login_at instanceof Carbon ? $user->last_login_at : null,
            // #96 — the ONE shared definition of a session (owner + operator read the same ActiveSessions)
            'sessions' => ActiveSessions::forUser((string) $user->getKey()),
            // Connected apps (OAuth #72) — the REAL third-party apps THIS user authorized (self-only), each with the exact
            // scopes granted + a real Revoke. Scope ids get their human descriptions for the UI.
            'connectedApps' => array_map(fn (array $c): array => [
                'client_id' => $c['client_id'],
                'name' => $c['name'],
                'scopes' => $c['scopes'],
                'scope_labels' => array_map(fn (string $s): string => OAuthScopes::catalog()[$s] ?? $s, $c['scopes']),
                'authorized_at' => $c['authorized_at'],
            ], app(OAuthConnectionService::class)->connectionsFor($user)),
            // the dialog a refused form came from, opened again with its error inside it (`_form`, set by each form)
            'errored' => is_string($errored) ? $errored : null,
            'erroredDevice' => $errored === 'revoke' && is_string(old('device_id')) ? (string) old('device_id') : null,
            'erroredApp' => $errored === 'app-revoke' && is_string(old('client_id')) ? ['id' => (string) old('client_id'), 'name' => 'this app'] : null,
            'pkErrored' => SecurityOverview::erroredPasskey($errored, $overview['passkeys']),
        ]);
    }
}
