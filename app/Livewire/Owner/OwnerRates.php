<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Controllers\Owner\OwnerRateController;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\RateCalendar;
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
 * and it is live. The design's Proposed-changes, Guardrails, comp-set and Base/Weekend/Peak tiers have NO
 * backing schema and are NOT rendered as real (no fake "pending sign-off"); the schema stores ONE rate per
 * night, so a single nightly rate is what the owner edits.
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

        return [
            'id' => (string) $rt->id,
            'name' => (string) $rt->name,
            'units' => $rt->units->map(fn (Unit $u): string => (string) ($u->name ?? $u->code))->values()->all(),
            'currency' => (string) ($plan->currency ?? 'EUR'),
            'has_plan' => $plan !== null,
            'rate_display' => $rate === null ? null : $this->money((int) $rate->price_minor, (string) $rate->currency),
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
