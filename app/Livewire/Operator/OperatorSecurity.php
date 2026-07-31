<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Livewire\Concerns\PreparesTwoFactor;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Support\Security\ActiveSessions;
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
        return view('livewire.operator.operator-security', [
            'viewer' => $this->viewer(),
            'twoFactor' => $this->twoFactorPanel('app.security.2fa'),
            // #104 GATE 3b — the REAL active-sessions panel (the SAME ActiveSessions the Profile page ships; it
            // was honestly "Soon" here while live one page over). Real `sessions` rows, current device flagged.
            'sessions' => ActiveSessions::forUser((string) (auth()->id() ?? '')),
        ]);
    }
}
