<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Livewire\Operator\OperatorSecurity;
use App\Livewire\Owner\OwnerSettings;
use App\Models\User;
use App\Support\TwoFactor\TwoFactorService;

/**
 * Builds the shared two-factor panel view-data for the operator ({@see OperatorSecurity})
 * and owner ({@see OwnerSettings}) security screens — one panel, rendered by
 * `resources/views/livewire/partials/two-factor-panel.blade.php`, so the two portals cannot drift.
 *
 * The 2FA state is derived from the SELF user (auth): `disabled` (no secret), `pending` (a secret generated
 * but not yet confirmed — shows the QR + confirm form), or `enabled` (confirmed — the source of truth
 * {@see User::hasTwoFactorEnabled()}). Recovery codes appear only when just flashed by the controller (shown
 * exactly once). The route prefix differs per portal (`app.security.2fa` vs `owner.settings.2fa`).
 */
trait PreparesTwoFactor
{
    /**
     * @return array<string, mixed>
     */
    protected function twoFactorPanel(string $routePrefix): array
    {
        /** @var User $user */
        $user = auth()->user();
        $service = app(TwoFactorService::class);

        $enabled = $user->hasTwoFactorEnabled();
        $pending = ! $enabled && $user->two_factor_secret !== null;

        return [
            'state' => $enabled ? 'enabled' : ($pending ? 'pending' : 'disabled'),
            'qr_svg' => $pending ? $service->qrCodeSvg($user) : null,
            'secret' => $pending ? $service->secretKey($user) : null,
            // Flashed by TwoFactorController on confirm/regenerate — rendered ONCE, never stored in plaintext.
            'recovery_codes' => session('two_factor_recovery_codes'),
            'routes' => [
                'enable' => route($routePrefix.'.enable'),
                'confirm' => route($routePrefix.'.confirm'),
                'regenerate' => route($routePrefix.'.regenerate'),
                'disable' => route($routePrefix.'.disable'),
            ],
        ];
    }
}
