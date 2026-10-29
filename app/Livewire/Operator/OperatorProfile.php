<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\OperatorProfileController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Property;
use App\Models\User;
use App\Support\Authorization\RoleLabel;
use App\Support\I18n\Locales;
use App\Support\Property\PropertyContext;
use App\Support\Security\ActiveSessions;
use App\Support\Time\TimezoneOptions;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator PROFILE (#96 exact-match) — the logged-in operator's OWN account: personal details + UI preferences
 * (editable, self-only POST → {@see OperatorProfileController}, audited), a READ-ONLY display of their REAL role
 * capabilities (spatie — never a fabricated permission list), and a Sign-in & security section that REUSES the
 * #66 features (password + 2FA link to the Security page) plus REAL session revocation (the shared
 * {@see ActiveSessions} helper + sign-out-of-all-other-devices). Behind `auth` + #26 (every back-office user
 * edits their own profile, like `/app/security`); personal settings stay editable (no pause gate).
 */
#[Layout('components.layouts.operator')]
class OperatorProfile extends Component
{
    use FormatsOperatorData;

    /**
     * The REAL capabilities to summarise as "Key permissions" — read from the actual role via spatie can(). Their
     * labels are translated at render time ({@see capLabel()}), never in this constant.
     */
    private const KEY_CAPS = [
        'reservations.create',
        'payments.record',
        'payouts.manage',
        'messages.manage',
        'reports.view',
        'users.manage',
    ];

    /** A key capability's label, in the request's language. */
    private function capLabel(string $perm): string
    {
        return match ($perm) {
            'reservations.create' => __('php/livewire/operator/operator_profile.cap_reservations'),
            'payments.record' => __('php/livewire/operator/operator_profile.cap_payments'),
            'payouts.manage' => __('php/livewire/operator/operator_profile.cap_payouts'),
            'messages.manage' => __('php/livewire/operator/operator_profile.cap_messages'),
            'reports.view' => __('php/livewire/operator/operator_profile.cap_reports'),
            'users.manage' => __('php/livewire/operator/operator_profile.cap_users'),
            default => $perm,
        };
    }

    /** The account status word (`users.status` is active | disabled). */
    private function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => __('php/livewire/operator/operator_profile.status_active'),
            'disabled' => __('php/livewire/operator/operator_profile.status_disabled'),
            default => ucfirst($status),
        };
    }

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $ctx = app(PropertyContext::class);
        $propertyId = $ctx->id();
        $property = $propertyId !== null ? Property::query()->find($propertyId) : null;

        // The REAL role capabilities — a read-only display, never fabricated. Key caps the user actually holds +
        // the full real permission set (spatie), "managed by your workspace admin".
        $keyCaps = collect(self::KEY_CAPS)
            ->map(fn (string $perm): array => ['label' => $this->capLabel($perm), 'granted' => (bool) $user->can($perm)])
            ->all();
        $allPermissions = $user->getAllPermissions()
            ->map(fn ($p): string => (string) $p->name)
            ->filter(fn (string $p): bool => $p !== '*')
            ->sort()
            ->values()
            ->all();

        return view('livewire.operator.operator-profile', [
            'viewer' => $this->viewer(),
            'user' => $user,
            'roles' => $user->getRoleNames()->map(fn ($r): string => RoleLabel::for((string) $r))->implode(' · '),
            'statusLabel' => $this->statusLabel((string) $user->status),
            'memberSince' => $user->created_at?->translatedFormat('M Y'),
            'propertyLabel' => $property !== null ? (string) $property->name : null,
            // Personal details (real columns).
            'personal' => [
                'name' => (string) $user->name,
                'preferred_name' => (string) ($user->preferred_name ?? ''),
                'email' => (string) $user->email,
                'phone' => (string) ($user->phone ?? ''),
                'locale' => (string) ($user->locale ?? 'en'),
                'timezone' => (string) ($user->timezone ?? ($property !== null ? $property->timezone : 'UTC')),
            ],
            // UI preferences (real, applied by the layout + a shared formatter).
            'prefs' => [
                'compact_density' => (bool) $user->compact_density,
                'onboarding_tips' => (bool) ($user->onboarding_tips ?? true),
                'reduce_motion' => (bool) $user->reduce_motion,
                'date_format' => (string) ($user->date_format ?? 'DD/MM/YYYY'),
            ],
            'keyCaps' => $keyCaps,
            'allPermissions' => $allPermissions,
            // Sign-in & security — reuse #66 (password/2FA link to Security) + real sessions.
            'twoFactorEnabled' => $user->hasTwoFactorEnabled(),
            'lastLogin' => $user->last_login_at,
            'sessions' => ActiveSessions::forUser((string) $user->getKey()),
            'localeOptions' => Locales::offeredOptions(),
            // The new select (2026-10-09): each language in its own name, with its name in the reader's language beside it.
            'languageOptions' => Locales::pickerOptions(),
            // ⚠️ Every zone the `timezone` rule accepts — the page used to offer five, so a person elsewhere saw (and could
            //    save) Lisbon in place of their own zone. The saved zone is kept even if the list ever lacks it.
            'timezoneOptions' => TimezoneOptions::choices((string) ($user->timezone ?? ($property !== null ? $property->timezone : 'UTC'))),
        ]);
    }
}
