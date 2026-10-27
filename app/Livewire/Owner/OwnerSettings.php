<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Controllers\Owner\OwnerAccountController;
use App\Http\Controllers\Owner\OwnerProfileController;
use App\Http\Controllers\Owner\OwnerTaxController;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\OwnerUser;
use App\Models\PayoutMethod;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Support\I18n\Locales;
use App\Support\Owner\OwnerCommissionRate;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerNextPayout;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner SETTINGS (exact-match pass · invariant #62) — the settings HUB, rebuilt to the decoded "Owner Settings"
 * wireframe on the owner design system via <x-owner.shell>. A HUB: MOST sections wire to features ALREADY BUILT
 * and are REUSED (anti-drift), never rebuilt. READ view; every write is a guarded controller POST (never a
 * Livewire action) so route middleware always runs (the /livewire/update boundary lesson).
 *
 * TWO identities, deliberately separated (unchanged from the #46 account rebuild):
 *   • MY PROFILE (per-USER, auth()-resolved) — name / login-email / language / avatar — via
 *     {@see OwnerAccountController}; works for accountant / view-only SUB-USERS too.
 *   • OWNER / BUSINESS DETAILS (the party paid out to) — 🔒 PRIMARY-OWNER-ONLY (#42) — via
 *     {@see OwnerProfileController} + the ONE {@see OwnerTaxController} `owner.tax.save`
 *     path (anti-drift — the same store the Payout Method + remittance PDF use).
 *
 * HONESTY MAP (see docs/OWNER-SETTINGS-AUDIT.md): billing = the REAL management fee (the real agreement rate via
 * {@see OwnerCommissionRate}, never the wireframe's 15%) + the real statement PDFs, NO owner card (the owner has
 * no Vyrobel subscription — that's the operator's); the masked payout IBAN is CAP_PAYOUT-only (#28). The hero
 * stats + completeness checklist are DERIVED from the real state — nothing fabricated (#39).
 */
#[Layout('components.layouts.owner')]
class OwnerSettings extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $user = auth()->user();
        $ownerCtx = app(OwnerContext::class);
        $isPrimaryOwner = $ownerCtx->isPrimaryOwner();
        $canPayout = $ownerCtx->can(OwnerTeamRole::CAP_PAYOUT);

        $ownerParty = $isPrimaryOwner
            ? Owner::query()->where('user_id', $user->getKey())->first()
            : null;

        $rail = OwnerNextPayout::rail($ownerCtx);          // iban_masked (CAP_PAYOUT), paused (tax-hold)
        $hasPayoutMethod = PayoutMethod::query()->exists(); // owner-scoped
        $hasTaxId = $ownerParty instanceof Owner && $ownerParty->hasTaxDetails();
        $feePercent = OwnerCommissionRate::percent($ownerCtx);

        $completeness = $this->completeness($user, $ownerParty, $hasTaxId, $hasPayoutMethod);

        return view('livewire.owner.owner-settings', [
            'viewer' => $this->viewer(),
            'isPrimaryOwner' => $isPrimaryOwner,
            'canPayout' => $canPayout,
            'account' => [
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'pending_email' => (string) ($user->pending_email ?? ''),
                // The "Verified" pill must read the VERIFICATION, not the absence of a pending change — those
                // are different facts, and every owner-realm path happens to verify on accept today, so the
                // badge was truthful by accident rather than by construction.
                'email_verified' => $user->email_verified_at !== null,
                'locale' => (string) ($user->locale ?: 'en'),
                'avatar_url' => $user->avatarUrl(),
                'initials' => $this->viewer()['initials'],
                'two_factor' => $user->two_factor_secret !== null,
            ],
            'localeOptions' => Locales::offeredOptions(),
            'ownerParty' => $ownerParty !== null ? [
                'name' => (string) $ownerParty->name,
                'email' => (string) ($ownerParty->email ?? ''),
                'phone' => (string) ($ownerParty->phone ?? ''),
                'type' => (string) $ownerParty->type,
                'legal_name' => (string) ($ownerParty->legal_name ?? ''),
                'tax_country' => (string) ($ownerParty->tax_country ?? ''),
                'vat_number' => (string) ($ownerParty->vat_number ?? ''),
                // The owner's registered address (GATE 3) — the statement letterhead recipient block.
                'address_street' => (string) (is_array($ownerParty->address) ? ($ownerParty->address['street'] ?? '') : ''),
                'address_city' => (string) (is_array($ownerParty->address) ? ($ownerParty->address['city'] ?? '') : ''),
                'address_postcode' => (string) (is_array($ownerParty->address) ? ($ownerParty->address['postcode'] ?? '') : ''),
                'address_country' => (string) (is_array($ownerParty->address) ? ($ownerParty->address['country'] ?? '') : ''),
                'has_tax_id' => $hasTaxId,
            ] : null,
            // Billing = the REAL management fee (never an owner card / subscription — the owner has neither).
            'fee' => [
                'percent' => $feePercent,   // null when ambiguous → the view shows the fee without a rate label
                'invoices_count' => OwnerStatement::query()->whereIn('status', ['finalized', 'paid'])->count(),
            ],
            'payout' => [
                'iban_masked' => $rail['iban_masked'],   // CAP_PAYOUT-only (#28); null otherwise
                'has_method' => $hasPayoutMethod,
            ],
            'hero' => [
                'completeness' => $completeness['percent'],
                'channels_live' => 1,   // Direct / Vyrobel is the one always-on live channel (OTA sync is gated)
                'team' => $this->teamCount($ownerCtx),
                'invoices' => OwnerStatement::query()->whereIn('status', ['finalized', 'paid'])->count(),
            ],
            'completeness' => $completeness['items'],
            // ── GATE 2 (preferences & access) ──
            'region' => $this->region(),
            'notif' => [
                // The REAL notification channels are in-app + email; SMS + Push are NOT built (no provider) →
                // labelled honest-pending, never a fake toggle that notifies via an unwired channel (#39).
                'real' => [__('php/livewire/owner/owner_settings.notif_in_app'), __('php/livewire/owner/owner_settings.notif_email')],
                'pending' => [__('php/livewire/owner/owner_settings.notif_sms'), __('php/livewire/owner/owner_settings.notif_push')],
            ],
            'channels' => [
                // Direct is the ONE real, always-on channel. OTA sync is ACCOUNT-GATED (Channex stub) → the
                // rows are honest-labelled "pending", NEVER a fake "Connected" that syncs nothing (#37/#39).
                ['name' => __('php/livewire/owner/owner_settings.channel_direct'), 'desc' => __('php/livewire/owner/owner_settings.channel_direct_desc'), 'status' => 'live'],
                ['name' => 'Airbnb', 'desc' => __('php/livewire/owner/owner_settings.channel_ota_desc'), 'status' => 'pending'],
                ['name' => 'Booking.com', 'desc' => __('php/livewire/owner/owner_settings.channel_ota_desc'), 'status' => 'pending'],
                ['name' => 'Vrbo', 'desc' => __('php/livewire/owner/owner_settings.channel_ota_desc'), 'status' => 'pending'],
            ],
            'team' => [
                'count' => $this->teamCount($ownerCtx),
                'members' => $this->teamMembers($ownerCtx, $user),
                'can_manage' => $isPrimaryOwner,
            ],
            'exports' => $canPayout || $ownerCtx->can(OwnerTeamRole::CAP_EXPORTS) ? [
                ['label' => __('php/livewire/owner/owner_settings.export_bookings'), 'href' => route('owner.bookings.export')],
                ['label' => __('php/livewire/owner/owner_settings.export_statement_history'), 'href' => route('owner.statements.history.csv')],
                ['label' => __('php/livewire/owner/owner_settings.export_full_account'), 'href' => route('owner.security.export')],
            ] : [],
        ]);
    }

    /**
     * Language & region — the ONE real, per-user editable field is `locale` (saved via owner.account.update).
     * Currency + timezone are the PROPERTY's real values (read-only here — set by the operator, not per-owner);
     * date format follows the locale. Nothing is a fake writable control (#39).
     *
     * @return list<array{label: string, value: string, editable: bool}>
     */
    private function region(): array
    {
        /** @var ?Unit $unit */
        // ⚠️ ALSO A REAL BUG FIX. This derives the owner's currency/timezone from their first unit's
        // PROPERTY, and an archived property resolves to null — so an owner whose alphabetically-first unit
        // sat at a removed property was shown the hardcoded 'EUR' / 'Europe/Lisbon' fallback instead of their
        // own live property's settings.
        $unit = app(OwnerPortalService::class)->unitsAtLiveProperties()->first();
        $property = $unit?->property;

        return [
            ['label' => __('php/livewire/owner/owner_settings.region_currency'), 'value' => $property !== null ? (string) $property->currency : 'EUR', 'editable' => false],
            ['label' => __('php/livewire/owner/owner_settings.region_timezone'), 'value' => $property !== null ? (string) $property->timezone : 'Europe/Lisbon', 'editable' => false],
        ];
    }

    /**
     * A read-only summary of the owner team (active sub-users + the primary owner) for the Settings hub — the
     * full invite/role management lives on the dedicated {@see OwnerTeam} page (reused, anti-drift). Name-only.
     *
     * @return list<array{initials: string, name: string, role: string, you: bool}>
     */
    private function teamMembers(OwnerContext $ownerCtx, mixed $user): array
    {
        if (! $ownerCtx->active() || $ownerCtx->ownerIds() === []) {
            return [];
        }

        $out = [[
            'initials' => $this->viewer()['initials'],
            'name' => (string) $user->name,
            'role' => __('php/livewire/owner/owner_settings.role_account_owner'),
            'you' => true,
        ]];

        OwnerUser::query()
            ->whereIn('owner_id', $ownerCtx->ownerIds())
            ->where('status', 'active')
            ->with('user')
            ->get()
            ->each(function (OwnerUser $m) use (&$out, $user): void {
                $name = (string) $m->user->name;   // active members always have a user (with('user') loaded)
                $out[] = [
                    'initials' => mb_strtoupper(mb_substr($name, 0, 1)),
                    'name' => $name,
                    'role' => OwnerTeamRole::label((string) $m->role),
                    'you' => (string) $m->user->getKey() === (string) $user->getKey(),
                ];
            });

        return $out;
    }

    /**
     * The profile-completeness checklist — DERIVED from the REAL account/business/payout state (never a
     * fabricated score). Each item is a real, checkable fact; the percent is done ÷ total.
     *
     * @return array{percent: int, items: list<array{label: string, done: bool, href: ?string}>}
     */
    private function completeness(mixed $user, ?Owner $ownerParty, bool $hasTaxId, bool $hasPayoutMethod): array
    {
        $items = [
            ['label' => __('php/livewire/owner/owner_settings.check_profile_photo'), 'done' => $user->avatarUrl() !== null, 'href' => null],
            ['label' => __('php/livewire/owner/owner_settings.check_login_email'), 'done' => (string) ($user->pending_email ?? '') === '', 'href' => null],
        ];

        // Business/tax + payout are the primary owner's to complete; a sub-user can't, so they aren't scored on them.
        if ($ownerParty instanceof Owner) {
            $items[] = ['label' => __('php/livewire/owner/owner_settings.check_tax_details'), 'done' => $hasTaxId, 'href' => route('owner.tax')];
            $items[] = ['label' => __('php/livewire/owner/owner_settings.check_payout_account'), 'done' => $hasPayoutMethod, 'href' => route('owner.payout-method')];
        }

        $items[] = ['label' => __('php/livewire/owner/owner_settings.check_two_factor'), 'done' => $user->two_factor_secret !== null, 'href' => route('owner.security')];

        $done = count(array_filter($items, fn (array $i): bool => $i['done']));
        $total = count($items);   // always ≥ 3 (the base items), so no division guard is needed

        return ['percent' => (int) round($done / $total * 100), 'items' => $items];
    }

    /** The owner team size — active sub-users ({@see OwnerUser}) + the primary owner, for the ids the context holds. */
    private function teamCount(OwnerContext $ownerCtx): int
    {
        if (! $ownerCtx->active() || $ownerCtx->ownerIds() === []) {
            return 0;
        }

        $subUsers = OwnerUser::query()
            ->whereIn('owner_id', $ownerCtx->ownerIds())
            ->where('status', 'active')
            ->count();

        return $subUsers + 1;   // + the primary owner themselves
    }
}
