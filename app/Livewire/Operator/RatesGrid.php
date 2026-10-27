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
use App\Models\RateSeason;
use App\Models\Restriction;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\UnitRateOverride;
use App\Services\RateAndRestrictionService;
use App\Services\RateDerivationService;
use App\Services\SeasonalRateService;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Pricing\NightlyRate;
use App\Support\Rates\SeasonApplyFacts;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
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
        $overriddenUnits = $this->indexOverriddenUnits($planIds, $fromStr, $throughStr);

        $rows = $roomTypes->map(fn (RoomType $rt): array => $this->row(
            $rt,
            $primaryPlan[(string) $rt->getKey()],
            $dates,
            $rates,
            $availability,
            $restrictions,
            $overriddenUnits,
        ))->all();

        return view('livewire.operator.rates-grid', [
            'viewer' => $this->viewer(),
            // ⚠️ The bulk-edit modal's "Set exact …" radio hardcoded '€'. The grid is property-scoped, so the
            // currency the operator is typing in is whatever these plans are priced in — never a literal.
            'currencySymbol' => $this->currencySymbol($this->gridCurrency($rates, $roomTypes)),
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
            // D18-5 — the units behind each room type, so the bulk per-unit panel can offer a real selection.
            // Display only: the grid's own rows and its blast-radius label are untouched.
            'unitOptions' => Unit::query()
                ->whereIn('room_type_id', $allRoomTypes->map(fn (RoomType $rt): string => (string) $rt->getKey())->all())
                ->orderBy('code')->get(['id', 'name', 'code', 'room_type_id'])
                ->groupBy('room_type_id')
                // ⚠️ `name ?: code` — units carry a code and a NULL name, so labelling by name alone renders
                // a picker of blank checkboxes. The mailers already use exactly this fallback.
                ->map(fn ($g): array => $g->map(fn ($u): array => ['id' => (string) $u->id, 'name' => (string) ($u->name ?: $u->code)])->values()->all())
                ->all(),
            'rtFilter' => $rtFilter,
            'tiles' => $this->tiles($rates, $availability),
            // ⚠️ 2026-09-30 — the window's links keep the room-type filter (they dropped it), and "Jump to rates" looks
            //    only at the plans on screen (it searched the whole organisation).
            'window' => $this->windowNav($from, $fromStr, $throughStr, $rtFilter, $planIds),
            // The editor and both bulk dialogs open on the WHOLE window by default. ⚠️ 2026-09-30 — the bulk dialogs were
            //    headed with the fortnight but posted the editor's dates, which started as ONE night: "increase 10%" over
            //    what looked like two weeks changed a single night.
            'formDefaults' => [
                'from' => $fromStr,
                'to' => $throughStr,
            ],
            // The nights ahead, whatever window is on screen — the figure the old "Pacing · next 14 days" card promised
            // and computed over the window instead.
            'ahead' => $this->ahead($roomTypeIds),
            // The REAL derived plans (name + adjustment) — the editor used to print "Non-refundable −10% and Weekly −15%"
            // whatever plans existed.
            'derivedPlans' => $this->derivedPlans($roomTypeIds),
            'ratePlans' => $this->ratePlanList($roomTypeIds, $planIds, $fromStr, $throughStr),
            // ⚠️ 2026-10-01 — the grid prices each room type's MAIN plan; where a room type has more than one base plan the
            //    editor offers the choice, so a plan that can be added can also be priced.
            'planChoices' => $this->planChoices($roomTypes, $primaryPlan),
            'mainPlans' => array_map(fn (?RatePlan $plan): ?string => $plan?->getKey() !== null ? (string) $plan->getKey() : null, $primaryPlan),
            'seasons' => $this->seasonList($roomTypeIds),
            // ⚠️ THE RULE'S OWN SENTENCE, from the rule — so the copy on the page cannot drift from the code. I18N-01: the
            //    English of this key is App\Support\Pricing\SeasonPrecedence::SENTENCE word for word (SeasonPrecedenceTest asserts it on the page).
            'seasonRule' => __('php/livewire/operator/rates_grid.season_rule'),
            'channels' => $this->channelList(),
            'readOnly' => app(OperatorWorkspace::class)->isReadOnly(),
        ]);
    }

    /**
     * The saved seasons for the visible room types, each carrying its REAL blast radius.
     *
     * ⚠️⚠️ EVERY FIGURE COMES FROM {@see SeasonalRateService::preview()}. Nothing here — and nothing in the
     * view — re-counts nights, units or changing nights. A count estimated in Blade would be a second
     * definition of the single number an operator is deciding on (§N.8f), on the screen that sets what a
     * guest is charged.
     *
     * ⚠️ `applied` IS THE TWO-STEP MADE VISIBLE. A season that exists has written nothing; only `applied_at`
     * says its nights carry the price. The panel shows the two states differently on purpose.
     *
     * ⚠️ ONE preview() PER SEASON — a handful of queries for a handful of rows. Deliberately not batched: the
     * figure has to be the one the service itself would compute, not a faster approximation of it.
     *
     * @param  list<string>  $roomTypeIds
     * @return list<array<string, mixed>>
     */
    private function seasonList(array $roomTypeIds): array
    {
        if ($roomTypeIds === []) {
            return [];
        }

        $planIds = RatePlan::query()
            ->whereIn('room_type_id', $roomTypeIds)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id): string => (string) $id)
            ->all();

        if ($planIds === []) {
            return [];
        }

        $seasons = app(SeasonalRateService::class);

        return RateSeason::query()
            ->whereIn('rate_plan_id', $planIds)
            ->with('ratePlan.roomType:id,name')
            ->orderBy('starts_on')
            ->get()
            ->map(function (RateSeason $season) use ($seasons): array {
                $p = $seasons->preview($season);

                // ⚠️ The dialog's figures and its apply address come from ONE builder, shared with the New reservation page
                //    (SeasonApplyFacts — straight from preview(), never recomputed); the rest is this page's own.
                return [
                    // ⚠️ Built from the ROUTE, as the apply address is (SeasonApplyFacts) and for the same reason: a
                    //    hand-assembled path can drift from the definition without anything failing.
                    'update_url' => route('app.seasons.update', $season->getKey()),
                    'delete_url' => route('app.seasons.destroy', $season->getKey()),
                    'plan_name' => (string) ($season->ratePlan->name ?? '—'),
                    'room_type' => (string) ($season->ratePlan->roomType->name ?? ''),
                    'starts_on' => $season->starts_on->toDateString(),
                    'ends_on' => $season->ends_on->toDateString(),
                    'applied' => $season->applied_at !== null,
                    // ⚠️ The edit form's price field needs the MAJOR-unit string the create form accepts, not
                    //    the display string — `MoneyInput::toMinor()` parses this back on the way in.
                    'price_input' => number_format((int) $season->price_minor / 100, 2, '.', ''),
                    'applied_on' => $season->applied_at?->translatedFormat('j M Y'),
                ] + SeasonApplyFacts::of($season, $p);
            })->all();
    }

    /**
     * The room types' rate plans — the REAL base + derived plans (task #100). A derived plan shows its
     * adjustment (−10% from base) + refundability. Channel scoping is HONEST: only Direct is a live channel
     * (#85), so no fabricated "All channels / Direct only" pill — the real distribution is Direct.
     *
     * @param  list<string>  $roomTypeIds
     * @param  list<string>  $mainPlanIds  the plans the grid prices — one per room type on screen
     * @return list<array{name: string, sub: string, refundable: bool, is_derived: bool, channel: string}>
     */
    private function ratePlanList(array $roomTypeIds, array $mainPlanIds, string $from, string $through): array
    {
        if ($roomTypeIds === []) {
            return [];
        }

        // What each plan charges in the fortnight on screen — the grid shows only the MAIN plans, so a second plan's prices
        // are seen here. One grouped read; a plan carries one currency.
        $nights = self::WINDOW_DAYS;
        $priced = [];
        foreach (RateCalendar::query()->whereHas('ratePlan', fn ($q) => $q->whereIn('room_type_id', $roomTypeIds))
            ->whereBetween('date', [$from, $through])
            ->selectRaw('rate_plan_id, currency, MIN(price_minor) AS lo, MAX(price_minor) AS hi, COUNT(*) AS n')
            // `toBase()` — aggregate rows, not models; the tenant scope still applies (Eloquent applies scopes before it hands over)
            ->groupBy('rate_plan_id', 'currency')->toBase()->get() as $row) {
            $priced[(string) $row->rate_plan_id] = ['lo' => (int) $row->lo, 'hi' => (int) $row->hi, 'n' => (int) $row->n, 'currency' => (string) $row->currency];
        }

        return RatePlan::query()
            ->whereIn('room_type_id', $roomTypeIds)
            // ⚠️ OFF-SALE PLANS ARE INCLUDED (F-34 / FIX 15). This used to filter `is_active`, which was
            // right while the card was a read-only list and became a TRAP the moment it grew a control:
            // taking a plan off sale would have removed it from the only screen that could put it back.
            // A one-way door is worse than no door. The row renders its state and the season selector
            // below still offers ACTIVE plans only — a season must never be defined against a dead plan.
            ->with(['derivedFrom:id,name', 'roomType:id,name'])
            ->orderByDesc('is_active')               // on sale first; off-sale sink to the bottom
            ->orderBy('derived_from_rate_plan_id')   // base plans (null) first
            ->orderBy('created_at')
            ->get()
            ->map(function (RatePlan $plan) use ($mainPlanIds, $priced, $nights): array {
                $pct = RateDerivationService::adjustmentPct($plan);
                $isMain = in_array((string) $plan->getKey(), $mainPlanIds, true);
                // ⚠️ "the grid above" only where it is TRUE — the grid prices each room type's main plan and no other
                //    (2026-10-01: every refundable plan said it, and a non-refundable one read "Base plan").
                $pctText = $pct !== null ? rtrim(rtrim(number_format($pct, 2), '0'), '.') : '';
                $fromName = $plan->derivedFrom instanceof RatePlan ? $plan->derivedFrom->name : null;   // null → "base", as before
                $sub = match (true) {
                    $pct !== null && $fromName !== null => __('php/livewire/operator/rates_grid.derived_from', ['pct' => $pctText, 'plan' => (string) $fromName]),
                    $pct !== null => __('php/livewire/operator/rates_grid.derived_from_base', ['pct' => $pctText]),
                    $plan->is_refundable && $isMain => __('php/livewire/operator/rates_grid.free_cancellation_main'),
                    (bool) $plan->is_refundable => __('php/livewire/operator/rates_grid.free_cancellation'),
                    $isMain => __('php/livewire/operator/rates_grid.non_refundable_main'),
                    default => __('php/livewire/operator/rates_grid.non_refundable'),
                };
                $p = $priced[(string) $plan->getKey()] ?? null;
                $pricedLine = $p === null
                    ? __('php/livewire/operator/rates_grid.no_prices_fortnight')
                    : trans_choice('php/livewire/operator/rates_grid.priced_on', $nights, [
                        'priced' => $p['n'],
                        'count' => $nights,
                        'range' => MoneyFormatter::display($p['lo'], $p['currency'])
                            .($p['hi'] !== $p['lo'] ? ' – '.MoneyFormatter::display($p['hi'], $p['currency']) : ''),
                    ]);

                return [
                    // ⚠️ ADDED for the seasonal-pricing panel's plan selector — a season is defined AGAINST a
                    // rate plan, so the form needs the real id. Display of the grid's own rows is untouched.
                    'id' => (string) $plan->getKey(),
                    'name' => (string) $plan->name,
                    // F-34 / FIX 15 — the row's own state, so the card can show it and the season
                    // selector can exclude it. `code` is the second editable field (see updateRatePlan).
                    'is_active' => (bool) $plan->is_active,
                    'code' => (string) ($plan->code ?? ''),
                    // ⚠️ D18-6 — WHICH room type this plan prices. A rate plan belongs to a room type, and
                    // several types routinely carry a plan of the same name: the demo has THREE active
                    // "Flexible" plans (Double Room, One-Bedroom Apartment, City Studio). Without this the
                    // list printed the same name and sub-line for each, so an operator could not tell which
                    // one they were reading — on the screen that prices rooms.
                    'room_type' => (string) ($plan->roomType->name ?? ''),
                    'room_type_id' => (string) $plan->room_type_id,
                    'sub' => $sub,
                    'is_main' => $isMain,
                    'priced' => $pricedLine,
                    'refundable' => (bool) $plan->is_refundable,
                    'is_derived' => $pct !== null,
                    'channel' => __('php/livewire/operator/rates_grid.channel_direct'),   // honest — the only live channel (#85)
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
            'name' => __('php/livewire/operator/rates_grid.direct_booking'),
            'status' => __('php/livewire/operator/rates_grid.status_live'),
            'live' => true,
        ]];

        foreach (Channel::query()->whereIn('property_id', $this->currentReadIds)->orderBy('name')->get() as $channel) {
            $status = (string) $channel->status;
            $rows[] = [
                'init' => mb_strtoupper(mb_substr((string) $channel->name, 0, 1)),
                'name' => (string) $channel->name,
                'status' => match ($status) {
                    'connected' => __('php/livewire/operator/rates_grid.status_connected'),
                    'error' => __('php/livewire/operator/rates_grid.status_needs_attention'),
                    'disabled' => __('php/livewire/operator/rates_grid.status_disabled'),
                    default => __('php/livewire/operator/rates_grid.status_not_connected'),
                },
                'live' => false,   // no live two-way sync until the OTA rail is set up (#85)
            ];
        }

        $connected = collect($rows)->filter(fn (array $r): bool => $r['live'])->count();

        return [
            'summary' => trans_choice('php/livewire/operator/rates_grid.channels_summary', $connected, ['count' => $connected]),
            'rows' => $rows,
        ];
    }

    /**
     * The base plans the editor may price, for each room type on screen that has MORE THAN ONE — the main plan (the one the
     * grid shows) first. A derived plan follows its base, so it is never offered here.
     *
     * @param  Collection<int, RoomType>  $roomTypes
     * @param  array<string, ?RatePlan>  $primaryPlan
     * @return array<string, list<array{id: string, label: string}>>
     */
    private function planChoices(Collection $roomTypes, array $primaryPlan): array
    {
        $choices = [];
        foreach ($roomTypes as $roomType) {
            $rtId = (string) $roomType->getKey();
            $main = $primaryPlan[$rtId]?->getKey();
            $plans = RatePlan::query()->where('room_type_id', $rtId)->whereNull('derived_from_rate_plan_id')
                ->orderByDesc('is_active')->orderBy('created_at')->get()
                ->sortBy(fn (RatePlan $plan): int => (string) $plan->getKey() === (string) $main ? 0 : 1)->values();
            if ($plans->count() < 2) {
                continue;
            }
            $choices[$rtId] = $plans->map(function (RatePlan $plan) use ($main): array {
                $isMain = (string) $plan->getKey() === (string) $main;

                return [
                    'id' => (string) $plan->getKey(),
                    'label' => match (true) {
                        $isMain && ! $plan->is_active => __('php/livewire/operator/rates_grid.plan_choice_main_off_sale', ['plan' => (string) $plan->name]),
                        $isMain => __('php/livewire/operator/rates_grid.plan_choice_main', ['plan' => (string) $plan->name]),
                        ! $plan->is_active => __('php/livewire/operator/rates_grid.plan_choice_off_sale', ['plan' => (string) $plan->name]),
                        default => (string) $plan->name,
                    },
                ];
            })->all();
        }

        return $choices;
    }

    /**
     * The next 14 nights from TODAY — booked % and open nights over the room types on screen, whatever window the grid shows.
     * ⚠️ The old "Pacing · next 14 days" card computed over the WINDOW (so it repeated the figures above it, and on any other
     * fortnight it was not the next 14 days at all). No "vs last year" — no prior-year snapshot exists (#39).
     *
     * @param  list<string>  $roomTypeIds
     * @return array{booked_pct: ?int, open_nights: int}
     */
    private function ahead(array $roomTypeIds): array
    {
        if ($roomTypeIds === []) {
            return ['booked_pct' => null, 'open_nights' => 0];
        }

        $today = CarbonImmutable::today();
        $cells = $this->indexAvailability($roomTypeIds, $today->toDateString(), $today->addDays(self::WINDOW_DAYS - 1)->toDateString());
        [$sold, $total, $open] = [0, 0, 0];
        foreach ($cells as $cell) {
            $sold += $cell['sold'];
            $total += $cell['total'];
            $open += $cell['available'];
        }

        return ['booked_pct' => $total > 0 ? (int) round($sold / $total * 100) : null, 'open_nights' => $open];
    }

    /**
     * The derived plans on screen that follow a base plan's price, each with its real adjustment ({@see RateDerivationService}).
     *
     * @param  list<string>  $roomTypeIds
     * @return list<string> e.g. "Non-refundable −10%"
     */
    private function derivedPlans(array $roomTypeIds): array
    {
        if ($roomTypeIds === []) {
            return [];
        }

        $labels = [];
        foreach (RatePlan::query()->whereIn('room_type_id', $roomTypeIds)->whereNotNull('derived_from_rate_plan_id')
            ->where('is_active', true)->orderBy('name')->get() as $plan) {
            $pct = RateDerivationService::adjustmentPct($plan);
            if ($pct === null) {
                continue;
            }
            $num = rtrim(rtrim(number_format(abs($pct), 2), '0'), '.');
            $labels[(string) $plan->name.$pct] = (string) $plan->name.' '.($pct < 0 ? '−' : '+').$num.'%';
        }

        return array_values($labels);
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
                // ⚠️ A restriction that can be SET but not SEEN is the same defect one step along: the
                //    operator would have no way to know why a booking was refused.
                'max_stay' => $row->max_stay !== null ? (int) $row->max_stay : null,
                'stop_sell' => (bool) $row->stop_sell,
                'closed_to_arrival' => (bool) $row->closed_to_arrival,
                'closed_to_departure' => (bool) $row->closed_to_departure,
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
    /**
     * D18-5 — which units already carry their OWN price, per plan, inside the displayed window.
     *
     * ⚠️ THE SOURCE OF TRUTH IS THE OVERRIDE TABLE, not a re-reading of rates. "Has its own price" is exactly
     * "has a row in `unit_rate_overrides`", so this cannot drift from what {@see NightlyRate}
     * actually resolves (§N.8f).
     *
     * ⚠️ COUNTED IN PHP AFTER `pluck`, DELIBERATELY. A `->distinct()->count()` on this shape has already
     * returned 0 for every period once in this codebase while looking like a working guard (§N.27.7), so the
     * unique-ing is done where it can be seen.
     *
     * @param  list<string>  $planIds
     * @return array<string, list<string>> plan id → the unit ids priced individually in the window
     */
    /**
     * The currency this grid edits in — the first real `rate_calendar` row's currency, else the scoped
     * property's own. Both are NOT NULL columns, so this never invents one.
     *
     * @param  array<string, array{minor: int, currency: string}>  $rates
     * @param  Collection<int, RoomType>  $roomTypes
     */
    private function gridCurrency(array $rates, Collection $roomTypes): string
    {
        foreach ($rates as $rate) {
            return $rate['currency'];
        }

        // ⚠️ F-44 (FIX 46) — NOTHING IS IN SCOPE TO DERIVE FROM when this collection is empty: there is
        //    no owner here and no property either. ⚠️ So the literal becomes the ONE product default
        //    (`config('billing.default_currency')`) rather than a hidden assumption repeated per file.
        //    ⚠️ Allow-listed by `CurrencyNeverDefaultsTest` WITH THIS REASON — see that test.
        return (string) ($roomTypes->first()?->property->currency ?? config('billing.default_currency', 'EUR'));
    }

    private function indexOverriddenUnits(array $planIds, string $from, string $through): array
    {
        if ($planIds === []) {
            return [];
        }

        return UnitRateOverride::query()
            ->whereIn('rate_plan_id', $planIds)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $through)
            ->get(['rate_plan_id', 'unit_id'])
            ->groupBy(fn (UnitRateOverride $o): string => (string) $o->rate_plan_id)
            ->map(fn ($rows): array => $rows->pluck('unit_id')->map(fn ($id): string => (string) $id)->unique()->values()->all())
            ->all();
    }

    private function row(RoomType $roomType, ?RatePlan $plan, array $dates, array $rates, array $availability, array $restrictions, array $overriddenUnits = []): array
    {
        $rtId = (string) $roomType->getKey();
        $planId = $plan?->getKey() !== null ? (string) $plan->getKey() : null;
        // ⚠️ THE BLAST-RADIUS COUNT, RECONCILED FOR PER-UNIT PRICING (D18-5). This label exists so the
        // operator is never misled about what a type-level save touches. It counted EVERY unit of the type,
        // which was exactly right while a rate plan could only be priced per type — and becomes a small lie
        // the moment a unit carries its own price, because a type-level save does not reach that unit.
        //
        // ⚠️ TRANSITION-CORRECT BY CONSTRUCTION: with no overrides in this plan+window the subtraction is
        // zero, so the number and the wording are byte-identical to what they have always been. The label
        // only changes shape once there is genuinely something to exclude.
        $units = (int) ($roomType->units_count ?? 0);
        $overridden = $planId === null ? 0 : count($overriddenUnits[$planId] ?? []);
        $affected = max(0, $units - $overridden);

        $cells = [];
        foreach ($dates as $date) {
            $rate = $planId !== null ? ($rates[$planId.'|'.$date] ?? null) : null;
            $avail = $availability[$rtId.'|'.$date] ?? null;
            $restriction = $restrictions[$rtId.'|'.$date] ?? null;

            $display = $rate !== null ? $this->money($rate['minor'], $rate['currency']) : null;
            $cells[] = [
                'date' => $date,
                'rate' => $display,
                'rate_fit' => $this->rateFit($display),
                // The raw decimal (e.g. "175.00") for the editor prefill — derived from integer minor units
                // via the Money value object, never a float division. The server re-parses authoritatively.
                'rate_input' => $rate !== null ? Money::of($rate['minor'], $rate['currency'])->toDecimalString() : '',
                'available' => $avail['available'] ?? null,
                'sold' => $avail['sold'] ?? null,
                'total' => $avail['total'] ?? null,
                'sold_out' => $avail !== null && $avail['available'] === 0,
                // the thin bar under each price, and the legend's "Last one" — real availability, nothing estimated
                'booked_pct' => $avail !== null && $avail['total'] > 0 ? (int) round($avail['sold'] / $avail['total'] * 100) : null,
                'last_one' => $avail !== null && $avail['available'] === 1 && $avail['total'] > 1,
                'min_stay' => $restriction['min_stay'] ?? null,
                'max_stay' => $restriction['max_stay'] ?? null,
                'stop_sell' => $restriction['stop_sell'] ?? false,
                'closed_to_arrival' => $restriction['closed_to_arrival'] ?? false,
                'closed_to_departure' => $restriction['closed_to_departure'] ?? false,
            ];
        }

        return [
            'id' => $rtId,
            'name' => (string) $roomType->name,
            'code' => (string) ($roomType->code ?? ''),
            'plan_name' => $plan !== null ? (string) $plan->name : null,
            'has_plan' => $planId !== null,
            // ⚠️ THE WORDING FOLLOWS THE NUMBER. Saying "2 units" while silently excluding a third would be
            // the same misleading count in a quieter form, so once any unit is excluded the label says why.
            // D18-6 · THE THIRD OPTION — the units under this type, READ-ONLY, each showing the rate it
            // actually resolves to. ⚠️ THE ROWS ARE NOT EDITABLE, AND THAT IS THE WHOLE DESIGN. A fully
            // editable per-unit grid ("Yes") would mint an override on every edit, detaching every unit from
            // its type and leaving the rack safeguard reading "0 of N" everywhere — useless BY SHAPE. Here the
            // type row stays the price-mover and per-unit stays the deliberately-invoked exception, which is
            // exactly the founder's ruling (a permission, not the norm).
            //
            // ⚠️ EVERY FIGURE COMES FROM NightlyRate — the ONE resolver (D4-1). The grid does NOT read
            // `unit_rate_overrides` or `rate_calendar` itself for these cells, so what a unit row displays is
            // by construction what that unit charges (item 18's displayed = charged), and a unit with no
            // override of its own shows its ROOM TYPE's rate, never €0.
            'unit_rows' => $planId === null ? [] : $this->unitRows($roomType, $planId, $dates),
            'units_label' => $overridden > 0
                ? trans_choice('php/livewire/operator/rates_grid.units_label_partial', $units, ['affected' => $affected, 'count' => $units, 'overridden' => $overridden])
                : trans_choice('php/livewire/operator/rates_grid.units_count', $units, ['count' => $units]),
            'cells' => $cells,
        ];
    }

    /**
     * The read-only per-unit rows for one room type (D18-6 third option).
     *
     * ⚠️ RESOLVED, NOT READ. Each cell asks {@see NightlyRate::forUnit()} — override first, else the type
     * price — so these rows cannot drift from what the booking paths charge. Re-indexing the tables here
     * would be a second implementation of the precedence and is exactly what §N.8f/D4-1 forbid.
     *
     * @param  list<string>  $dates
     * @return list<array{id: string, label: string, cells: list<array{date: string, rate: ?string, rate_fit: string, overridden: bool}>}>
     */
    private function unitRows(RoomType $roomType, string $planId, array $dates): array
    {
        return Unit::query()->where('room_type_id', $roomType->getKey())->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(function (Unit $unit) use ($planId, $dates): array {
                $unitId = (string) $unit->getKey();

                return [
                    'id' => $unitId,
                    // ⚠️ `name ?: code` — units carry a code and a NULL name; a money row that does not say
                    // which unit it prices is the blank-rows defect the bulk pass shipped and had to fix.
                    'label' => (string) ($unit->name !== null && $unit->name !== '' ? $unit->name : $unit->code),
                    'cells' => array_map(function (string $date) use ($planId, $unitId): array {
                        // ⚠️ F-38 — this read `NightlyRate::forUnit()` and printed the result as `money($minor, 'EUR')`,
                        // a LITERAL passed to a formatter whose currency argument is required. A GBP property's grid
                        // therefore showed euro amounts over pound prices, while the TYPE row six lines up
                        // ({@see row()}) had always passed the real `$rate['currency']`. The resolver now answers
                        // both halves together, so the symbol follows the same override→type precedence as the price.
                        $priced = NightlyRate::forUnitWithCurrency($planId, $unitId, $date);
                        $display = $priced === null ? null : $this->money($priced['minor'], $priced['currency']);

                        return [
                            'date' => $date,
                            'rate' => $display,
                            'rate_fit' => $this->rateFit($display),
                            // Marks the exception so the operator can see WHICH units are priced apart.
                            'overridden' => NightlyRate::hasOverride($planId, $unitId, $date),
                        ];
                    }, $dates),
                ];
            })->values()->all();
    }

    /**
     * A long price steps down a size so it stays inside its night. 🖥 A day column holds about 60 px of text at 1440 px, and
     * "€1,250.00" set at 13 px is 68 px wide — it ran into the next night (2026-10-01). Counted in characters of the price as
     * shown, which is what its width follows.
     */
    private function rateFit(?string $rate): string
    {
        $length = $rate === null ? 0 : mb_strlen($rate);

        return match (true) {
            $length >= 11 => 'is-longest',
            $length >= 10 => 'is-longer',
            $length >= 8 => 'is-long',
            default => '',
        };
    }

    /**
     * @return array{weekday: string, day: string, month: string}
     */
    private function dateHeader(string $date): array
    {
        $carbon = CarbonImmutable::parse($date);

        return [
            'date' => $date,
            'weekday' => $carbon->translatedFormat('D'),
            'day' => $carbon->format('j'),
            'month' => $carbon->translatedFormat('M'),
            // ⚠️ 2026-09-30 — a weekend NIGHT is Friday or Saturday: the nights bulk edit's "Weekends" changes, the nights
            //    the weekend uplift prices and the nights a season counts as "Fri/Sat". The grid shaded Saturday and Sunday,
            //    so its colours disagreed with every price rule on the page.
            'is_weekend' => in_array($carbon->dayOfWeekIso, [5, 6], true),
            'is_today' => $carbon->isToday(),
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
     * @param  list<string>  $planIds  the plans the grid shows — the jump looks only at these
     * @return array{label: string, from: string, through: string, prev: string, next: string, today: string, jump: ?array{date: string, href: string}}
     */
    private function windowNav(CarbonImmutable $from, string $fromStr, string $throughStr, string $rtFilter, array $planIds): array
    {
        $through = CarbonImmutable::parse($throughStr);
        // ⚠️ 2026-09-30 — every window link keeps the room-type filter; they used to drop it on the first click.
        $keep = $rtFilter !== '' ? ['rt' => $rtFilter] : [];

        // If nothing is priced in this window, offer a one-click jump to the next date that IS priced — for the plans ON
        // SCREEN. ⚠️ It used to search every plan in the organisation, so another property's rates could send the operator
        // to an empty fortnight here.
        $jump = null;
        $earliest = $planIds === [] ? null : RateCalendar::query()
            ->whereIn('rate_plan_id', $planIds)
            ->whereDate('date', '>=', $fromStr)
            ->orderBy('date')
            ->value('date');
        if ($earliest !== null) {
            $earliestStr = CarbonImmutable::parse($earliest)->toDateString();
            if ($earliestStr > $throughStr) {
                $jump = ['date' => $earliestStr, 'href' => route('app.rates', ['from' => $earliestStr] + $keep)];
            }
        }

        return [
            'label' => $from->translatedFormat('j M').' – '.$through->translatedFormat('j M Y'),
            // D18-6 — the raw bounds, so a dialog opened from a unit row defaults to the window the operator
            // is actually looking at. (The label above is for humans and cannot be put in a date input.)
            'from' => $fromStr,
            'through' => $throughStr,
            'prev' => route('app.rates', ['from' => $from->subDays(self::WINDOW_DAYS)->toDateString()] + $keep),
            'next' => route('app.rates', ['from' => $from->addDays(self::WINDOW_DAYS)->toDateString()] + $keep),
            'today' => route('app.rates', ['from' => CarbonImmutable::today()->toDateString()] + $keep),
            'jump' => $jump,
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────────────────────

    /**
     * ⚠️ DELEGATES — this used to be a SECOND definition of "the room type's primary rate plan", and it
     * disagreed with the model's (F-34, FIX 15). Neither branch here filtered `is_active`: the default branch
     * returned a plan taken off sale, and the fallback was saved only by the `where('is_active', true)`
     * closure on the eager-load far above — a constraint at a distance, correct today and one edit from not
     * being. There were THREE copies of this method in the codebase. There is now ONE, in the model, and the
     * project's own rule is why: two computations of one concept are how surfaces drift.
     */
    private function primaryRatePlan(RoomType $roomType): ?RatePlan
    {
        return $roomType->primaryRatePlan();
    }

    private function subtitle(int $count): string
    {
        return trans_choice('php/livewire/operator/rates_grid.subtitle', $count, ['count' => $count]);
    }
}
