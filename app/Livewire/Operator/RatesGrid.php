<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\RateActionController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Availability;
use App\Models\Channel;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\Restriction;
use App\Models\RoomType;
use App\Services\RateAndRestrictionService;
use App\Services\RateDerivationService;
use App\Support\Money\Money;
use App\Support\Operator\OperatorWorkspace;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator RATES & AVAILABILITY grid (Wave 4 · Sub-gate 1) — a room-type × date calendar of nightly rates,
 * availability and stay-rules, styled on the shared operator design system via <x-operator.shell>. This
 * component READS ONLY; every write goes through the guarded POST → {@see RateActionController}
 * → {@see RateAndRestrictionService} (invariant #20 — a scope-changing action rides a GET
 * link or a POST form, never a wire-action that would bypass the tenant middleware).
 *
 * ⚠️ PER-ROOM-TYPE, NOT PER-UNIT (docs/WAVE4-INVENTORY.md STRUCT-1). `rate_calendar` is keyed on
 * `rate_plan_id`, and a rate plan belongs to a `room_type`; the design's per-physical-unit grid cannot be
 * backed without silently editing every unit under a room type. Each row here is a ROOM TYPE, priced through
 * its PRIMARY rate plan (`default_rate_plan_id`, else the oldest active plan). The sub-label states how many
 * physical units sit under it, so the operator is never misled about what a save touches.
 *
 * ⚠️ TENANT-scoped. RoomType / RateCalendar / Availability / Restriction are all BelongsToOrganization, so a
 * foreign org's rows are not in the collection at all — no explicit org predicate appears here (adding one
 * would imply the scope were optional). `/app` additionally sits behind EnsureBackOfficeAccess (#26), and
 * this route carries `can:rates.view` (#30). Proven by OperatorRatesTest.
 *
 * Honesty triage (invariant #19; standing order = OMIT what has no backend):
 *   • REAL     — the nightly rate (`rate_calendar.price`, a Money cast over `price_minor`+`currency`);
 *                availability (`availability.total/sold/out_of_order/blocked`, available computed not stored);
 *                the stay-rules badges (`restrictions.min_stay`, `restrictions.stop_sell` — BOTH are values
 *                the service actually writes, so no dead-enum badge can render).
 *   • DERIVED  — the three stat tiles: average rate PER CURRENCY (never summed across — invariant #17),
 *                booked % (Σsold / Σtotal over the window) and open nights (Σavailable). Each is a real
 *                aggregate over the rows on screen, and its label states its own scope.
 *   • OMITTED (no backend — absent, not faked):
 *       – SMART PRICING / suggestions (a sin() demand curve in the design — no pricing engine exists).
 *       – The "+8% vs Aug" / "+9% vs last year" TRENDS (no historical snapshot table exists anywhere, so a
 *         baseline cannot be computed — the Wave-1 fabricated-trend rule).
 *       – The CHANNEL-SYNC ribbon + per-channel status pills (Synced/Syncing/Queued). `channels.status` is
 *         written by NO application code (only a test factory), so every one of those states is a dead enum;
 *         the whole distribution surface is omitted rather than shown as live. See the view's "Soon" note.
 *       – The rate-plan "All channels / Direct only" pills (no rate_plan↔channel model exists).
 *   • SOON     — "Push to channels" (the ARI push is real but the live Channex rail is an account-gated
 *                checkpoint) and "propagate to derived plans" (no derived plans are configured). Both are
 *                rendered as an honest disabled note, never a button that fires nothing.
 *
 * "Today" is CarbonImmutable::today() — SERVER time, matching FrontDesk / ReservationStage. The property
 * timezone convention is not yet honoured by any of the three; when fixed, fix them together.
 */
#[Layout('components.layouts.operator')]
class RatesGrid extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    /** The grid shows a two-week window — enough to plan a fortnight without an unreadable wall of columns. */
    private const WINDOW_DAYS = 14;

    public function render(): View
    {
        $from = $this->resolveFrom();
        $dates = $this->windowDates($from);
        $lastDate = end($dates);

        // Phase 6a · multi-property: scope the grid to the current property (or every accessible
        // property when "all" is selected). Pre-6a this read ALL room types org-wide — which silently
        // BLENDED properties once an org ran more than one.
        $allRoomTypes = RoomType::query()
            ->whereIn('property_id', $this->currentReadIds)
            ->with(['defaultRatePlan', 'ratePlans' => fn ($q) => $q->where('is_active', true)->orderBy('created_at')])
            ->withCount('units')
            ->orderBy('name')
            ->get();

        // #104 GATE 2d — a read-only room-type filter (the wireframe's segment/unit dropdown), driven by ?rt=
        // exactly like the ?from= window nav. The OPTIONS stay the full accessible set; only the GRID narrows.
        // A tampered/unknown id falls through to "all" (the room type must be in the accessible set).
        $rtFilter = (string) request()->query('rt', '');
        $roomTypes = ($rtFilter !== '' && $allRoomTypes->contains(fn (RoomType $rt): bool => (string) $rt->getKey() === $rtFilter))
            ? $allRoomTypes->filter(fn (RoomType $rt): bool => (string) $rt->getKey() === $rtFilter)->values()
            : $allRoomTypes;

        // Resolve each room type's primary plan once; collect the plan ids so the calendar/restriction/
        // availability reads are three batched queries, not an N+1 across the grid.
        $primaryPlan = [];
        foreach ($roomTypes as $roomType) {
            $primaryPlan[(string) $roomType->getKey()] = $this->primaryRatePlan($roomType);
        }

        $planIds = array_values(array_filter(array_map(
            fn (?RatePlan $plan): ?string => $plan?->getKey() !== null ? (string) $plan->getKey() : null,
            $primaryPlan,
        )));
        $roomTypeIds = $roomTypes->map(fn (RoomType $rt): string => (string) $rt->getKey())->all();

        $fromStr = $from->toDateString();
        $throughStr = CarbonImmutable::parse($lastDate)->toDateString();

        $rates = $this->indexRates($planIds, $fromStr, $throughStr);
        $availability = $this->indexAvailability($roomTypeIds, $fromStr, $throughStr);
        $restrictions = $this->indexRestrictions($roomTypeIds, $fromStr, $throughStr);

        $rows = $roomTypes->map(fn (RoomType $rt): array => $this->row(
            $rt,
            $primaryPlan[(string) $rt->getKey()],
            $dates,
            $rates,
            $availability,
            $restrictions,
        ))->all();

        return view('livewire.operator.rates-grid', [
            'viewer' => $this->viewer(),
            'subtitle' => $this->subtitle($roomTypes->count()),
            'canManage' => (bool) auth()->user()?->can('rates.manage'),
            'dates' => array_map(fn (string $d): array => $this->dateHeader($d), $dates),
            'rows' => $rows,
            // Options are the FULL accessible set (so the filter dropdown offers every room type), even when the
            // grid itself is narrowed to one by ?rt=.
            'roomTypeOptions' => $allRoomTypes->map(fn (RoomType $rt): array => [
                'id' => (string) $rt->getKey(),
                'name' => (string) $rt->name,
                'has_plan' => $this->primaryRatePlan($rt) !== null,
            ])->all(),
            'rtFilter' => $rtFilter,
            'tiles' => $this->tiles($rates, $availability),
            'window' => $this->windowNav($from, $fromStr, $throughStr),
            'formDefaults' => [
                'from' => $fromStr,
                'to' => $fromStr,
            ],
            'ratePlans' => $this->ratePlanList($roomTypeIds),
            'channels' => $this->channelList(),
            'pacing' => $this->pacing($availability),
            'readOnly' => app(OperatorWorkspace::class)->isReadOnly(),
        ]);
    }

    /**
     * The room types' rate plans — the REAL base + derived plans (task #100). A derived plan shows its
     * adjustment (−10% from base) + refundability. Channel scoping is HONEST: only Direct is a live channel
     * (#85), so no fabricated "All channels / Direct only" pill — the real distribution is Direct.
     *
     * @param  list<string>  $roomTypeIds
     * @return list<array{name: string, sub: string, refundable: bool, is_derived: bool, channel: string}>
     */
    private function ratePlanList(array $roomTypeIds): array
    {
        if ($roomTypeIds === []) {
            return [];
        }

        return RatePlan::query()
            ->whereIn('room_type_id', $roomTypeIds)
            ->where('is_active', true)
            // D18-6 — the plan's ROOM TYPE, eager-loaded so the list can say WHICH type each plan prices.
            ->with(['derivedFrom:id,name', 'roomType:id,name'])
            ->orderBy('derived_from_rate_plan_id')   // base plans (null) first
            ->orderBy('created_at')
            ->get()
            ->map(function (RatePlan $plan): array {
                $pct = RateDerivationService::adjustmentPct($plan);
                $sub = $pct !== null
                    ? rtrim(rtrim(number_format($pct, 2), '0'), '.').'% · derived from '.((string) ($plan->derivedFrom->name ?? 'base'))
                    : ($plan->is_refundable ? 'Free cancellation · the grid above' : 'Base plan');

                return [
                    'name' => (string) $plan->name,
                    // ⚠️ D18-6 — WHICH room type this plan prices. A rate plan belongs to a room type, and
                    // several types routinely carry a plan of the same name: the demo has THREE active
                    // "Flexible" plans (Double Room, One-Bedroom Apartment, City Studio). Without this the
                    // list printed the same name and sub-line for each, so an operator could not tell which
                    // one they were reading — on the screen that prices rooms.
                    'room_type' => (string) ($plan->roomType->name ?? ''),
                    'sub' => $sub,
                    'refundable' => (bool) $plan->is_refundable,
                    'is_derived' => $pct !== null,
                    'channel' => 'Direct',   // honest — the only live channel (#85)
                ];
            })->all();
    }

    /**
     * HONEST channel list (#85) — Direct is live; any real `channels` rows show their REAL status; live OTA
     * sync is account-gated, so nothing here fabricates a "Synced". The push affordance is honest ("needs the
     * channel setup").
     *
     * @return array{summary: string, rows: list<array{init: string, name: string, status: string, live: bool}>}
     */
    private function channelList(): array
    {
        $rows = [[
            'init' => 'D',
            'name' => 'Direct booking',
            'status' => 'Live',
            'live' => true,
        ]];

        foreach (Channel::query()->whereIn('property_id', $this->currentReadIds)->orderBy('name')->get() as $channel) {
            $status = (string) $channel->status;
            $rows[] = [
                'init' => mb_strtoupper(mb_substr((string) $channel->name, 0, 1)),
                'name' => (string) $channel->name,
                'status' => match ($status) {
                    'connected' => 'Connected',
                    'error' => 'Needs attention',
                    'disabled' => 'Disabled',
                    default => 'Not connected',
                },
                'live' => false,   // no live two-way sync until the OTA rail is set up (#85)
            ];
        }

        $connected = collect($rows)->filter(fn (array $r): bool => $r['live'])->count();

        return [
            'summary' => $connected.' live · OTA sync needs setup',
            'rows' => $rows,
        ];
    }

    /**
     * Pacing — a REAL derived signal over the window (booked % + open nights). NO fabricated "+9% vs last year"
     * trend (no prior-year snapshot exists). An honest, real read of on-the-books.
     *
     * @param  array<string, array{total: int, sold: int, available: int}>  $availability
     * @return array{booked_pct: ?int, open_nights: int, insight: string}
     */
    private function pacing(array $availability): array
    {
        $sold = 0;
        $total = 0;
        $open = 0;
        foreach ($availability as $cell) {
            $sold += $cell['sold'];
            $total += $cell['total'];
            $open += $cell['available'];
        }
        $pct = $total > 0 ? (int) round($sold / $total * 100) : null;

        $insight = $pct === null
            ? 'No inventory published in this window yet.'
            : ($pct >= 70 ? 'Filling fast — review your open nights.' : ($pct >= 30 ? 'Steady on-the-books for the next two weeks.' : $open.' open nights — consider a promotion.'));

        return ['booked_pct' => $pct, 'open_nights' => $open, 'insight' => $insight];
    }

    // ── Request state ────────────────────────────────────────────────────────────────────────────

    /** The window start: a valid `?from=` date, else today (server). */
    private function resolveFrom(): CarbonImmutable
    {
        $param = request()->query('from');

        if (is_string($param) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $param) === 1) {
            [$y, $m, $d] = array_map('intval', explode('-', $param));
            if (checkdate($m, $d, $y)) {
                return CarbonImmutable::parse($param)->startOfDay();
            }
        }

        return CarbonImmutable::today();
    }

    /**
     * The window's dates as Y-m-d strings.
     *
     * @return list<string>
     */
    private function windowDates(CarbonImmutable $from): array
    {
        $dates = [];
        for ($i = 0; $i < self::WINDOW_DAYS; $i++) {
            $dates[] = $from->addDays($i)->toDateString();
        }

        return $dates;
    }

    // ── Batched reads, indexed for O(1) cell lookup ──────────────────────────────────────────────

    /**
     * @param  list<string>  $planIds
     * @return array<string, array{minor: int, currency: string}> keyed "{plan_id}|{date}"
     */
    private function indexRates(array $planIds, string $from, string $through): array
    {
        if ($planIds === []) {
            return [];
        }

        $index = [];
        foreach (RateCalendar::query()
            ->whereIn('rate_plan_id', $planIds)
            ->whereBetween('date', [$from, $through])
            ->get() as $row) {
            $key = $row->rate_plan_id.'|'.CarbonImmutable::parse($row->date)->toDateString();
            $index[$key] = ['minor' => (int) $row->price_minor, 'currency' => (string) $row->currency];
        }

        return $index;
    }

    /**
     * @param  list<string>  $roomTypeIds
     * @return array<string, array{total: int, sold: int, available: int}> keyed "{room_type_id}|{date}"
     */
    private function indexAvailability(array $roomTypeIds, string $from, string $through): array
    {
        if ($roomTypeIds === []) {
            return [];
        }

        $index = [];
        foreach (Availability::query()
            ->whereIn('room_type_id', $roomTypeIds)
            ->whereBetween('date', [$from, $through])
            ->get() as $row) {
            $available = max(0, (int) $row->total - (int) $row->sold - (int) $row->out_of_order - (int) $row->blocked);
            $key = $row->room_type_id.'|'.CarbonImmutable::parse($row->date)->toDateString();
            $index[$key] = ['total' => (int) $row->total, 'sold' => (int) $row->sold, 'available' => $available];
        }

        return $index;
    }

    /**
     * Room-type-wide restrictions only (rate_plan_id IS NULL) — the grain this grid writes at, so a badge
     * always reflects exactly what a save from this screen set.
     *
     * @param  list<string>  $roomTypeIds
     * @return array<string, array{min_stay: ?int, stop_sell: bool}> keyed "{room_type_id}|{date}"
     */
    private function indexRestrictions(array $roomTypeIds, string $from, string $through): array
    {
        if ($roomTypeIds === []) {
            return [];
        }

        $index = [];
        foreach (Restriction::query()
            ->whereIn('room_type_id', $roomTypeIds)
            ->whereNull('rate_plan_id')
            ->whereBetween('date', [$from, $through])
            ->get() as $row) {
            $key = $row->room_type_id.'|'.CarbonImmutable::parse($row->date)->toDateString();
            $index[$key] = [
                'min_stay' => $row->min_stay !== null ? (int) $row->min_stay : null,
                'stop_sell' => (bool) $row->stop_sell,
            ];
        }

        return $index;
    }

    // ── Row assembly ─────────────────────────────────────────────────────────────────────────────

    /**
     * @param  list<string>  $dates
     * @param  array<string, array{minor: int, currency: string}>  $rates
     * @param  array<string, array{total: int, sold: int, available: int}>  $availability
     * @param  array<string, array{min_stay: ?int, stop_sell: bool}>  $restrictions
     * @return array<string, mixed>
     */
    private function row(RoomType $roomType, ?RatePlan $plan, array $dates, array $rates, array $availability, array $restrictions): array
    {
        $rtId = (string) $roomType->getKey();
        $planId = $plan?->getKey() !== null ? (string) $plan->getKey() : null;
        $units = (int) ($roomType->units_count ?? 0);

        $cells = [];
        foreach ($dates as $date) {
            $rate = $planId !== null ? ($rates[$planId.'|'.$date] ?? null) : null;
            $avail = $availability[$rtId.'|'.$date] ?? null;
            $restriction = $restrictions[$rtId.'|'.$date] ?? null;

            $cells[] = [
                'date' => $date,
                'rate' => $rate !== null ? $this->money($rate['minor'], $rate['currency']) : null,
                // The raw decimal (e.g. "175.00") for the editor prefill — derived from integer minor units
                // via the Money value object, never a float division. The server re-parses authoritatively.
                'rate_input' => $rate !== null ? Money::of($rate['minor'], $rate['currency'])->toDecimalString() : '',
                'available' => $avail['available'] ?? null,
                'sold' => $avail['sold'] ?? null,
                'total' => $avail['total'] ?? null,
                'sold_out' => $avail !== null && $avail['available'] === 0,
                'min_stay' => $restriction['min_stay'] ?? null,
                'stop_sell' => $restriction['stop_sell'] ?? false,
            ];
        }

        return [
            'id' => $rtId,
            'name' => (string) $roomType->name,
            'code' => (string) ($roomType->code ?? ''),
            'plan_name' => $plan !== null ? (string) $plan->name : null,
            'has_plan' => $planId !== null,
            'units_label' => $units.' '.($units === 1 ? 'unit' : 'units'),
            'cells' => $cells,
        ];
    }

    /**
     * @return array{weekday: string, day: string, month: string}
     */
    private function dateHeader(string $date): array
    {
        $carbon = CarbonImmutable::parse($date);

        return [
            'date' => $date,
            'weekday' => $carbon->format('D'),
            'day' => $carbon->format('j'),
            'month' => $carbon->format('M'),
            'is_weekend' => $carbon->isWeekend(),
        ];
    }

    // ── Stat tiles — DERIVED, real aggregates over the window ─────────────────────────────────────

    /**
     * Average nightly rate PER CURRENCY, booked %, and open nights across the window on screen.
     *
     * @param  array<string, array{minor: int, currency: string}>  $rates
     * @param  array<string, array{total: int, sold: int, available: int}>  $availability
     * @return array{avg: list<string>, multi_currency: bool, booked_pct: ?int, open_nights: int}
     */
    private function tiles(array $rates, array $availability): array
    {
        // Average rate: sum + count per currency, then integer-divide. Never a cross-currency sum (#17).
        $sumByCurrency = [];
        $countByCurrency = [];
        foreach ($rates as $rate) {
            $currency = $rate['currency'];
            $sumByCurrency[$currency] = ($sumByCurrency[$currency] ?? 0) + $rate['minor'];
            $countByCurrency[$currency] = ($countByCurrency[$currency] ?? 0) + 1;
        }

        ksort($sumByCurrency);
        $avg = [];
        foreach ($sumByCurrency as $currency => $sum) {
            $avg[] = $this->money(intdiv($sum, $countByCurrency[$currency]), $currency);
        }

        // Occupancy + open nights: real availability, summed across the whole window.
        $sold = 0;
        $total = 0;
        $open = 0;
        foreach ($availability as $cell) {
            $sold += $cell['sold'];
            $total += $cell['total'];
            $open += $cell['available'];
        }

        return [
            'avg' => $avg,
            'multi_currency' => count($avg) > 1,
            'booked_pct' => $total > 0 ? (int) round($sold / $total * 100) : null,
            'open_nights' => $open,
        ];
    }

    // ── Window navigation ────────────────────────────────────────────────────────────────────────

    /**
     * @return array{label: string, prev: string, next: string, today: string, jump: ?array{date: string, href: string}}
     */
    private function windowNav(CarbonImmutable $from, string $fromStr, string $throughStr): array
    {
        $through = CarbonImmutable::parse($throughStr);

        // If nothing is priced in this window, offer a one-click jump to the next date that IS priced — the
        // demo's rates live in a future month, and an operator landing on an empty fortnight should be told
        // where their real rates are, not left guessing.
        $jump = null;
        $earliest = RateCalendar::query()
            ->whereDate('date', '>=', $fromStr)
            ->orderBy('date')
            ->value('date');
        if ($earliest !== null) {
            $earliestStr = CarbonImmutable::parse($earliest)->toDateString();
            if ($earliestStr > $throughStr) {
                $jump = ['date' => $earliestStr, 'href' => route('app.rates', ['from' => $earliestStr])];
            }
        }

        return [
            'label' => $from->format('j M').' – '.$through->format('j M Y'),
            'prev' => route('app.rates', ['from' => $from->subDays(self::WINDOW_DAYS)->toDateString()]),
            'next' => route('app.rates', ['from' => $from->addDays(self::WINDOW_DAYS)->toDateString()]),
            'today' => route('app.rates', ['from' => CarbonImmutable::today()->toDateString()]),
            'jump' => $jump,
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────────────────────

    private function primaryRatePlan(RoomType $roomType): ?RatePlan
    {
        if ($roomType->default_rate_plan_id !== null && $roomType->defaultRatePlan !== null) {
            return $roomType->defaultRatePlan;
        }

        return $roomType->ratePlans->first();
    }

    private function subtitle(int $count): string
    {
        return $count.' '.($count === 1 ? 'room type' : 'room types');
    }
}
