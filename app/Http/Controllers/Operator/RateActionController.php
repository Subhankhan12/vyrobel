<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Livewire\Operator\RatesGrid;
use App\Models\RoomType;
use App\Services\RateAndRestrictionService;
use App\Services\RateBulkService;
use App\Services\RateDerivationService;
use App\Support\I18n\ListJoin;
use App\Support\I18n\LocalDate;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use App\Support\Money\MoneyInput;
use App\Support\Operator\CalendarReturn;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Rates\RateScope;
use App\Support\Rates\RatesListParams;
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

    /** `return` when the price is set from the New reservation wizard ({@see toWizard()}). */
    public const RETURN_WIZARD = 'new-reservation';

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
            return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.invalid_dates'));
        }

        $start = CarbonImmutable::parse($fromParam)->startOfDay();
        $endInclusive = CarbonImmutable::parse($toParam)->startOfDay();

        if ($endInclusive->lt($start)) {
            return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.end_before_start'));
        }

        // The service takes a half-open [start, end) range; the operator's "end" is the last night they mean
        // to include, so the exclusive bound is one day later.
        $endExclusive = $endInclusive->addDay();
        $nights = (int) $start->diffInDays($endExclusive);

        if ($nights > self::MAX_SPAN_NIGHTS) {
            return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.range_too_long'));
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

        // ⚠️ 2026-09-30 — max stay and closed to arrival/departure were written below but could never be set ON THEIR OWN:
        //    this check ignored them, so a save of just one of those rules was refused. Each rule the grid shows can be set.
        if (! $setRate && ! $setMinStay && ! $setStopSell && ! $setMaxStay && ! $setCta && ! $setCtd) {
            return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.nothing_to_set'));
        }

        // Parse money BEFORE touching the gateway of the DB — an invalid price must reject cleanly, never
        // half-apply a restriction and then fail on the rate.
        $priceMinor = null;
        if ($setRate) {
            $priceMinor = MoneyInput::toMinor((string) $request->input('rate', ''), self::MAX_PRICE_MINOR);
            if ($priceMinor === null) {
                return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.invalid_rate'));
            }
        }

        // min_stay: an explicit value ≥ 1, or blank meaning "no minimum" (null clears the column).
        $minStay = null;
        if ($setMinStay) {
            $raw = trim((string) $request->input('min_stay', ''));
            if ($raw !== '') {
                if (! ctype_digit($raw) || (int) $raw < 1 || (int) $raw > 365) {
                    return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.invalid_min_stay'));
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
                    return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.invalid_max_stay'));
                }
                $maxStay = (int) $raw;
            }
        }

        if ($setMinStay && $setMaxStay && $minStay !== null && $maxStay !== null && $maxStay < $minStay) {
            // ⚠️ A maximum below the minimum closes those nights to every possible stay. Refused here
            //    rather than written, because the calendar would look set and sell nothing.
            return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.max_below_min'));
        }

        $stopSell = $setStopSell && $request->boolean('stop_sell');
        $closedToArrival = $setCta && $request->boolean('closed_to_arrival');
        $closedToDeparture = $setCtd && $request->boolean('closed_to_departure');

        $roomTypes = $this->resolveRoomTypes($request->input('room_type_id'));
        if ($roomTypes === []) {
            return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.choose_room_type'));
        }

        // ⚠️ 2026-10-01 — A CHOSEN PLAN. The grid prices each room type's MAIN plan, so a second plan could be added and never
        //    priced. The editor may now name one: a base plan of the ONE room type chosen, inside the person's properties
        //    (DEC-l). A derived plan follows its base and is never priced directly.
        $chosenPlan = null;
        $planId = $request->input('rate_plan_id');
        if (is_string($planId) && $planId !== '') {
            if (count($roomTypes) !== 1 || $request->input('room_type_id') === 'all') {
                return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.one_room_type_for_plan'));
            }
            $chosenPlan = app(RateScope::class)->ratePlan($planId);
            if ((string) $chosenPlan->room_type_id !== (string) $roomTypes[0]->getKey()) {
                return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.plan_other_room_type'));
            }
            if ($chosenPlan->derived_from_rate_plan_id !== null) {
                return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.derived_plan_follows_base'));
            }
        }

        $updateDerived = $request->boolean('update_derived');
        $applied = 0;
        $missingPlan = 0;

        foreach ($roomTypes as $roomType) {
            if ($setRate) {
                $plan = $chosenPlan ?? $roomType->primaryRatePlan();
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

        // ⚠️ 2026-10-04 — THE NEW RESERVATION WIZARD SETS A MISSING PRICE HERE, through these same rules, and goes back to the
        //    booking it came from: "Set a rate first →" used to leave the wizard and lose the booking. It sends one room type,
        //    the stay's nights and a nightly price — so the answer names exactly that.
        //    ⚠️ "Pick a unit" only when the price went on the plan the wizard prices a stay with (the room type's main plan) —
        //    a hand-made request naming another plan gets the plain summary below, still back in the wizard.
        //    ⚠️ 2026-10-04 (his next report, the Calendar) — the Calendar's New reservation dialog sets it here too, and goes back
        //    INTO the dialog on the same stay ({@see CalendarReturn}).
        if ($this->fromBooking($request) && $setRate && count($roomTypes) === 1) {
            $primary = $roomTypes[0]->primaryRatePlan();
            $plan = $chosenPlan ?? $primary;
            if ($plan === null) {
                return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.no_rate_plan_yet', ['room_type' => $roomTypes[0]->name]));
            }
            if ($primary !== null && (string) $plan->getKey() === (string) $primary->getKey()) {
                // ⚠️ one night is "the night of 2 Sep" — never "these 1 night" (2026-10-04)
                $price = MoneyFormatter::display($priceMinor, (string) $plan->currency);
                $set = $nights === 1
                    ? __('php/http/controllers/operator/rate_action_controller.price_set_one_night', ['price' => $price, 'date' => $start->translatedFormat(LocalDate::pattern('j M'))])
                    : trans_choice('php/http/controllers/operator/rate_action_controller.price_set_nights', $nights, ['price' => $price, 'count' => $nights]);

                // the wizard says what comes next; the Calendar's dialog says it itself, from what it shows — a unit may already be
                // picked (🔬 the walk: "Pick a unit to continue" above the unit that was picked)
                return $this->backToBooking($request, CalendarReturn::wanted($request) ? $set : $set.' '.__('php/http/controllers/operator/rate_action_controller.pick_unit_to_continue'), refused: false);
            }
        }

        // keyed by a code, never by the translated phrase (two phrases that translate alike would collapse)
        return $this->done($request, $fromParam, $this->summary($applied, $nights, $missingPlan, [
            'rate' => $setRate,
            'min_stay' => $setMinStay, 'max_stay' => $setMaxStay,
            'stop_sell' => $setStopSell, 'cta' => $setCta, 'ctd' => $setCtd,
        ], $chosenPlan?->name));
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
            return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.invalid_dates'));
        }

        $start = CarbonImmutable::parse($fromParam)->startOfDay();
        $endInclusive = CarbonImmutable::parse($toParam)->startOfDay();
        if ($endInclusive->lt($start)) {
            return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.end_before_start'));
        }
        $endExclusive = $endInclusive->addDay();
        if ((int) $start->diffInDays($endExclusive) > self::MAX_SPAN_NIGHTS) {
            return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.range_too_long'));
        }

        $mode = (string) $request->input('mode');
        if (! in_array($mode, RateBulkService::MODES, true)) {
            return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.choose_bulk_mode'));
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
                return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.invalid_exact_rate'));
            }
        } else {
            $raw = trim((string) $request->input('amount', ''));
            if (! is_numeric($raw) || (float) $raw <= 0 || (float) $raw > 100) {
                return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.invalid_percentage'));
            }
            // ⚠️⚠️ MONEY (2026-09-30) — a 100% decrease was accepted and wrote €0 nights: a room given away. The single
            //    editor has always refused a price of zero; the bulk path now refuses the percentage that makes one.
            if ($mode === 'decrease_pct' && (float) $raw >= 100) {
                return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.decrease_too_large'));
            }
            $pct = (float) $raw;
        }

        $roomTypes = $this->resolveRoomTypes($request->input('room_type_id'));
        if ($roomTypes === []) {
            return $this->refuse($request, $fromParam, __('php/http/controllers/operator/rate_action_controller.choose_room_types'));
        }

        $result = $bulk->apply(
            $roomTypes, $start, $endExclusive, $mode, $pct, $exactMinor, $nights,
            $request->boolean('skip_booked', true), $request->boolean('update_derived'),
        );

        $msg = __('php/http/controllers/operator/rate_action_controller.bulk_updated', [
            'nights' => trans_choice('php/http/controllers/operator/rate_action_controller.nights_count', $result['applied'], ['count' => $result['applied']]),
            'room_types' => trans_choice('php/http/controllers/operator/rate_action_controller.room_types_count', $result['room_types'], ['count' => $result['room_types']]),
        ]);
        if ($result['skipped'] > 0) {
            $msg .= ' '.trans_choice('php/http/controllers/operator/rate_action_controller.bulk_skipped', $result['skipped'], ['count' => $result['skipped']]);
        }
        if ($result['too_low'] > 0) {
            $msg .= ' '.trans_choice('php/http/controllers/operator/rate_action_controller.bulk_too_low', $result['too_low'], ['count' => $result['too_low']]);
        }
        if ($result['too_high'] > 0) {
            $msg .= ' '.trans_choice('php/http/controllers/operator/rate_action_controller.bulk_too_high', $result['too_high'], ['count' => $result['too_high']]);
        }
        if ($result['derived'] > 0) {
            $msg .= ' '.trans_choice('php/http/controllers/operator/rate_action_controller.bulk_derived', $result['derived'], ['count' => $result['derived']]);
        }

        return $this->done($request, $fromParam, $msg);
    }

    /** Read-only guard (task #100 — was missing on the rate write, a gap vs peer controllers). */
    private function guardReadOnly(Request $request): ?RedirectResponse
    {
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->refuse($request, $this->dateString($request->input('start_date')), __('php/http/controllers/operator/rate_action_controller.read_only'));
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
        // ⚠️⚠️ DEC-l (2026-09-30) — through the ONE rule: "all" is the room types ON SCREEN, never every room type in the
        //    organisation (a bulk change made on one property silently repriced the others), and a single id outside the
        //    person's properties is not found.
        if ($roomTypeId === 'all') {
            return app(RateScope::class)->roomTypes()->orderBy('name')->get()->all();
        }

        if (! is_string($roomTypeId) || $roomTypeId === '') {
            return [];
        }

        return [app(RateScope::class)->roomType($roomTypeId)];
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

    /** @param  array<string, bool>  $set  what this save set, by code (rate · min_stay · max_stay · stop_sell · cta · ctd) */
    private function summary(int $applied, int $nights, int $missingPlan, array $set, ?string $planName = null): string
    {
        $what = array_map(fn (string $code): string => match ($code) {
            'rate' => $planName !== null
                ? __('php/http/controllers/operator/rate_action_controller.what_rate_on_plan', ['plan' => $planName])
                : __('php/http/controllers/operator/rate_action_controller.what_rate'),
            'min_stay' => __('php/http/controllers/operator/rate_action_controller.what_min_stay'),
            'max_stay' => __('php/http/controllers/operator/rate_action_controller.what_max_stay'),
            'stop_sell' => __('php/http/controllers/operator/rate_action_controller.what_stop_sell'),
            'cta' => __('php/http/controllers/operator/rate_action_controller.what_cta'),
            'ctd' => __('php/http/controllers/operator/rate_action_controller.what_ctd'),
            default => $code,
        }, array_keys(array_filter($set)));

        $roomTypes = trans_choice('php/http/controllers/operator/rate_action_controller.room_types_count', $applied, ['count' => $applied]);
        $nightsLabel = trans_choice('php/http/controllers/operator/rate_action_controller.nights_count', $nights, ['count' => $nights]);
        // Plain English — "Updated the nightly price and the minimum stay …", never "rate + minimum stay" (2026-10-01).
        $last = array_pop($what);
        $list = $what === [] ? (string) $last : __('php/http/controllers/operator/rate_action_controller.list_and', ['items' => ListJoin::join($what), 'last' => (string) $last]);
        $message = __('php/http/controllers/operator/rate_action_controller.summary_updated', ['what' => $list, 'room_types' => $roomTypes, 'nights' => $nightsLabel]);

        if ($missingPlan > 0) {
            // ⚠️ P-6 · Gate 1 — this reported the state and offered no remedy, because none existed. There
            // is a rate-plan form on this page now, so the message says where to go.
            $message .= ' '.trans_choice('php/http/controllers/operator/rate_action_controller.missing_plan_skipped', $missingPlan, ['count' => $missingPlan]);
        }

        return $message;
    }

    /**
     * A refusal — shown on the page as an ALERT (`rates_error`, `role="alert"`). ⚠️ Until 2026-09-30 refusals and
     * successes shared `rates_notice`, so an error never announced itself.
     */
    private function refuse(Request $request, ?string $from, string $message): RedirectResponse
    {
        if ($this->fromBooking($request)) {
            return $this->backToBooking($request, $message, refused: true);
        }

        return RatesListParams::redirect($request, $from)->with('rates_error', $message);
    }

    /** Done — shown as a notice, back on the page as the operator had it ({@see RatesListParams}). */
    private function done(Request $request, ?string $from, string $message): RedirectResponse
    {
        if ($this->fromBooking($request)) {
            return $this->backToBooking($request, $message, refused: false);
        }

        return RatesListParams::redirect($request, $from)->with('rates_notice', $message);
    }

    /** The price was set from inside the New reservation wizard — a fixed value, never a URL (an open redirect). */
    private function returnsToWizard(Request $request): bool
    {
        return $request->input('return') === self::RETURN_WIZARD;
    }

    /** Set from inside a booking — the New reservation wizard, or the Calendar's New reservation dialog. */
    private function fromBooking(Request $request): bool
    {
        return $this->returnsToWizard($request) || CalendarReturn::wanted($request);
    }

    /**
     * Back to the booking it came from, saying what happened THERE: inside the Calendar's dialog on the same stay, or the
     * wizard on the same dates. A refusal is the page's alert, a success its status line.
     */
    private function backToBooking(Request $request, string $message, bool $refused): RedirectResponse
    {
        if (CalendarReturn::wanted($request)) {
            return redirect()->to(CalendarReturn::dialog($request))->with($refused ? 'calendar_dialog_error' : 'calendar_dialog_ok', $message);
        }

        return $this->toWizard($request)->with($refused ? 'new_reservation_notice' : 'new_reservation_ok', $message);
    }

    /** Back to the wizard on the same dates — each one a real calendar date or left out. */
    private function toWizard(Request $request): RedirectResponse
    {
        $dates = array_filter([
            'check_in' => $this->dateString($request->input('return_check_in')),
            'check_out' => $this->dateString($request->input('return_check_out')),
        ], fn (?string $v): bool => $v !== null);

        return redirect()->to(route('app.reservations.create', $dates));
    }
}
