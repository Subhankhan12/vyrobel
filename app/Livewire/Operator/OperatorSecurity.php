<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator SECURITY (Auth · Wave 4 follow-on) — a lean account-security page whose ONE real feature is
 * change-password. It lives here, not on {@see OperatorSettings}, because `/app/settings` is gated
 * `can:properties.manage` (managers only) whereas EVERY back-office user must be able to change their own
 * password — so this page is behind `auth` + EnsureBackOfficeAccess (#26) and NO fine-grained permission.
 *
 * The write is the guarded POST → {@see ChangePasswordController} (the user is
 * resolved from `auth()`, the current password is verified, the hash+rotate is the shared reset path).
 *
 * Honesty (invariant #19): the design's 2FA, active-sessions, trusted-devices, connected-apps and IP-rules
 * panels are unbacked subsystems (docs/AUTH-INVENTORY.md) — shown here as an honest "Soon", never faked.
 */
#[Layout('components.layouts.operator')]
class OperatorSecurity extends Component
{
    use FormatsOperatorData;

    public function render(): View
    {
        return view('livewire.operator.operator-security', [
            'viewer' => $this->viewer(),
        ]);
    }
}
