<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Controllers\Owner\OwnerRateController;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\RateCalendar;
use App\Models\Restriction;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\OwnerRateService;
use App\Services\RateAndRestrictionService;
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
 * ⚠️ DIVERGENCE (recorded): the "Owner Rates" design is an approval/guardrails model (the manager proposes,
 * the owner signs off, floor/ceiling guardrails). The decision is FULL DIRECT edit — the owner sets the rate
 * and it is live. Operator→owner RATE PROPOSALS are now REAL (#67 — a `rate_proposal` entity the owner approves/
 * declines), but surfaced in the ATTENTION feed (My Units / Overview / Insights), not on this Rates page. The
 * design's Guardrails, comp-set and the Peak tier remain unmodelled → NOT rendered as real (no fake "pending sign-off").
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

        if ($roomTypes->isEmpty()) {
            return view('livewire.owner.rates', [
                'viewer' => $this->viewer(),
                'cards' => [],
                'selectedId' => null,
                'from' => Carbon::today()->toDateString(),
                'to' => Carbon::today()->addDays(6)->toDateString(),
            ]);
        }

        $from = $this->resolveFrom();
        $selectedId = $this->resolveSelected($roomTypes, $from);

        return view('livewire.owner.rates', [
            'viewer' => $this->viewer(),
            'cards' => $roomTypes->map(fn (RoomType $rt): array => $this->card($rt, $from))->all(),
            'selectedId' => $selectedId,
            'from' => $from,
            'to' => Carbon::parse($from)->addDays(6)->toDateString(),   // a sensible default range end
        ]);
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

        return [
            'id' => (string) $rt->id,
            'name' => (string) $rt->name,
            'units' => $rt->units->map(fn (Unit $u): string => (string) ($u->name ?? $u->code))->values()->all(),
            'currency' => (string) ($plan->currency ?? 'EUR'),
            'has_plan' => $plan !== null,
            'rate_display' => $rate === null ? null : $this->money($rateMinor, (string) $rate->currency),
            'rate_units' => $rateMinor !== null ? (int) round($rateMinor / 100) : null,
            'weekend_pct' => $weekendPct,
            // The derived Fri/Sat price at the current uplift — a real figure for the manage view (never fabricated).
            'weekend_display' => ($rateMinor !== null && $weekendPct !== null && $weekendPct > 0)
                ? $this->money((int) round($rateMinor * (1 + $weekendPct / 100)), (string) $rate->currency)
                : null,
            'min_stay' => $minStay !== null ? (int) $minStay : null,
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
}
