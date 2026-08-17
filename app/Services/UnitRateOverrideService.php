<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\UnitRateOverride;
use App\Support\Money\MoneyInput;
use App\Support\Pricing\NightlyRate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * D18-5 — THE ONE WRITER for `unit_rate_overrides`, and the reason it exists is D4-1.
 *
 * ⚠️⚠️ CONFIRM-FIRST FOUND THAT THE "SINGLE-SET AUTHORING PATH" THIS WAS TOLD TO REUSE DOES NOT EXIST.
 * Phase 1 shipped the table and {@see NightlyRate} but deliberately shipped NO authoring surface (see the
 * DECISIONS entry for 2026-08-17: "the surface is deliberately not built"), and its own sequenced list ends
 * with "bulk tooling". Nothing in `app/` writes this table — the only writers in the repo are three test
 * fixtures. So a bulk-only writer built today would BECOME the single writer, and the single-set surface
 * built tomorrow would be the SECOND one — precisely the two-sources-that-can-disagree hazard the D4-1 rule
 * exists to prevent, arrived at from the other direction.
 *
 * ⚠️ THEREFORE THIS SERVICE IS THE WRITE PATH ITSELF, NOT A BULK WRAPPER AROUND ONE. Single-set is simply
 * the N=1 call: one unit, one night. When the single-set UI ships it calls this, and there is still exactly
 * one place that decides what an override row means (§N.8f).
 *
 * ⚠️⚠️ THE PREVIEW IS A CONTRACT, NOT A COURTESY — this is the whole safety model of a bulk money op. A bulk
 * mistake is not one wrong price, it is many wrong prices at once, and it is silent. So:
 *
 *   1. {@see preview()} computes the exact per-unit, per-night change set and WRITES NOTHING. It returns a
 *      `digest` — a hash over every (unit, night, from, to) tuple it is about to promise.
 *   2. {@see commit()} RE-COMPUTES that change set inside the transaction and REFUSES unless the digest
 *      still matches. If anything moved underneath the operator while they were reading the preview — a
 *      colleague edited the type rate, another override appeared — the promise is stale and the commit
 *      aborts having written nothing, rather than applying a change to figures nobody approved.
 *
 *   That is what makes "what commits equals what the preview showed" a mechanism rather than a claim.
 *
 * ⚠️ ATOMIC, ALWAYS. Every write happens inside ONE transaction. A half-applied bulk set is a half-priced
 * set of units — the worst possible outcome, because it looks like it worked. A failure anywhere rolls the
 * whole thing back, including the units that had already succeeded.
 *
 * ⚠️ CLEAR REMOVES ROWS, IT DOES NOT WRITE ZEROS. An absent override means "no override", which resolves to
 * the room-type price. Writing €0 would sell the night for nothing. The preview shows the type rate each
 * unit will fall back TO, so the operator approves the real landing figure, not the word "cleared".
 *
 * ⚠️ NO PRICING MATH LIVES HERE. The figure written is the integer minor amount the controller parsed with
 * the same {@see MoneyInput} the type-level rate write uses — VAT-inclusive on the flat
 * view (§G.1), untouched. This service decides WHICH rows change, never what a price means.
 */
final class UnitRateOverrideService
{
    public const MODE_SET = 'set';

    public const MODE_CLEAR = 'clear';

    public const MODES = [self::MODE_SET, self::MODE_CLEAR];

    /** Bounds on one bulk operation — a blast-radius limit, not a business rule. */
    public const MAX_SPAN_NIGHTS = 370;

    public const MAX_UNITS = 200;

    /**
     * Compute exactly what a bulk set/clear WOULD change. ⚠️ WRITES NOTHING — this is the approval gate.
     *
     * @param  list<string>  $unitIds  already resolved + tenant-scoped by the caller
     * @param  string  $endExclusive  half-open [start, end), matching the rate services
     * @return array{mode:string,currency:string,price_minor:?int,nights:int,digest:string,units:list<array<string,mixed>>,changing_units:int,unchanged_units:int,skipped_units:int,changing_nights:int,writes:int}
     */
    public function preview(RatePlan $plan, array $unitIds, string $start, string $endExclusive, string $mode, ?int $priceMinor): array
    {
        $dates = $this->nights($start, $endExclusive);
        $typePrice = $this->indexTypePrices($plan, $dates);
        $overrides = $this->indexOverrides($plan, $unitIds, $dates);

        $units = [];
        $tuples = [];
        $changingUnits = 0;
        $unchangedUnits = 0;
        $skippedUnits = 0;
        $changingNights = 0;
        $writes = 0;

        foreach ($unitIds as $unitId) {
            $unitChanging = 0;
            $unitWrites = 0;
            $from = null;
            $to = null;

            foreach ($dates as $date) {
                // ⚠️ The CURRENT resolved price — override first, then the type rate. Identical precedence to
                // NightlyRate::forUnit(), read from indexes so a 200-unit range is a handful of queries
                // rather than tens of thousands. A test asserts the two agree tuple-for-tuple.
                $current = $overrides[$unitId][$date] ?? $typePrice[$date] ?? null;

                if ($mode === self::MODE_SET) {
                    $target = $priceMinor;
                    $op = 'w';
                } else {
                    // ⚠️ CLEAR LANDS ON THE TYPE RATE, NEVER €0 — and only where a row actually exists.
                    if (! isset($overrides[$unitId][$date])) {
                        continue;
                    }
                    $target = $typePrice[$date] ?? null;
                    $op = 'd';
                }

                $from ??= $current;
                $to ??= $target;
                $unitWrites++;
                $tuples[] = $unitId.'|'.$date.'|'.$this->tupleValue($current).'|'.$this->tupleValue($target).'|'.$op;

                if ($current !== $target) {
                    $unitChanging++;
                }
            }

            if ($unitWrites === 0) {
                // Nothing to clear on this unit — it carries no override in the window.
                $skippedUnits++;

                continue;
            }

            $writes += $unitWrites;
            $changingNights += $unitChanging;
            $unitChanging > 0 ? $changingUnits++ : $unchangedUnits++;

            $units[] = [
                'unit_id' => $unitId,
                'from_minor' => $from,
                'to_minor' => $to,
                'nights' => $unitWrites,
                'nights_changing' => $unitChanging,
                // 'change'  — money moves on this unit
                // 'pinned'  — a SET whose figure equals what the unit already resolves to: no money moves,
                //             but an override row is created, which DOES change the rack safeguard's count
                // 'cleared' — a CLEAR that hands the unit back to the type rate
                'state' => $unitChanging > 0 ? ($mode === self::MODE_CLEAR ? 'cleared' : 'change') : 'pinned',
            ];
        }

        return [
            'mode' => $mode,
            'currency' => (string) $plan->currency,
            'price_minor' => $priceMinor,
            'nights' => count($dates),
            'digest' => $this->digest($plan, $mode, $priceMinor, $tuples),
            'units' => $units,
            'changing_units' => $changingUnits,
            'unchanged_units' => $unchangedUnits,
            'skipped_units' => $skippedUnits,
            'changing_nights' => $changingNights,
            'writes' => $writes,
        ];
    }

    /**
     * Apply the previewed change set — ATOMICALLY, and ONLY if it still matches what was previewed.
     *
     * ⚠️ THE DIGEST CHECK RUNS INSIDE THE TRANSACTION, so the recomputed picture cannot go stale between the
     * check and the writes. A mismatch throws, the transaction rolls back, and nothing at all is written.
     *
     * @param  list<string>  $unitIds
     *
     * @throws PreviewChangedException when the world moved since the operator approved
     */
    public function commit(RatePlan $plan, array $unitIds, string $start, string $endExclusive, string $mode, ?int $priceMinor, string $expectedDigest): array
    {
        return DB::transaction(function () use ($plan, $unitIds, $start, $endExclusive, $mode, $priceMinor, $expectedDigest): array {
            $preview = $this->preview($plan, $unitIds, $start, $endExclusive, $mode, $priceMinor);

            if (! hash_equals($preview['digest'], $expectedDigest)) {
                // ⚠️ REFUSE, DON'T RECONCILE. Writing "the new correct thing" would apply figures the
                // operator never saw — exactly the mass-mispricing this whole flow exists to prevent.
                throw new PreviewChangedException;
            }

            $dates = $this->nights($start, $endExclusive);
            $currency = (string) $plan->currency;
            $applied = 0;

            foreach ($preview['units'] as $unit) {
                $unitId = (string) $unit['unit_id'];

                if ($mode === self::MODE_CLEAR) {
                    // ⚠️ REMOVE the rows — the units fall back to the type rate through NightlyRate.
                    $applied += UnitRateOverride::query()
                        ->where('rate_plan_id', $plan->getKey())
                        ->where('unit_id', $unitId)
                        ->whereIn('date', $dates)
                        ->delete();

                    continue;
                }

                foreach ($dates as $date) {
                    UnitRateOverride::query()->updateOrCreate(
                        ['rate_plan_id' => (string) $plan->getKey(), 'unit_id' => $unitId, 'date' => $date],
                        ['price_minor' => $priceMinor, 'currency' => $currency],
                    );
                    $applied++;
                }
            }

            return [
                'applied' => $applied,
                'units' => count($preview['units']),
                'changing_units' => $preview['changing_units'],
                'mode' => $mode,
            ];
        });
    }

    /** The nights of a half-open range, as Y-m-d strings. */
    private function nights(string $start, string $endExclusive): array
    {
        $dates = [];
        $end = CarbonImmutable::parse($endExclusive)->startOfDay();

        for ($d = CarbonImmutable::parse($start)->startOfDay(); $d->lt($end); $d = $d->addDay()) {
            $dates[] = $d->toDateString();
        }

        return $dates;
    }

    /** @return array<string,int> date => type price */
    private function indexTypePrices(RatePlan $plan, array $dates): array
    {
        if ($dates === []) {
            return [];
        }

        return RateCalendar::query()
            ->where('rate_plan_id', $plan->getKey())
            ->whereIn('date', $dates)
            ->get(['date', 'price_minor'])
            ->mapWithKeys(fn ($r): array => [CarbonImmutable::parse($r->date)->toDateString() => (int) $r->price_minor])
            ->all();
    }

    /** @return array<string,array<string,int>> unit => date => override price */
    private function indexOverrides(RatePlan $plan, array $unitIds, array $dates): array
    {
        if ($dates === [] || $unitIds === []) {
            return [];
        }

        $index = [];
        UnitRateOverride::query()
            ->where('rate_plan_id', $plan->getKey())
            ->whereIn('unit_id', $unitIds)
            ->whereIn('date', $dates)
            ->get(['unit_id', 'date', 'price_minor'])
            ->each(function ($o) use (&$index): void {
                $index[(string) $o->unit_id][CarbonImmutable::parse($o->date)->toDateString()] = (int) $o->price_minor;
            });

        return $index;
    }

    /** NULL is a real state (an unpriced night) and must not collide with a price in the digest. */
    private function tupleValue(?int $value): string
    {
        return $value === null ? 'null' : (string) $value;
    }

    /** The contract: every promised tuple, plus what the operation itself claims to be. */
    private function digest(RatePlan $plan, string $mode, ?int $priceMinor, array $tuples): string
    {
        return hash('sha256', implode("\n", array_merge(
            [(string) $plan->getKey(), $mode, $this->tupleValue($priceMinor)],
            $tuples,
        )));
    }
}
