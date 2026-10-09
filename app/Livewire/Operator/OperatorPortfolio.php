<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Channel;
use App\Models\Organization;
use App\Models\Property;
use App\Services\FrontDeskService;
use App\Services\PropertyRemovalService;
use App\Support\Analytics\PerformanceMetrics;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Property\PropertyContext;
use App\Support\Property\PropertyOccupancy;
use App\Support\Tenancy\TenantContext;
use App\Support\Time\TimezoneOptions;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Operator PORTFOLIO (Phase 6a · Step 2) — the multi-property overview: an all-properties KPI strip + a
 * card per property (real unit count, derived status, real occupancy tonight), and the "Add property"
 * entry point. The overview always spans the user's FULL accessible set (the property_user allow-list, or
 * the whole org when unpivoted) — independent of the topbar switcher's current selection.
 *
 * #96 exact-match: the KPI strip now carries Revenue·month + Needs-attention (real derived), the cards carry
 * per-property ADR + room revenue (this month), and an HONEST Fix-sync affordance (the REAL `channels.status`
 * — "Sync needs attention"/"Channels connected"/"No channels connected", NEVER a fabricated "synced N min ago",
 * #85/#39). All figures REAL/DERIVED: occupancy from the Availability spine, arrivals from the front-desk read,
 * ADR/RevPAR/revenue via the SG1 {@see PerformanceMetrics} (Group = Σ properties, reconciling #17), and a status
 * DERIVED from real inventory ("Finish setup to go live" when a property has no units yet).
 *
 * TENANT-scoped (Property is BelongsToOrganization); `/app` behind EnsureBackOfficeAccess (#26); the route
 * carries `can:properties.view`. The Add/Edit affordances render only for `properties.manage` so the UI
 * never offers a write the route would 403.
 */
#[Layout('components.layouts.operator')]
class OperatorPortfolio extends Component
{
    use FormatsOperatorData;

    /** The user's accessible property ids (pivot-or-all), captured at mount — the overview's scope. */
    #[Locked]
    public array $accessibleIds = [];

    #[Locked]
    public string $currentPropertyId = '';

    public function mount(): void
    {
        $ctx = app(PropertyContext::class);
        $this->accessibleIds = $ctx->accessibleIds();
        $this->currentPropertyId = (string) ($ctx->id() ?? '');
    }

    public function render(FrontDeskService $desk): View
    {
        $today = CarbonImmutable::today()->toDateString();
        $monthStart = CarbonImmutable::today()->startOfMonth();
        $monthEnd = $monthStart->addMonth();

        /** @var Collection<int, Property> $properties */
        $properties = $this->accessibleIds === []
            ? collect()
            : Property::query()
                ->whereIn('id', $this->accessibleIds)
                ->withCount(['units', 'roomTypes'])
                ->orderBy('name')
                ->get();

        // Real per-property channel status (#93/#85 honest — the fix-sync affordance reads the real
        // `channels.status`/`last_synced_at`, NEVER a fabricated "synced"). One batched read.
        $channelByProperty = Channel::query()
            ->whereIn('property_id', $properties->pluck('id'))
            ->get()
            ->groupBy('property_id');

        $needsAttention = 0;
        $revenueMonthMinor = 0;
        // ⚠️ F-44 (FIX 46) — NOTHING IS IN SCOPE TO DERIVE FROM when this collection is empty: there is
        //    no owner here and no property either. ⚠️ So the literal becomes the ONE product default
        //    (`config('billing.default_currency')`) rather than a hidden assumption repeated per file.
        //    ⚠️ Allow-listed by `CurrencyNeverDefaultsTest` WITH THIS REASON — see that test.
        $revenueCurrency = (string) ($properties->first()->currency ?? config('billing.default_currency', 'EUR'));

        $removals = app(PropertyRemovalService::class);
        $cards = $properties->map(function (Property $property) use ($desk, $today, $monthStart, $monthEnd, $channelByProperty, $removals, &$needsAttention, &$revenueMonthMinor): array {
            $occ = PropertyOccupancy::forProperties([(string) $property->id], $today);
            $unitCount = (int) $property->units_count;
            $roomTypeCount = (int) $property->room_types_count;
            $currency = (string) ($property->currency ?? 'EUR');
            $m = PerformanceMetrics::forProperties([(string) $property->id], $monthStart, $monthEnd, $currency);
            $revenueMonthMinor += $m->roomRevenueMinor;

            $status = $this->statusFor($property, $unitCount, $roomTypeCount);
            $sync = $this->channelStatus($channelByProperty->get($property->id));
            if ($status['setup'] || $sync['needs_fix']) {
                $needsAttention++;
            }

            return [
                'id' => (string) $property->id,
                'name' => (string) $property->name,
                'initial' => mb_strtoupper(mb_substr((string) $property->name, 0, 1)),
                'location' => $this->locationLabel($property),
                'type_label' => $this->typeLabel((string) $property->type),
                'units' => $unitCount,
                'room_types' => $roomTypeCount,
                'occupancy_pct' => $occ['pct'],
                // ⚠️ The DESIGN's own rule for a property with nothing to divide by: the prototype's
                // freshly-added property renders `adr: '—'` (and `occ/revpar: '—'` in the table) while
                // revenue stays a real €0. An average over ZERO room-nights is UNDEFINED, not zero — so
                // it shows "—". A genuinely computed zero (revenue, occupancy over real inventory) stays.
                'occupancy_display' => $occ['total'] > 0 ? $occ['pct'].'%' : '—',
                'rooms_sold' => $occ['sold'],
                'rooms_total' => $occ['total'],
                'adr' => $m->roomNightsSold > 0 ? $this->money($m->adrMinor(), $currency) : '—',
                'revenue' => $this->money($m->roomRevenueMinor, $currency),
                'arrivals' => $desk->arrivals((string) $property->id, $today)->count(),
                'status' => $status,
                'sync' => $sync,
                'is_current' => (string) $property->id === $this->currentPropertyId,
                'timezone' => (string) $property->timezone,
                'type' => (string) $property->type,
                'removal' => $this->removalFor($property, $removals),
            ];
        })->all();

        $totalUnits = (int) $properties->sum('units_count');
        $aggregate = PropertyOccupancy::forProperties($this->accessibleIds, $today);
        $arrivalsToday = collect($cards)->sum('arrivals');

        return view('livewire.operator.portfolio', [
            'viewer' => $this->viewer(),
            'canManage' => (bool) auth()->user()?->can('properties.manage'),
            // ⚠️ DO-BILL-2 — the Add-property modal's BILLING-CONSEQUENCE copy is keyed by the plan's SHAPE.
            // It used to assert "billed per unit" unconditionally, which is false on a FLAT tier: adding a
            // property (and its units) to Essential/Pro changes the bill by nothing. The demo org is flat, so
            // the claim was live-false on the default demo — the same §N.8d billing pass 3 retired on
            // /app/billing, one page over (§N.17.4: this claim RECURS across surfaces).
            //
            // Resolved from the SAME source pass 3 reads — the org's current subscription's plan — so there is
            // no parallel notion of "what shape are we on" to drift (§N.8f). Null-safe on purpose: an org with
            // no plan row makes no billing claim at all, which is the honest rendering.
            'planIsPerUnit' => (bool) Organization::query()
                ->find(app(TenantContext::class)->id())?->currentSubscription()?->plan?->isPerUnit(),
            'cards' => $cards,
            'summary' => [
                'properties' => $properties->count(),
                'units' => $totalUnits,
                'occupancy_pct' => $aggregate['pct'],
                'rooms_sold' => $aggregate['sold'],
                'rooms_total' => $aggregate['total'],
                'arrivals' => $arrivalsToday,
                'revenue_month' => $this->money($revenueMonthMinor, $revenueCurrency),
                'needs_attention' => $needsAttention,
            ],
            'comparison' => $this->comparison($properties),
            'comparisonYear' => CarbonImmutable::today()->format('Y'),
            'canViewAnalytics' => (bool) auth()->user()?->can('reports.view'),
            'readOnly' => app(OperatorWorkspace::class)->isReadOnly(),
            // 2026-09-29 — the Add/Edit dialogs' fields: every time zone with its offset now, and the three types as tiles
            // The same rich list as the profile's (2026-10-10, the app-wide select): city first, region groups, live clocks.
            'timezones' => TimezoneOptions::choices(),
            'typeChoices' => [
                ['value' => 'hotel', 'label' => 'Hotel', 'icon' => '<path d="M4 21V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16"/><path d="M2 21h20M9 7h1M14 7h1M9 11h1M14 11h1M10 21v-4h4v4"/>'],
                ['value' => 'villa', 'label' => 'Villas & apartments', 'icon' => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-5h4v5"/>'],
                ['value' => 'guest_house', 'label' => 'Guesthouse / lodge', 'icon' => '<path d="M3 18V7M3 14h18v4M21 14v-2a3 3 0 0 0-3-3h-7v5"/><circle cx="7" cy="11" r="2"/>'],
            ],
        ]);
    }

    /**
     * ⚠️ 2026-09-29 — what REMOVING this property would do now, for its card and its confirmation. Read from
     * {@see PropertyRemovalService::outlook()} — the SAME read the remove action acts on, so the card can never promise
     * "right away" while the action schedules, or the reverse.
     *
     * @return array{scheduled: bool, leaves_on: string|null, stays: int, in_house: int, last_checkout: string|null, clear: bool, last_remaining: bool}
     */
    private function removalFor(Property $property, PropertyRemovalService $removals): array
    {
        $o = $removals->outlook($property);

        return [
            'scheduled' => $property->removal_scheduled_at !== null,
            'leaves_on' => $property->removal_effective_at?->format('j M Y'),
            'stays' => $o['stays'],
            'in_house' => $o['in_house'],
            'last_checkout' => $o['last_checkout']?->format('j M Y'),
            'clear' => $o['clear'],
            'last_remaining' => $o['last_remaining'],
        ];
    }

    /**
     * The property's REAL channel status (#85 honest) — read from `channels.status`, NEVER fabricated. Live
     * two-way sync is account-gated, so `last_synced_at` is null and this never claims "synced N min ago".
     *
     * @param  Collection<int, Channel>|null  $channels
     * @return array{label: string, tone: string, needs_fix: bool}
     */
    private function channelStatus($channels): array
    {
        if ($channels === null || $channels->isEmpty()) {
            return ['label' => 'No channels connected', 'tone' => 'muted', 'needs_fix' => false];
        }
        if ($channels->contains(fn (Channel $c): bool => (string) $c->status === 'error')) {
            return ['label' => 'Sync needs attention', 'tone' => 'warn', 'needs_fix' => true];
        }
        if ($channels->contains(fn (Channel $c): bool => (string) $c->status === 'connected')) {
            return ['label' => 'Channels connected', 'tone' => 'ok', 'needs_fix' => false];
        }

        return ['label' => 'Channels disconnected', 'tone' => 'muted', 'needs_fix' => false];
    }

    /**
     * The side-by-side ADR / RevPAR / revenue comparison (deferred from Phase 6a, real in 6b) — one row per
     * property + a group row, all REAL via the SG1 {@see PerformanceMetrics} for the current year. A
     * property with no data shows honest zeros; nothing is fabricated.
     *
     * @param  Collection<int, Property>  $properties
     * @return array{rows: list<array{name: string, initial: string, units: int, occupancy_pct: string, adr: string, revpar: string, revenue: string}>, total: array{occupancy_pct: string, adr: string, revpar: string, revenue: string}}
     */
    private function comparison($properties): array
    {
        // #96 — the side-by-side comparison spans the operating YEAR (the reconciling multi-period basis the
        // Insights page + the certified analytics use; the KPI/cards above are current-month per the wireframe).
        // Same PerformanceMetrics for a row [$id] and the Group ($accessibleIds) → Group = Σ reconciles (#17).
        $start = CarbonImmutable::today()->startOfYear();
        $end = $start->addYear();

        $rows = $properties->map(function (Property $property) use ($start, $end): array {
            $currency = (string) ($property->currency ?? 'EUR');
            $m = PerformanceMetrics::forProperties([(string) $property->id], $start, $end, $currency);

            return [
                'name' => (string) $property->name,
                'initial' => mb_strtoupper(mb_substr((string) $property->name, 0, 1)),
                'units' => (int) $property->units_count,
                // Same undefined-vs-zero rule as the cards (the design's `—`).
                'occupancy_pct' => $m->availableRoomNights > 0 ? $m->occupancyPct().'%' : '—',
                'adr' => $m->roomNightsSold > 0 ? $this->money($m->adrMinor(), $currency) : '—',
                'revpar' => $m->availableRoomNights > 0 ? $this->money($m->revparMinor(), $currency) : '—',
                'revenue' => $this->money($m->roomRevenueMinor, $currency),
            ];
        })->all();

        // ⚠️ F-44 (FIX 46) — NOTHING IS IN SCOPE TO DERIVE FROM when this collection is empty: there is
        //    no owner here and no property either. ⚠️ So the literal becomes the ONE product default
        //    (`config('billing.default_currency')`) rather than a hidden assumption repeated per file.
        //    ⚠️ Allow-listed by `CurrencyNeverDefaultsTest` WITH THIS REASON — see that test.
        $currency = (string) ($properties->first()->currency ?? config('billing.default_currency', 'EUR'));
        $group = PerformanceMetrics::forProperties($this->accessibleIds, $start, $end, $currency);

        return [
            'rows' => $rows,
            'total' => [
                'occupancy_pct' => $group->availableRoomNights > 0 ? $group->occupancyPct().'%' : '—',
                'adr' => $group->roomNightsSold > 0 ? $this->money($group->adrMinor(), $currency) : '—',
                'revpar' => $group->availableRoomNights > 0 ? $this->money($group->revparMinor(), $currency) : '—',
                'revenue' => $this->money($group->roomRevenueMinor, $currency),
            ],
        ];
    }

    /**
     * The card's status — DERIVED from real state, never a faked label:
     *   • inactive property                → "Inactive"
     *   • active but no room types/units    → "Finish setup to go live" (not yet operational)
     *   • active with inventory             → "Active"
     *
     * @return array{label: string, tone: string, setup: bool}
     */
    private function statusFor(Property $property, int $unitCount, int $roomTypeCount): array
    {
        if ((string) $property->status === 'inactive') {
            return ['label' => 'Inactive', 'tone' => 'muted', 'setup' => false];
        }

        if ($roomTypeCount === 0 || $unitCount === 0) {
            return ['label' => 'Finish setup to go live', 'tone' => 'warn', 'setup' => true];
        }

        return ['label' => 'Active', 'tone' => 'ok', 'setup' => false];
    }

    /** A human label for the real `properties.type` enum (warm, no invented value). */
    private function typeLabel(string $type): string
    {
        return match ($type) {
            'hotel' => 'Hotel',
            'aparthotel' => 'Aparthotel',
            'serviced_apartment' => 'Serviced apartment',
            'apartment_building' => 'Apartment building',
            'apartment' => 'Apartment',
            'villa' => 'Villas & apartments',
            'hostel' => 'Hostel',
            'guest_house' => 'Guesthouse / lodge',
            'resort' => 'Resort',
            'mixed' => 'Mixed',
            'portfolio' => 'Portfolio',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    private function locationLabel(Property $property): string
    {
        $address = $property->address;
        $city = is_array($address) && isset($address['city']) ? trim((string) $address['city']) : '';

        return $city !== '' ? $city : (string) $property->timezone;
    }
}
