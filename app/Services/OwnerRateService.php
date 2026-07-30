<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Owner\OwnerRateException;
use App\Models\RoomType;
use App\Models\Unit;
use App\Support\Money\Money;
use App\Support\Money\MoneyInput;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Support\Collection;

/**
 * Owner RATES write path (Phase 2 · Sub-gate 3) — the HIGHEST-RISK gate of Phase 2: an owner-scoped MONEY write.
 * The owner edits the nightly rate (and min-stay / open-close) on their OWN room types, DIRECTLY (no operator
 * sign-off — see the divergence note below), through the SAME certified {@see RateAndRestrictionService} the
 * operator Rates screen drives ({@see setRate}/{@see setRestriction}) — never a parallel owner-rate writer.
 *
 * ⚠️ THE OWNER-SCOPE GUARD ON A MONEY WRITE — and it is SUBTLE because RATES ARE PER-ROOM-TYPE. A rate is
 * keyed on rate_plan → room_type, and a room type can contain units owned by DIFFERENT parties. So an owner may
 * edit a room type's rate ONLY IF they own it EXCLUSIVELY — every unit in that room type is theirs. A room type
 * that also holds an operator-owned or another-owner unit is NOT editable (editing it would move someone else's
 * price). The editable set is resolved through OWNERSHIP ({@see editableRoomTypes()}: owner-scoped units →
 * their room types → keep only the ones with no foreign unit), NEVER from a request room-type/unit id. A write
 * to a room type outside that set throws {@see OwnerRateException} with nothing written — mutation-proven both
 * ways (removing the guard lets A write B's rate; with it, A's tampering leaves B's rate untouched).
 *
 * ⚠️ DIRECT EDIT, NO APPROVAL — a CONSCIOUS DIVERGENCE from the design. The "Owner Rates" prototype is an
 * approval/guardrails model ("Every change needs your sign-off before it goes live" — the manager proposes,
 * the owner approves/declines/counters, and sets floor/ceiling guardrails). The product decision is FULL
 * DIRECT edit: the owner sets the rate and it is live immediately. There is NO proposal/approval/guardrail
 * schema, so those sections are not rendered as real (never a fake "pending sign-off"); an approval workflow
 * would be a clean addition later. Recorded in docs/DECISIONS.md.
 *
 * ⚠️ INTEGER MONEY (#17): the € the owner types is parsed to minor units by {@see MoneyInput}
 * in the controller (string arithmetic, the 175.57 → 17557 guarantee — never a float); here it is only ever an
 * int carried by the {@see Money} value object in the plan's currency. No pricing math client-side.
 */
class OwnerRateService
{
    public function __construct(
        private RateAndRestrictionService $rates,
        private OwnerPortalService $portal,
        private OwnerContext $ownerContext,
    ) {}

    /**
     * The room types the owner may READ and EDIT — the ones they own EXCLUSIVELY (every unit is theirs). This
     * is both the read-isolation set (a shared room type never appears) and the write allow-list.
     *
     * @return Collection<int, RoomType>
     */
    public function editableRoomTypes(): Collection
    {
        $units = $this->portal->units();   // owner-scoped, fail-closed when empty
        // 🔒 P1-1: rates are a CAP_ACTIONS money write — editable only for owners where the acting user's
        // PER-OWNER role grants actions (a co-owner of A + view-only of B may not touch B's rates).
        $ownerIds = $this->ownerContext->ownerIdsWithCapability(OwnerTeamRole::CAP_ACTIONS);
        $ownedUnitIds = $units->map(fn (Unit $u): string => (string) $u->id);

        if ($ownedUnitIds->isEmpty() || $ownerIds === []) {
            return collect();   // no owner context / no actions capability → nothing editable (fail closed)
        }

        $exclusiveRoomTypeIds = $units
            ->filter(fn (Unit $u): bool => in_array((string) $u->owner_id, $ownerIds, true))   // only actionable owners' units
            ->map(fn (Unit $u): string => (string) $u->room_type_id)
            ->unique()
            ->filter(fn (string $roomTypeId): bool => ! $this->hasForeignUnit($roomTypeId, $ownerIds))
            ->values();

        if ($exclusiveRoomTypeIds->isEmpty()) {
            return collect();
        }

        return RoomType::query()
            ->whereIn('id', $exclusiveRoomTypeIds->all())
            ->with(['units', 'defaultRatePlan'])
            ->orderBy('name')
            ->get();
    }

    /**
     * True if the room type contains any unit NOT owned by this owner (an operator-owned unit → owner_id null,
     * or another owner's). Read WITHOUT the owner scope on purpose — to decide the boundary we must see every
     * unit in the room type, including the ones the owner scope would hide. It exposes nothing (only counts
     * ownership) and is used to DENY.
     *
     * @param  list<string>  $ownerIds
     */
    private function hasForeignUnit(string $roomTypeId, array $ownerIds): bool
    {
        return Unit::withoutOwnerScope()
            ->where('room_type_id', $roomTypeId)
            ->where(fn ($q) => $q->whereNull('owner_id')->orWhereNotIn('owner_id', $ownerIds))
            ->exists();
    }

    /**
     * THE guard: a room type the owner may edit, or throw. Resolved by membership in {@see editableRoomTypes()}
     * — never by trusting the request id.
     */
    public function editableRoomTypeOrFail(string $roomTypeId): RoomType
    {
        $roomType = $this->editableRoomTypes()->first(fn (RoomType $rt): bool => (string) $rt->id === $roomTypeId);

        if (! $roomType instanceof RoomType) {
            throw OwnerRateException::notYourRoomType($roomTypeId);
        }

        return $roomType;
    }

    /**
     * Apply a nightly rate and/or restrictions to ONE of the owner's OWN room types over [from, toExclusive),
     * through the certified service. `$priceMinor` is already an integer (parsed + bounded in the controller);
     * the currency is the plan's, never the request's. Returns false only when a rate was asked for but the
     * room type has no rate plan to store it on.
     */
    public function apply(string $roomTypeId, string $from, string $toExclusive, ?int $priceMinor, ?int $minStay, ?bool $stopSell, ?float $weekendUpliftPct = null): bool
    {
        return $this->applyTo($this->editableRoomTypeOrFail($roomTypeId), $from, $toExclusive, $priceMinor, $minStay, $stopSell, $weekendUpliftPct);
    }

    /**
     * Apply a rate to a room type EXCLUSIVELY owned by `$ownerId`, verifying that boundary WITHOUT the owner
     * scope — so an OPERATOR-context caller (a rate-proposal auto-approve / counter-accept) can write the same
     * money the owner would, and only ever to a room type that is entirely this owner's. The exclusive-ownership
     * check is the SAME boundary {@see RateProposalService} enforces at propose time; a shared
     * room type throws {@see OwnerRateException::notYourRoomType}. Used only by the certified proposal path.
     */
    public function applyForExclusiveOwner(string $roomTypeId, string $ownerId, string $from, string $toExclusive, ?int $priceMinor, ?int $minStay, ?bool $stopSell, ?float $weekendUpliftPct = null): bool
    {
        $shared = Unit::withoutOwnerScope()
            ->where('room_type_id', $roomTypeId)
            ->where(fn ($q) => $q->whereNull('owner_id')->orWhere('owner_id', '!=', $ownerId))
            ->exists();

        if ($shared) {
            throw OwnerRateException::notYourRoomType($roomTypeId);
        }

        $roomType = RoomType::query()->withoutGlobalScopes()->findOrFail($roomTypeId);

        return $this->applyTo($roomType, $from, $toExclusive, $priceMinor, $minStay, $stopSell, $weekendUpliftPct);
    }

    /** The shared apply body — resolves the plan + writes the nightly/weekend rate + min-stay via the certified service. */
    private function applyTo(RoomType $roomType, string $from, string $toExclusive, ?int $priceMinor, ?int $minStay, ?bool $stopSell, ?float $weekendUpliftPct): bool
    {
        $rateStored = true;
        $plan = $roomType->primaryRatePlan();

        if ($priceMinor !== null) {
            if ($plan === null) {
                $rateStored = false;
            } elseif ($weekendUpliftPct !== null && $weekendUpliftPct > 0) {
                // REAL weekend pricing — Fri/Sat nights get base × (1 + uplift%), the rest get base (#39: no inert flag).
                $weekendMinor = (int) round($priceMinor * (1 + $weekendUpliftPct / 100));
                $this->rates->setRateWeekendAware($plan, $from, $toExclusive, Money::of($priceMinor, (string) $plan->currency), Money::of($weekendMinor, (string) $plan->currency));
            } else {
                $this->rates->setRate($plan, $from, $toExclusive, Money::of($priceMinor, (string) $plan->currency));
            }
        }

        // Persist the owner's weekend-uplift POLICY on the plan (so the editor prefills it), when provided.
        if ($weekendUpliftPct !== null && $plan !== null) {
            $plan->forceFill(['weekend_uplift_pct' => $weekendUpliftPct])->save();
        }

        if ($minStay !== null || $stopSell !== null) {
            $attributes = ['room_type_id' => (string) $roomType->getKey()];
            if ($minStay !== null) {
                $attributes['min_stay'] = $minStay;
            }
            if ($stopSell !== null) {
                $attributes['stop_sell'] = $stopSell;
            }
            $this->rates->setRestriction($attributes, $from, $toExclusive);
        }

        return $rateStored;
    }

    /**
     * The owner's current WEEKEND-UPLIFT % for a room type they may edit (the editor prefills it), or null when
     * unset. Owner-scoped via {@see editableRoomTypeOrFail} — a room type the owner may not edit throws (403),
     * the same guard as the write.
     */
    public function weekendUpliftFor(string $roomTypeId): ?float
    {
        $plan = $this->editableRoomTypeOrFail($roomTypeId)->primaryRatePlan();

        return $plan?->weekend_uplift_pct !== null ? (float) $plan->weekend_uplift_pct : null;
    }
}
