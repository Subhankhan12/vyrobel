<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\SettingsActionController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Fee;
use App\Models\ManagementAgreement;
use App\Models\Organization;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\Property;
use App\Models\PropertyPolicy;
use App\Models\Reservation;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Services\SettingsService;
use App\Support\Billing\SubscriptionPricing;
use App\Support\Branding\PropertyBrand;
use App\Support\Folio\StayEstimator;
use App\Support\Money\Money;
use App\Support\Operator\OperatorWorkspace;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator PROPERTY SETTINGS (task #98) — the per-property settings hub, matched to the decoded
 * `Operator Property Settings.html`: basics · policies · tax & fees summary · owner · property status ·
 * who-can-access · guest-facing brand (+ the detailed taxes/fees editor + remove-property in GATE 2).
 *
 * READ view; every write is a guarded POST → {@see SettingsActionController} → certified {@see SettingsService}
 * (never a toast-only fake save). Context-scoped to the CURRENT property (Phase 6a `PropertyContext` via
 * {@see ResolvesCurrentProperty}) — NOT a `{property}` route param (that would break the Livewire re-render
 * contract). Honesty (#39): only backed columns are editable; the owner commission is the REAL
 * `ManagementAgreement.commission_pct` (18% in the demo, never the wireframe's hardcoded 15%); the guest-brand
 * live preview renders the SAME shared email layout the real send uses; the self-check-in door code is
 * GUEST-only (#33) and never shown here.
 *
 * TENANT-scoped (Property / PropertyPolicy / Tax / Fee / Owner are BelongsToOrganization); `/app` behind
 * EnsureBackOfficeAccess (#26); the route carries `can:properties.manage`.
 */
#[Layout('components.layouts.operator')]
class OperatorSettings extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    public function render(): View
    {
        $property = $this->currentProperty();
        $policy = $property?->policies()->first();
        $organization = $property !== null ? Organization::query()->find($property->organization_id) : null;

        return view('livewire.operator.operator-settings', [
            'viewer' => $this->viewer(),
            'subtitle' => $property !== null ? (string) $property->name : 'No property configured',
            'hasProperty' => $property !== null,
            'canManageProperty' => (bool) auth()->user()?->can('properties.manage'),
            'canManageOrg' => (bool) auth()->user()?->can('settings.manage'),
            'canManageUsers' => (bool) auth()->user()?->can('users.manage'),
            'readOnly' => app(OperatorWorkspace::class)->isReadOnly(),
            'org' => $organization !== null ? $this->orgForm($organization) : [],
            'property' => $property !== null ? $this->propertyForm($property) : [],
            'timezones' => timezone_identifiers_list(),
            'policy' => $this->policyForm($policy),
            'taxes' => $property !== null ? $this->taxes((string) $property->id) : [],
            'fees' => $property !== null ? $this->fees((string) $property->id) : [],
            'owner' => $property !== null ? $this->ownerCard($property) : null,
            'team' => $property !== null ? $this->team($property) : [],
            'pendingInvites' => $property !== null ? $this->pendingInvites() : [],
            'roleDefinitions' => self::roleDefinitions(),
            'brand' => $property !== null ? $this->brandForm($property) : null,
            'brandPreview' => $property !== null ? PropertyBrand::for($property) : PropertyBrand::default(),
            'sample' => $property !== null ? $this->sampleStay($property) : null,
            'removal' => $property !== null ? $this->removalState($property) : null,
            // #104b GATE 2 — the Settings-hub additions, all on the ONE store / real reuses.
            'invoice' => $organization !== null ? $this->invoiceForm($organization) : [],
            'plan' => $this->planCard($organization),
        ]);
    }

    /**
     * #104b GATE 2 — the invoice-details CONFIG (legal entity / VAT number / billing city / series prefix / next
     * number / auto-issue). Real fields; the certified issuance + SAF-T export are honestly deferred in the view.
     *
     * @return array<string, mixed>
     */
    private function invoiceForm(Organization $organization): array
    {
        return [
            'legal_entity' => (string) ($organization->billing_company ?? ''),
            'vat_number' => (string) ($organization->billing_vat_id ?? ''),
            'billing_city' => (string) ($organization->billing_city ?? ''),
            'series_prefix' => (string) ($organization->invoice_series_prefix ?? ''),
            'next_number' => (int) ($organization->invoice_next_number ?? 1),
            'auto_issue' => (bool) $organization->invoice_auto_issue,
        ];
    }

    /**
     * #104b GATE 2 — the "Your plan" card, REUSING the #85 billing state (SubscriptionStatus + the real per-unit
     * pricing). €99 is a configurable placeholder (honest, from config/billing.php), not fabricated market pricing.
     *
     * @return array{tier: string, status: string, price: string, manage_url: string}|null
     */
    private function planCard(?Organization $organization): ?array
    {
        if ($organization === null) {
            return null;
        }

        $status = $organization->billingStatus();
        $pricing = SubscriptionPricing::current('monthly');

        // The REAL plan name off the org's current subscription (the SAME source /app/billing reads) — never a
        // hardcoded tier, so the two operator surfaces can't disagree (#39). "Vyrobel " is the branded prefix.
        $planName = $organization->currentSubscription()?->plan?->name;

        return [
            'tier' => $planName !== null ? 'Vyrobel '.$planName : 'No plan',
            'status' => (string) $status->label(),
            'price' => $this->money((int) $pricing['gross_minor'], (string) config('billing.default_currency', 'EUR')).'/mo',
            'manage_url' => route('app.billing'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function propertyForm(Property $property): array
    {
        $address = is_array($property->address) ? $property->address : [];

        return [
            'name' => (string) $property->name,
            'timezone' => (string) $property->timezone,
            'star_rating' => $property->star_rating,
            'check_in_time' => $this->timeValue($property->check_in_time),
            'check_out_time' => $this->timeValue($property->check_out_time),
            'status' => (string) $property->status,
            'address_line' => (string) ($address['line1'] ?? $address['line'] ?? ''),
            'city' => (string) ($address['city'] ?? ''),
            'country' => (string) ($address['country'] ?? ''),
            'postal_code' => (string) ($address['postal_code'] ?? ''),
            'bookings_paused' => $property->bookings_paused_at !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function policyForm(?PropertyPolicy $policy): array
    {
        $cancellation = is_array($policy?->cancellation) ? $policy->cancellation : [];
        $deposit = is_array($policy?->deposit) ? $policy->deposit : [];

        return [
            'pet' => (string) ($policy->pet ?? 'not_allowed'),
            'smoking' => (string) ($policy->smoking ?? 'not_allowed'),
            'min_stay' => $policy->min_stay ?? 1,
            'max_stay' => $policy->max_stay ?? null,
            // `type` is the stored key. `tier` is read as a fallback ONLY to recover rows written by the
            // earlier code path, which replaced the whole column with `['tier' => …]` and dropped the rest.
            'cancellation_type' => (string) ($cancellation['type'] ?? $cancellation['tier'] ?? 'flexible'),
            // The value the free-cancellation promise actually hangs on. null → the field renders empty and
            // the property genuinely has no free-cancel window; never defaulted to a number nobody chose.
            'free_until_days' => isset($cancellation['free_until_days']) && is_numeric($cancellation['free_until_days'])
                ? (int) $cancellation['free_until_days']
                : null,
            'deposit_required' => (bool) ($deposit['required'] ?? false),
            'self_check_in' => (bool) ($policy->self_check_in ?? false),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orgForm(Organization $organization): array
    {
        return [
            'default_currency' => (string) $organization->default_currency,
            'default_locale' => (string) $organization->default_locale,
            'billing_email' => (string) ($organization->billing_email ?? ''),
        ];
    }

    /**
     * The linked OWNER + REAL commission (task #98). No `property_owner` FK exists — a property's owner(s) are
     * the distinct owners of its units (`Unit.owner_id`); the headline is the owner with the most units here.
     * The commission is the REAL `ManagementAgreement.commission_pct` in effect today (18% in the demo), NEVER
     * the wireframe's hardcoded 15%; unset → "Not set", never "0%".
     *
     * @return array{name: string, initials: string, units: int, commission: string, id: string}|null
     */
    private function ownerCard(Property $property): ?array
    {
        $ownerUnits = Unit::query()
            ->where('property_id', $property->id)
            ->whereNotNull('owner_id')
            ->get(['id', 'owner_id']);

        if ($ownerUnits->isEmpty()) {
            return null;
        }

        $byOwner = $ownerUnits->groupBy('owner_id');
        $primaryOwnerId = (string) $byOwner->sortByDesc(fn (Collection $g): int => $g->count())->keys()->first();
        $owner = Owner::query()->find($primaryOwnerId);
        if ($owner === null) {
            return null;
        }

        return [
            'name' => (string) $owner->name,
            'initials' => $this->initials((string) $owner->name),
            'units' => $byOwner->get($primaryOwnerId)?->count() ?? 0,
            'commission' => $this->commissionLabel($primaryOwnerId),
            'id' => $primaryOwnerId,
        ];
    }

    /** The owner-wide agreement commission in effect today, via the CERTIFIED effectiveOn scope. */
    private function commissionLabel(string $ownerId): string
    {
        /** @var Collection<int, ManagementAgreement> $current */
        $current = ManagementAgreement::query()
            ->where('owner_id', $ownerId)
            ->effectiveOn(Carbon::today())
            ->get();

        $primary = $current->first(fn (ManagementAgreement $a): bool => $a->unit_id === null) ?? $current->first();

        if (! $primary instanceof ManagementAgreement || $primary->commission_pct === null) {
            return 'Not set';
        }

        return rtrim(rtrim((string) $primary->commission_pct, '0'), '.').'% commission';
    }

    /**
     * The operator team who can access THIS property (task #98). A member can access it if they hold an
     * all-access admin role (`properties.manage`), have NO property_user pivot rows (full-org, pivot-or-all
     * rule), or are pivoted to this property. Scope label follows the same logic. Name-only.
     *
     * @return list<array{id: string, name: string, initials: string, roles: string, scope: string, you: bool}>
     */
    private function team(Property $property): array
    {
        $propertyId = (string) $property->id;
        $meId = (string) (auth()->id() ?? '');

        /** @var Collection<int, User> $users */
        $users = User::query()
            ->whereHas('memberships', fn ($q) => $q->where('organization_id', $property->organization_id))
            ->with('propertyAssignments:id,user_id,property_id')
            ->orderBy('name')
            ->get();

        return $users->map(function (User $u) use ($propertyId, $meId): ?array {
            $roles = $u->getRoleNames()->all();
            $backOffice = array_values(array_filter($roles, fn (string $r): bool => $r !== 'External Owner' && $r !== 'Guest'));
            if ($backOffice === []) {
                return null;   // owners / guests are not operator team
            }

            $pivotIds = $u->propertyAssignments->pluck('property_id')->map(fn ($id): string => (string) $id);
            $allAccess = $u->can('properties.manage');

            $canAccess = $allAccess || $pivotIds->isEmpty() || $pivotIds->contains($propertyId);
            if (! $canAccess) {
                return null;
            }

            $scope = $pivotIds->contains($propertyId) && ! $allAccess ? 'This property' : 'All properties';

            return [
                'id' => (string) $u->getKey(),
                'name' => (string) $u->name,
                'initials' => $this->initials((string) $u->name),
                'roles' => implode(' · ', $backOffice),
                'scope' => $scope,
                'you' => (string) $u->getKey() === $meId,
            ];
        })->filter()->values()->all();
    }

    /**
     * Pending operator-team invites for the org (task #98). Reuses the real OwnerInvite store; staff invites
     * carry a manager/accountant role. Email + role + expiry only (no PII beyond the invited address).
     *
     * @return list<array{email: string, role: string, expires: string, sent: string}>
     */
    private function pendingInvites(): array
    {
        return OwnerInvite::query()
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (OwnerInvite $i): array => [
                'email' => (string) $i->email,
                'role' => ucfirst((string) $i->role),
                'expires' => $i->expires_at !== null ? Carbon::parse($i->expires_at)->diffForHumans() : '—',
                'sent' => $i->created_at !== null ? Carbon::parse($i->created_at)->diffForHumans() : '—',
            ])->all();
    }

    /**
     * The REAL role tiers (task #98) — the wireframe's Admin / Front desk / Housekeeping explainers mapped to
     * the actual `config/authorization.php` roles + accurate capability prose. NEVER a fabricated catalogue;
     * "View full permissions" links to the real roles page.
     *
     * @return list<array{name: string, blurb: string}>
     */
    private static function roleDefinitions(): array
    {
        return [
            ['name' => 'Admin', 'blurb' => 'Everything — settings, rates, finance, team. Set at group level (Group Admin / Property Owner), applies to all properties.'],
            ['name' => 'Front desk', 'blurb' => 'Reservations, calendar, folios, guests & messages — no settings or owner finance.'],
            ['name' => 'Housekeeping', 'blurb' => 'The housekeeping board only — room statuses and their own assignments.'],
        ];
    }

    /**
     * The guest-facing BRAND form values (task #98). The from ADDRESS is the fixed verified sender (read-only,
     * Postmark-safe); the editable pieces are accent / logo / from-name / reply-to / powered-by.
     *
     * @return array<string, mixed>
     */
    private function brandForm(Property $property): array
    {
        return [
            'accent' => (string) ($property->brand_accent ?? PropertyBrand::DEFAULT_ACCENT),
            'logo_url' => PropertyBrand::for($property)->logoUrl,
            'from_name' => (string) ($property->brand_email_from_name ?? ''),
            'reply_to' => (string) ($property->brand_reply_to ?? ''),
            'powered_by' => (bool) ($property->brand_powered_by ?? true),
            'from_address' => (string) config('mail.from.address'),
        ];
    }

    /**
     * The sample 4-night stay (task #98 GATE 2) — Room €150 × 4, 2 guests, via the SHARED {@see StayEstimator}
     * (the SAME rules + compute functions a REAL folio uses), so it reconciles to the cent (#17). Amounts are
     * formatted here for the view.
     *
     * @return array{lines: list<array{label: string, detail: string, amount: string, informational: bool}>, total: string, currency: string}
     */
    private function sampleStay(Property $property): array
    {
        $currency = (string) ($property->currency ?: 'EUR');
        $room = Money::of(60000, $currency);   // €150 × 4 nights (the wireframe's illustrative sample)

        $taxes = Tax::query()->where('property_id', $property->id)->orderBy('name')->get();
        $fees = Fee::query()->where('property_id', $property->id)->orderBy('name')->get();
        $estimate = StayEstimator::estimate($taxes, $fees, $room, 4, 2, $currency);

        return [
            'lines' => array_map(fn (array $l): array => [
                'label' => $l['label'],
                'detail' => $l['detail'],
                'amount' => $this->money($l['amount']->amountMinor(), $currency),
                'informational' => $l['informational'],
            ], $estimate['lines']),
            'total' => $this->money($estimate['total']->amountMinor(), $currency),
            'currency' => $currency,
        ];
    }

    /**
     * The property removal state (task #98 GATE 2 · archive-not-destroy) — whether a removal is scheduled, its
     * effective date, and how many guests are in house right now (name-free count).
     *
     * @return array{scheduled: bool, effective: string, in_house: int, name: string, units: int}
     */
    private function removalState(Property $property): array
    {
        $today = Carbon::today()->toDateString();
        $inHouse = Reservation::query()
            ->where('property_id', $property->id)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->whereDate('check_in', '<=', $today)
            ->whereDate('check_out', '>', $today)
            ->count();

        return [
            'scheduled' => $property->removal_scheduled_at !== null,
            'effective' => $property->removal_effective_at?->format('j M Y') ?? '',
            'in_house' => $inHouse,
            'name' => (string) $property->name,
            'units' => Unit::query()->where('property_id', $property->id)->count(),
        ];
    }

    /**
     * Configured taxes — REAL. Rate/amount shown as the tax actually stores them (the detailed editor is GATE 2).
     *
     * @return list<array{id: string, name: string, kind: string, basis: string}>
     */
    private function taxes(string $propertyId): array
    {
        return Tax::query()->where('property_id', $propertyId)->orderBy('name')->get()->map(fn (Tax $tax): array => [
            'id' => (string) $tax->id,
            'name' => (string) $tax->name,
            'kind' => ucfirst(str_replace('_', ' ', (string) $tax->kind)),
            'mode' => (string) $tax->mode,
            'inclusive' => (bool) $tax->inclusive,
            'percentage' => (string) $tax->mode === 'percentage',
            'rate' => rtrim(rtrim((string) $tax->rate, '0'), '.'),
            'amount' => number_format((int) $tax->amount_minor / 100, 2, '.', ''),
            'currency' => (string) $tax->currency,
            'basis' => (string) $tax->mode === 'percentage'
                ? rtrim(rtrim((string) $tax->rate, '0'), '.').'%'
                : $this->money((int) $tax->amount_minor, (string) $tax->currency),
        ])->all();
    }

    /**
     * @return list<array{id: string, name: string, basis: string}>
     */
    private function fees(string $propertyId): array
    {
        return Fee::query()->where('property_id', $propertyId)->orderBy('name')->get()->map(fn (Fee $fee): array => [
            'id' => (string) $fee->id,
            'name' => (string) $fee->name,
            'optional' => (bool) $fee->optional,
            'percentage' => (string) $fee->mode === 'percentage',
            'rate' => rtrim(rtrim((string) $fee->rate, '0'), '.'),
            'amount' => number_format((int) $fee->amount_minor / 100, 2, '.', ''),
            'currency' => (string) $fee->currency,
            'basis' => (string) $fee->mode === 'percentage'
                ? rtrim(rtrim((string) $fee->rate, '0'), '.').'%'
                : $this->money((int) $fee->amount_minor, (string) $fee->currency),
        ])->all();
    }

    /** Initials from a display name (first + last word). */
    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts));
        if ($parts === []) {
            return '—';
        }
        $first = mb_substr($parts[0], 0, 1);
        $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';

        return mb_strtoupper($first.$last);
    }

    /** A `time` column ("15:00:00") as an H:i value for a <input type="time">, or '' when null. */
    private function timeValue(mixed $time): string
    {
        return is_string($time) && strlen($time) >= 5 ? substr($time, 0, 5) : '';
    }
}
