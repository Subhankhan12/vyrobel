<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Controllers\Owner\OwnerRateController;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\RateCalendar;
use App\Models\RateGuardrail;
use App\Models\RateProposal;
use App\Models\Restriction;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\OwnerRateService;
use App\Services\RateAndRestrictionService;
use App\Services\SeasonalRateService;
use App\Support\Analytics\PerformanceMetrics;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Rates (Phase 2 · Sub-gate 3) — the owner sets nightly rates on their OWN room types, DIRECTLY. The READ
 * side; the money WRITE goes POST → {@see OwnerRateController} → {@see OwnerRateService}
 * → the certified {@see RateAndRestrictionService}.
 *
 * ISOLATION — the screen shows ONLY room types the owner owns EXCLUSIVELY (via
 * {@see OwnerRateService::editableRoomTypes()}: owner-scoped units → their room types → those with no foreign
 * unit). A room type shared with another owner or the operator never appears and can't be edited — because a
 * rate is per-room-type, editing a shared one would move someone else's price. `?room_type=` / `?from=` ride GET
 * so the owner middleware re-runs (a wire-action would bypass the owner scope — invariant #20).
 *
 * ⚠️ STALE DIVERGENCE CORRECTED (design pass · Owner-4). This used to read: "the design's Guardrails, comp-set
 * and the Peak tier remain unmodelled … Operator→owner RATE PROPOSALS are … surfaced in the ATTENTION feed …
 * not on this Rates page." TWO of those three claims have since shipped and are on THIS page:
 *   • PROPOSALS are here — the real #67 `rate_proposal` cards with Approve / Decline / Counter, each POSTing
 *     an `owner.rate-proposals.*` route.
 *   • GUARDRAILS are here AND ENFORCING — a real {@see RateGuardrail} row, saved via `owner.rates.guardrails`,
 *     which {@see RateProposalService::propose()} genuinely applies: below floor / above ceiling is REJECTED,
 *     and a change within ±`auto_approve_pct` auto-applies. ⚠️ With NO row the service leaves every proposal
 *     `pending` — which is exactly what the card's ±0% state says, so the copy is literally true.
 * Only the **comp-set / market position** and the **Peak tier** are still unmodelled, and both stay refused.
 * The DIRECT edit remains real and additional: the owner sets their own rate and it is live immediately.
 *
 * Wave-B (owner completeness sweep · GATE 3): the design's BASE + WEEKEND tiers are now REAL — the editor sets
 * the nightly (weekday) rate, a WEEKEND UPLIFT % (writes the higher price onto Fri/Sat via the Wave-A
 * {@see OwnerRateService::setRateWeekendAware}), and the min-stay; the current-rates table surfaces all three
 * per room type (the owner's pricing home), with a real derived Fri/Sat preview before saving. Still one stored
 * rate per night underneath — the weekend price IS a real per-night rate, not a separate tier entity.
 */
#[Layout('components.layouts.owner')]
class OwnerRates extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $roomTypes = app(OwnerRateService::class)->editableRoomTypes();   // exclusive; empty → fail-closed

        // The manager's OPEN proposals (pending + the owner's counters awaiting the operator) — surfaced on the
        // Rates page itself (#81), owner-scoped by BelongsToOwner. The guardrails + the real average are shown
        // regardless of whether there are editable room types.
        $proposals = $this->openProposals();
        $guardrail = $this->guardrailData();

        if ($roomTypes->isEmpty()) {
            return view('livewire.owner.rates', [
                'viewer' => $this->viewer(),
                'cards' => [],
                'selectedId' => null,
                'from' => Carbon::today()->toDateString(),
                'to' => Carbon::today()->addDays(6)->toDateString(),
                'proposals' => $proposals,
                'guardrail' => $guardrail,
                'avgDisplay' => null,
                'currencySymbol' => $this->currencySymbol($this->ratesCurrency([], $roomTypes)),
            ]);
        }

        $from = $this->resolveFrom();
        $selectedId = $this->resolveSelected($roomTypes, $from);
        $cards = $roomTypes->map(fn (RoomType $rt): array => $this->card($rt, $from))->all();

        return view('livewire.owner.rates', [
            'viewer' => $this->viewer(),
            'cards' => $cards,
            'selectedId' => $selectedId,
            'from' => $from,
            'to' => Carbon::parse($from)->addDays(6)->toDateString(),   // a sensible default range end
            'proposals' => $proposals,
            'guardrail' => $guardrail,
            // WHERE YOU SIT — the owner's REAL average nightly rate across their room types (comp-set/market is
            // external data Vyrobel does not have → omitted, never fabricated #39).
            'avgDisplay' => $this->averageDisplay($cards),
            // ⚠️ The rate inputs, the counter-offer field, the guardrail floor/ceiling and the weekend-uplift
            // preview all printed a hardcoded '€'. They edit money in the RATE PLAN's currency, so a GBP
            // portfolio labelled every one of them in euros. One page-level symbol, from the real currency.
            'currencySymbol' => $this->currencySymbol($this->ratesCurrency($cards, $roomTypes)),
        ]);
    }

    /**
     * The manager's OPEN rate proposals for this owner (pending + countered) — owner-scoped (RateProposal is
     * BelongsToOwner). Each formatted for the exact-match card: the room type, the window, the note (rationale),
     * the before→after, the delta, and the state (so approved/declined/countered render distinctly).
     *
     * @return list<array<string, mixed>>
     */
    private function openProposals(): array
    {
        return RateProposal::query()
            ->whereIn('state', [RateProposal::STATE_PENDING, RateProposal::STATE_COUNTERED])
            ->with('roomType')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (RateProposal $p): array => $this->proposalRow($p))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function proposalRow(RateProposal $p): array
    {
        $currency = (string) $p->currency;
        $current = $p->current_rate_minor !== null ? (int) $p->current_rate_minor : null;
        $proposed = (int) $p->proposed_rate_minor;
        $pct = ($current !== null && $current > 0) ? (int) round(($proposed - $current) / $current * 100) : null;
        $up = $proposed >= ($current ?? $proposed);

        return [
            'id' => (string) $p->getKey(),
            'villa' => (string) ($p->roomType->name ?? 'Room type'),
            'window' => Carbon::parse($p->from_date)->format('j M').' – '.Carbon::parse($p->to_date)->format('j M'),
            'effective' => Carbon::parse($p->from_date)->format('j M'),
            'rationale' => (string) ($p->note ?? ''),
            'from_str' => $current !== null ? $this->money($current, $currency) : '—',
            'to_str' => $this->money($proposed, $currency),
            'delta_str' => $pct === null ? '—' : (($up ? '+' : '−').abs($pct).'%'),
            'up' => $up,
            'countered' => $p->isCountered(),
            'counter_str' => $p->countered_rate_minor !== null ? $this->money((int) $p->countered_rate_minor, $currency) : null,
        ];
    }

    /**
     * The owner's saved guardrails, or the honest defaults (no row → dynamic, 0% auto-approve, no floor/ceiling —
     * i.e. every change waits for the owner, which is exactly today's behaviour until they set limits).
     *
     * @return array{mode: string, auto_pct: int, floor: string, ceiling: string, set: bool}
     */
    private function guardrailData(): array
    {
        $ids = app(OwnerContext::class)->ownerIdsWithCapability(OwnerTeamRole::CAP_ACTIONS);
        $g = $ids === [] ? null : RateGuardrail::query()->where('owner_id', $ids[0])->first();

        if ($g === null) {
            // No row → every change waits for the owner (today's behaviour until they set limits).
            return ['mode' => RateGuardrail::MODE_DYNAMIC, 'auto_pct' => 0, 'floor' => '', 'ceiling' => '', 'set' => false];
        }

        return [
            'mode' => (string) $g->pricing_mode,
            'auto_pct' => (int) $g->auto_approve_pct,
            'floor' => $g->floor_minor !== null ? (string) (int) round($g->floor_minor / 100) : '',
            'ceiling' => $g->ceiling_minor !== null ? (string) (int) round($g->ceiling_minor / 100) : '',
            'set' => true,
        ];
    }

    /**
     * The owner's REAL average current nightly rate across their room types (the ones with a set rate), or null.
     *
     * @param  list<array<string, mixed>>  $cards
     */
    /**
     * The currency these rate editors work in — the first card's real rate-plan currency, else the owner's
     * room-type property currency (the {@see OwnerMyUnits} rule, so the two screens agree).
     *
     * ⚠️ THE GUARDRAIL FLOOR/CEILING RENDER EVEN WITH NO EDITABLE ROOM TYPES, so this must answer in that
     * branch too — hence the property fallback rather than reading the cards alone.
     *
     * @param  list<array<string, mixed>>  $cards
     * @param  Collection<int, RoomType>  $roomTypes
     */
    private function ratesCurrency(array $cards, Collection $roomTypes): string
    {
        foreach ($cards as $card) {
            if (isset($card['currency']) && is_string($card['currency']) && $card['currency'] !== '') {
                return $card['currency'];
            }
        }

        // ⚠️⚠️ F-44 (FIX 46) — THE FALLBACK FIRES ON AN EMPTY COLLECTION, NOT A NULL COLUMN.
        //    A GBP owner with nothing yet to show was quoted EUROS. ⚠️ The amount is right and the
        //    symbol is wrong, which is the dangerous direction: zero looks the same in every currency.
        //    ⚠️ THE OWNER THEMSELVES STILL CARRIES ONE — `owners.payout_currency` is char(3) NOT NULL.
        //    Same answer F-46 gave for this class; the last resort is CONFIG, never a literal.
        return (string) ($roomTypes->first()?->property->currency
            ?? $this->ownerPayoutCurrency()
            ?? config('billing.default_currency', 'EUR'));
    }

    private function averageDisplay(array $cards): ?string
    {
        $minors = [];
        $currency = 'EUR';
        foreach ($cards as $c) {
            if (($c['rate_units'] ?? null) !== null) {
                $minors[] = (int) $c['rate_units'] * 100;
                $currency = (string) $c['currency'];
            }
        }

        if ($minors === []) {
            return null;
        }

        return $this->money((int) round(array_sum($minors) / count($minors)), $currency);
    }

    /**
     * A room type row: its name, the owner's units in it, and the CURRENT nightly rate on the reference date
     * (from `rate_calendar` for its primary plan — real, or "Not set" when no rate/plan exists).
     *
     * @return array<string, mixed>
     */
    private function card(RoomType $rt, string $from): array
    {
        $plan = $rt->primaryRatePlan();
        $rate = $plan === null ? null : RateCalendar::query()
            ->where('rate_plan_id', $plan->getKey())
            ->whereDate('date', $from)
            ->first();

        // Wave-B: surface the real weekend-uplift POLICY (on the plan) + the current min-stay (soonest upcoming
        // restriction) alongside the nightly rate — the owner's pricing home in one manage view.
        $today = Carbon::today()->toDateString();
        $weekendPct = $plan?->weekend_uplift_pct !== null ? (float) $plan->weekend_uplift_pct : null;
        // Owner-set restrictions are keyed by room_type_id (OwnerRateService::apply → setRestriction), rate_plan_id
        // null — so read by room_type_id, the same key they were written under.
        $minStay = Restriction::query()
            ->where('room_type_id', $rt->getKey())
            ->whereDate('date', '>=', $today)
            ->whereNotNull('min_stay')
            ->orderBy('date')
            ->value('min_stay');

        $rateMinor = $rate === null ? null : (int) $rate->price_minor;

        // REAL forward occupancy for THIS room type's owned units (next 30 nights) — the shared metric layer.
        $unitIds = $rt->units->map(fn (Unit $u): string => (string) $u->id)->all();
        $occStart = CarbonImmutable::today();
        $occ = $unitIds === [] ? 0 : PerformanceMetrics::forOwnerUnits($unitIds, $occStart, $occStart->addDays(30), (string) ($plan->currency ?? 'EUR'))->occupancyPct();

        return [
            'id' => (string) $rt->id,
            'name' => (string) $rt->name,
            'units' => $rt->units->map(fn (Unit $u): string => (string) ($u->name ?? $u->code))->values()->all(),
            'currency' => (string) ($plan->currency ?? 'EUR'),
            'has_plan' => $plan !== null,
            'rate_display' => $rate === null ? null : $this->money($rateMinor, (string) $rate->currency),
            'rate_units' => $rateMinor !== null ? (int) round($rateMinor / 100) : null,
            'weekend_pct' => $weekendPct,
            // ⚠️ PEAK — the covering season, READ FROM `rate_seasons` and NEVER fabricated. This is DISPLAY
            // ONLY: the charge reads `rate_calendar`, into which a season is materialized when applied. A row
            // with no season shows "—", and a season DEFINED but not yet applied says so rather than implying
            // those nights already carry the price.
            'season' => $plan === null ? null : $this->seasonCardFor((string) $plan->getKey()),
            // The derived Fri/Sat price at the current uplift — a real figure for the manage view (never fabricated).
            'weekend_display' => ($rateMinor !== null && $weekendPct !== null && $weekendPct > 0)
                ? $this->money((int) round($rateMinor * (1 + $weekendPct / 100)), (string) $rate->currency)
                : null,
            'min_stay' => $minStay !== null ? (int) $minStay : null,
            'occ_pct' => $occ,
        ];
    }

    /** `?from=` if a real date, else today — the reference date whose rate the cards show. */
    private function resolveFrom(): string
    {
        $param = request()->query('from');
        if (is_string($param) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $param) === 1) {
            [$y, $m, $d] = array_map('intval', explode('-', $param));
            if (checkdate($m, $d, $y)) {
                return $param;
            }
        }

        return Carbon::today()->toDateString();
    }

    /**
     * `?room_type=` if it is one of the owner's EDITABLE room types, else the first. A foreign id is ignored —
     * the set is already the exclusive-ownership set.
     *
     * @param  Collection<int, RoomType>  $roomTypes
     */
    private function resolveSelected($roomTypes, string $from): string
    {
        $requested = request()->query('room_type');
        if (is_string($requested) && $roomTypes->contains(fn (RoomType $rt): bool => (string) $rt->id === $requested)) {
            return $requested;
        }

        return (string) $roomTypes->first()->id;
    }

    /**
     * The season covering TODAY for a plan, for the PEAK column — or NULL when none.
     *
     * ⚠️ DISPLAY ONLY, and it must stay that way. A season prices nights by being MATERIALIZED into
     * `rate_calendar`; reading `rate_seasons` to charge a stay would be the second price source this design
     * exists to avoid (D4-1).
     *
     * @return array{name: string, display: string, applied: bool}|null
     */
    private function seasonCardFor(string $ratePlanId): ?array
    {
        $season = app(SeasonalRateService::class)->covering($ratePlanId, now()->toDateString());

        if ($season === null) {
            return null;
        }

        return [
            'name' => (string) $season->name,
            'display' => $this->money((int) $season->price_minor, (string) $season->currency),
            // ⚠️ An unapplied season is NOT yet the price of anything — say so rather than let the column
            // imply those nights are already selling at it.
            'applied' => $season->applied_at !== null,
        ];
    }
}
