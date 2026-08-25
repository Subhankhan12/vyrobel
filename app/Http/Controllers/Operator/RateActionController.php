<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Livewire\Operator\RatesGrid;
use App\Models\RoomType;
use App\Services\RateAndRestrictionService;
use App\Services\RateBulkService;
use App\Services\RateDerivationService;
use App\Support\Money\Money;
use App\Support\Money\MoneyInput;
use App\Support\Operator\OperatorWorkspace;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Operator RATES + RESTRICTIONS write — the FIRST-EVER production caller of
 * {@see RateAndRestrictionService} (Phase 1 shipped the service with the schema, but only the ARI-push
 * wiring referenced it in a comment; nothing drove it). Driven from the styled {@see RatesGrid} via the
 * locked operator-mutation pattern: POST → controller → service → redirect (PRG), exactly as
 * {@see ReservationLifecycleController} and {@see MaintenanceActionController}.
 *
 * ⚠️ PER-ROOM-TYPE, NOT PER-UNIT (docs/WAVE4-INVENTORY.md STRUCT-1). The design draws a per-physical-unit
 * grid, but `rate_calendar` is keyed on `rate_plan_id` and a rate plan belongs to a `room_type` — a room
 * type's three units share one plan, so a "per-unit" edit would silently move all three. This screen edits
 * a room type's PRIMARY rate plan (its `default_rate_plan_id`, or its first active plan), which is what the
 * service actually persists.
 *
 * ⚠️ INTEGER MONEY (convention #3 / invariant #17). The € the operator types is parsed to integer minor
 * units by {@see parseMinorUnits()} using STRING arithmetic — never a float multiply, which would drift
 * (17556.9999… on 175.57). The Money value object then carries it; the service stores `price_minor`. No
 * pricing math happens client-side.
 *
 * TENANT + PERMISSION. A normal POST re-runs the web middleware (SetTenantForWebRequest sets context), so
 * `{room_type}` is resolved through the `BelongsToOrganization` global scope AFTER the middleware (a
 * cross-org id 404s), and `/app` sits behind EnsureBackOfficeAccess (#26). The route additionally carries
 * `can:rates.manage` (#30) — held by Property Owner / Group Admin / Hotel Manager / Revenue Manager, and
 * deliberately NOT Front Desk or Reservation Agent (who hold at most `rates.view`). {@see RatesGrid} gates
 * the editor controls off the SAME permission so the UI never offers a write the route will 403.
 */
class RateActionController extends Controller
{
    /** A rate cannot exceed this (minor units) — €1,000,000.00 — a sanity bound on a hand-typed field. */
    private const MAX_PRICE_MINOR = 100_000_000;

    /** Longest span a single apply may cover, in nights — a guard on the per-night upsert loop. */
    private const MAX_SPAN_NIGHTS = 370;

    /**
     * Apply a rate and/or restriction change over a date range, to ONE room type or to ALL of the tenant's
     * room types. The single-cell editor is just start == end (one night); the bulk panel is a wider range.
     */
    public function apply(Request $request, RateAndRestrictionService $rates, RateDerivationService $derivation): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly($request)) {
            return $readOnly;
        }

        $fromParam = $this->dateString($request->input('start_date'));
        $toParam = $this->dateString($request->input('end_date'));

        if ($fromParam === null || $toParam === null) {
            return $this->back($fromParam, 'Choose valid start and end dates.');
        }

        $start = CarbonImmutable::parse($fromParam)->startOfDay();
        $endInclusive = CarbonImmutable::parse($toParam)->startOfDay();

        if ($endInclusive->lt($start)) {
            return $this->back($fromParam, 'The end date can’t be before the start date.');
        }

        // The service takes a half-open [start, end) range; the operator's "end" is the last night they mean
        // to include, so the exclusive bound is one day later.
        $endExclusive = $endInclusive->addDay();
        $nights = (int) $start->diffInDays($endExclusive);

        if ($nights > self::MAX_SPAN_NIGHTS) {
            return $this->back($fromParam, 'That date range is too long — please apply at most a year at a time.');
        }

        $setRate = $request->boolean('set_rate');
        $setMinStay = $request->boolean('set_min_stay');
        $setStopSell = $request->boolean('set_stop_sell');
        // ⚠️ THE THREE THAT COULD BE STORED, PUSHED AND ENFORCED BUT NEVER SET. `max_stay`,
        //    `closed_to_arrival` and `closed_to_departure` all had columns, an ARI push and (as of the
        //    enforcement pass) a booking-time check — and NO WRITER. ⚠️ Enforcing something nobody can set
        //    is the exact defect this sequence exists to remove, so they are written HERE, on the same
        //    surface, through the SAME `setRestriction()` and keyed the same way, so the engine and the
        //    rate-grid badge keep reading one row.
        $setMaxStay = $request->boolean('set_max_stay');
        $setCta = $request->boolean('set_closed_to_arrival');
        $setCtd = $request->boolean('set_closed_to_departure');

        if (! $setRate && ! $setMinStay && ! $setStopSell) {
            return $this->back($fromParam, 'Choose at least one thing to set — a nightly rate, a minimum stay, or open/close.');
        }

        // Parse money BEFORE touching the gateway of the DB — an invalid price must reject cleanly, never
        // half-apply a restriction and then fail on the rate.
        $priceMinor = null;
        if ($setRate) {
            $priceMinor = MoneyInput::toMinor((string) $request->input('rate', ''), self::MAX_PRICE_MINOR);
            if ($priceMinor === null) {
                return $this->back($fromParam, 'Enter a valid nightly rate, e.g. 175 or 175.50.');
            }
        }

        // min_stay: an explicit value ≥ 1, or blank meaning "no minimum" (null clears the column).
        $minStay = null;
        if ($setMinStay) {
            $raw = trim((string) $request->input('min_stay', ''));
            if ($raw !== '') {
                if (! ctype_digit($raw) || (int) $raw < 1 || (int) $raw > 365) {
                    return $this->back($fromParam, 'A minimum stay must be a whole number of nights (1–365), or blank for none.');
                }
                $minStay = (int) $raw;
            }
        }

        // max_stay: an explicit value >= 1, or blank meaning "no maximum" (null clears the column) — the
        // same shape as min_stay, deliberately, so the two mirrors cannot drift apart.
        $maxStay = null;
        if ($setMaxStay) {
            $raw = trim((string) $request->input('max_stay', ''));
            if ($raw !== '') {
                if (! ctype_digit($raw) || (int) $raw < 1 || (int) $raw > 365) {
                    return $this->back($fromParam, 'A maximum stay must be a whole number of nights (1–365), or blank for none.');
                }
                $maxStay = (int) $raw;
            }
        }

        if ($setMinStay && $setMaxStay && $minStay !== null && $maxStay !== null && $maxStay < $minStay) {
            // ⚠️ A maximum below the minimum closes those nights to every possible stay. Refused here
            //    rather than written, because the calendar would look set and sell nothing.
            return $this->back($fromParam, 'A maximum stay cannot be shorter than the minimum stay.');
        }

        $stopSell = $setStopSell && $request->boolean('stop_sell');
        $closedToArrival = $setCta && $request->boolean('closed_to_arrival');
        $closedToDeparture = $setCtd && $request->boolean('closed_to_departure');

        $roomTypes = $this->resolveRoomTypes($request->input('room_type_id'));
        if ($roomTypes === []) {
            return $this->back($fromParam, 'Choose a room type to update.');
        }

        $updateDerived = $request->boolean('update_derived');
        $applied = 0;
        $missingPlan = 0;

        foreach ($roomTypes as $roomType) {
            if ($setRate) {
                $plan = $roomType->primaryRatePlan();
                if ($plan === null) {
                    // A room type with no rate plan has nowhere to store a price — report it rather than
                    // silently skipping, so the operator knows the rate did not land.
                    $missingPlan++;
                } else {
                    $rates->setRate($plan, $start, $endExclusive, Money::of($priceMinor, (string) $plan->currency));
                    // #100 — recompute Non-refundable/Weekly derived plans (base × adjustment) so they track.
                    if ($updateDerived) {
                        $derivation->recomputeDerived($plan, $start, $endExclusive);
                    }
                }
            }

            if ($setMinStay || $setStopSell || $setMaxStay || $setCta || $setCtd) {
                $attributes = ['room_type_id' => (string) $roomType->getKey()];
                if ($setMinStay) {
                    $attributes['min_stay'] = $minStay;   // null clears it
                }
                if ($setMaxStay) {
                    $attributes['max_stay'] = $maxStay;   // null clears it
                }
                if ($setStopSell) {
                    $attributes['stop_sell'] = $stopSell;
                }
                if ($setCta) {
                    $attributes['closed_to_arrival'] = $closedToArrival;
                }
                if ($setCtd) {
                    $attributes['closed_to_departure'] = $closedToDeparture;
                }
                $rates->setRestriction($attributes, $start, $endExclusive);
            }

            $applied++;
        }

        return $this->back($fromParam, $this->summary($applied, $nights, $missingPlan, $setRate, $setMinStay, $setStopSell));
    }

    /**
     * BULK edit (task #100) — an increase% / decrease% / set-exact adjustment across a set of room types over
     * a date range, restricted to a nights filter (whole/weekends/midweek), skipping booked & closed nights,
     * optionally recomputing derived plans. Writes through the certified {@see RateBulkService}. `rates.manage`.
     */
    public function bulkApply(Request $request, RateBulkService $bulk): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly($request)) {
            return $readOnly;
        }

        $fromParam = $this->dateString($request->input('start_date'));
        $toParam = $this->dateString($request->input('end_date'));
        if ($fromParam === null || $toParam === null) {
            return $this->back($fromParam, 'Choose valid start and end dates.');
        }

        $start = CarbonImmutable::parse($fromParam)->startOfDay();
        $endInclusive = CarbonImmutable::parse($toParam)->startOfDay();
        if ($endInclusive->lt($start)) {
            return $this->back($fromParam, 'The end date can’t be before the start date.');
        }
        $endExclusive = $endInclusive->addDay();
        if ((int) $start->diffInDays($endExclusive) > self::MAX_SPAN_NIGHTS) {
            return $this->back($fromParam, 'That date range is too long — please apply at most a year at a time.');
        }

        $mode = (string) $request->input('mode');
        if (! in_array($mode, RateBulkService::MODES, true)) {
            return $this->back($fromParam, 'Choose increase %, decrease %, or set exact €.');
        }
        $nights = (string) $request->input('nights', 'whole');
        if (! in_array($nights, RateBulkService::NIGHTS, true)) {
            $nights = 'whole';
        }

        $pct = 0.0;
        $exactMinor = null;
        if ($mode === 'set_exact') {
            $exactMinor = MoneyInput::toMinor((string) $request->input('amount', ''), self::MAX_PRICE_MINOR);
            if ($exactMinor === null) {
                return $this->back($fromParam, 'Enter a valid exact rate, e.g. 175 or 175.50.');
            }
        } else {
            $raw = trim((string) $request->input('amount', ''));
            if (! is_numeric($raw) || (float) $raw <= 0 || (float) $raw > 100) {
                return $this->back($fromParam, 'Enter a percentage between 0 and 100.');
            }
            $pct = (float) $raw;
        }

        $roomTypes = $this->resolveRoomTypes($request->input('room_type_id'));
        if ($roomTypes === []) {
            return $this->back($fromParam, 'Choose which units to update.');
        }

        $result = $bulk->apply(
            $roomTypes, $start, $endExclusive, $mode, $pct, $exactMinor, $nights,
            $request->boolean('skip_booked', true), $request->boolean('update_derived'),
        );

        $msg = 'Updated '.$result['applied'].' '.($result['applied'] === 1 ? 'night' : 'nights')
            .' across '.$result['room_types'].' '.($result['room_types'] === 1 ? 'room type' : 'room types').'.';
        if ($result['skipped'] > 0) {
            $msg .= ' '.$result['skipped'].' booked/closed skipped.';
        }
        if ($result['derived'] > 0) {
            $msg .= ' '.$result['derived'].' derived plans updated.';
        }

        return $this->back($fromParam, $msg);
    }

    /** Read-only guard (task #100 — was missing on the rate write, a gap vs peer controllers). */
    private function guardReadOnly(Request $request): ?RedirectResponse
    {
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->back($this->dateString($request->input('start_date')), 'Your workspace is read-only right now — rate changes are paused.');
        }

        return null;
    }

    /**
     * The room types to apply to: a single tenant-scoped id, or every room type in the tenant when the
     * form submits `all`. `findOrFail` runs through the BelongsToOrganization global scope, so a cross-org
     * id 404s rather than leaking.
     *
     * @return list<RoomType>
     */
    private function resolveRoomTypes(mixed $roomTypeId): array
    {
        if ($roomTypeId === 'all') {
            return RoomType::query()->orderBy('name')->get()->all();
        }

        if (! is_string($roomTypeId) || $roomTypeId === '') {
            return [];
        }

        return [RoomType::query()->findOrFail($roomTypeId)];
    }

    /** A Y-m-d string from request input, or null if it is not a real calendar date. */
    private function dateString(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y) ? $value : null;
    }

    private function summary(int $applied, int $nights, int $missingPlan, bool $rate, bool $minStay, bool $stopSell): string
    {
        $what = array_filter([
            $rate ? 'rate' : null,
            $minStay ? 'minimum stay' : null,
            $stopSell ? 'availability' : null,
        ]);

        $roomTypes = $applied.' '.($applied === 1 ? 'room type' : 'room types');
        $nightsLabel = $nights.' '.($nights === 1 ? 'night' : 'nights');
        $message = 'Updated '.implode(' + ', $what).' for '.$roomTypes.' across '.$nightsLabel.'.';

        if ($missingPlan > 0) {
            // ⚠️ P-6 · Gate 1 — this reported the state and offered no remedy, because none existed. There
            // is a rate-plan form on this page now, so the message says where to go.
            $message .= ' '.$missingPlan.' had no rate plan and were skipped for the rate — add one below.';
        }

        return $message;
    }

    /** PRG back to the rates grid, preserving the window, using the ONLY flash key that view renders. */
    private function back(?string $from, string $notice): RedirectResponse
    {
        $params = $from !== null ? ['from' => $from] : [];

        return redirect()->to(route('app.rates', $params))->with('rates_notice', $notice);
    }
}
