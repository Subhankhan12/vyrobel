<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\RateCalendar;
use App\Models\RateSeason;
use App\Services\SeasonalRateService;
use App\Support\Events\AuditEventRecorder;
use App\Support\Money\MoneyInput;
use App\Support\Operator\CalendarReturn;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Rates\RateScope;
use App\Support\Rates\RatesListParams;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * OWNER FIX 4b — defining and APPLYING a seasonal ("peak") price.
 *
 * ⚠️ TWO STEPS ON PURPOSE, the same shape the per-unit override uses. `store` records the season and writes
 * NO price; `apply` materializes it into `rate_calendar` through the certified writer. Defining a season is
 * therefore free, and only the deliberate second action moves money.
 *
 * ⚠️ BOTH ARE `rates.manage`-GATED AT THE ROUTE (#30) — applying overwrites real nightly prices for a whole
 * range, which is squarely a revenue action and deliberately not Front Desk's.
 *
 * ⚠️ §G.1 — the price is parsed with {@see MoneyInput::toMinor()}: a FINAL, VAT-inclusive figure in integer
 * minor units, never a float and never a pre-VAT base.
 */
class SeasonActionController extends Controller
{
    /** A season price cannot exceed this (minor units) — €1,000,000.00, the ceiling every price field on the page shares. */
    private const MAX_PRICE_MINOR = 100_000_000;

    public function __construct(private AuditEventRecorder $audit) {}

    /**
     * Plain words for every refusal the two season forms can meet (2026-10-01) — Laravel's defaults read "The ends on field
     * must be a date after or equal to starts on." Shared by save and edit so the two cannot word one rule two ways.
     *
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'rate_plan_id.required' => __('php/http/controllers/operator/season_action_controller.choose_rate_plan'),
            'name.required' => __('php/http/controllers/operator/season_action_controller.name_required'),
            'name.max' => __('php/http/controllers/operator/season_action_controller.name_too_long'),
            'starts_on.required' => __('php/http/controllers/operator/season_action_controller.choose_first_night'),
            'starts_on.date' => __('php/http/controllers/operator/season_action_controller.choose_first_night'),
            'ends_on.required' => __('php/http/controllers/operator/season_action_controller.choose_last_night'),
            'ends_on.date' => __('php/http/controllers/operator/season_action_controller.choose_last_night'),
            'ends_on.after_or_equal' => __('php/http/controllers/operator/season_action_controller.last_before_first'),
            'price.required' => __('php/http/controllers/operator/season_action_controller.price_required'),
        ];
    }

    /**
     * ⚠️⚠️ THE WORKSPACE PAUSE (2026-09-30). Every other rate write refused a paused workspace; this controller did not, so a
     * paused workspace — which the shell tells the operator is read-only — could still SAVE and APPLY a season, and applying
     * writes `rate_calendar`. Now all four season writes refuse it, with the same sentence as the rest of the page.
     */
    private function paused(Request $request): ?RedirectResponse
    {
        if (! app(OperatorWorkspace::class)->isReadOnly()) {
            return null;
        }

        $message = __('php/http/controllers/operator/season_action_controller.read_only');

        if (CalendarReturn::wanted($request)) {
            return redirect()->to(CalendarReturn::dialog($request))->with('calendar_dialog_error', $message);
        }

        return $this->fromWizard($request)
            ? $this->toWizard($request)->with('new_reservation_notice', $message)
            : $this->toPage($request)->with('rates_error', $message);
    }

    /** Back to the Rates page as the operator had it when started there ({@see RatesListParams}); otherwise back. */
    private function toPage(Request $request): RedirectResponse
    {
        return $request->input('return') === 'list' ? RatesListParams::redirect($request) : back();
    }

    /**
     * ⚠️ 2026-10-04 — APPLY, FROM INSIDE A BOOKING. On production the founder's €200 "Winter" season was saved and never
     * applied, so the New reservation page said there was no price. That page now offers this same Apply — and comes back to
     * it on the same dates, as the Rates write does for a price set there ({@see RateActionController::toWizard()}).
     * `return` is a fixed token, never a URL; the way back is built from two checked dates.
     */
    private function fromWizard(Request $request): bool
    {
        return $request->input('return') === RateActionController::RETURN_WIZARD;
    }

    private function toWizard(Request $request): RedirectResponse
    {
        $dates = array_filter([
            'check_in' => $this->dateString($request->input('return_check_in')),
            'check_out' => $this->dateString($request->input('return_check_out')),
        ], fn (?string $v): bool => $v !== null);

        return redirect()->to(route('app.reservations.create', $dates));
    }

    private function dateString(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y) ? $value : null;
    }

    /**
     * ⚠️ F-22 — the shape of a season's audit row. Identifiers, dates and an INTEGER minor-unit price:
     * no names, no contact details, nothing personal (these rows are operator-readable). Used for the
     * `before` of an edit and a delete as well as the `after` of a create, so one definition cannot drift.
     *
     * @return array<string, mixed>
     */
    private function seasonState(RateSeason $season): array
    {
        return [
            'rate_plan_id' => (string) $season->rate_plan_id,
            'name' => (string) $season->name,
            'starts_on' => (string) $season->starts_on,
            'ends_on' => (string) $season->ends_on,
            'price_minor' => (int) $season->price_minor,
            'currency' => (string) $season->currency,
            'applied_at' => $season->applied_at?->toDateTimeString(),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        if ($refused = $this->paused($request)) {
            return $refused;
        }

        $data = $request->validate([
            'rate_plan_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:80'],
            'starts_on' => ['required', 'date'],
            // ⚠️ INCLUSIVE, and it must not precede the start — a backwards range would write nothing at all
            // and read as "applied" afterwards.
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'price' => ['required', 'string'],
        ], $this->messages());

        // ⚠️ DEC-l (2026-09-30) — inside the person's properties, not just the organisation.
        $plan = app(RateScope::class)->ratePlan($data['rate_plan_id']);

        $minor = MoneyInput::toMinor($data['price'], self::MAX_PRICE_MINOR);
        if ($minor === null || $minor <= 0) {
            return back()->withErrors(['price' => __('php/http/controllers/operator/season_action_controller.price_out_of_range')])->withInput();
        }

        $season = RateSeason::create([
            'rate_plan_id' => (string) $plan->getKey(),
            'name' => $data['name'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'price_minor' => $minor,
            'currency' => (string) $plan->currency,
        ]);

        // ⚠️ F-22 — `before` is genuinely null here: the season did not exist. Honest null, not a placeholder.
        $this->audit->record('rate_season.created', $season, null, $this->seasonState($season));

        return $this->toPage($request)->with('rates_notice', __('php/http/controllers/operator/season_action_controller.season_saved'));
    }

    /**
     * Edit a season — ⚠️⚠️ ONLY WHILE IT IS STILL A DRAFT.
     *
     * ⚠️⚠️ AN APPLIED SEASON CANNOT BE EDITED HONESTLY, AND THAT IS A CONCLUSION, NOT A LIMITATION OF
     * EFFORT. Applying WRITES nightly rows through `RateAndRestrictionService::setRate()`, which does
     * `updateOrCreate(..., ['price_minor' => …])` — the previous price is overwritten and **nothing
     * anywhere records what it was** (the audit row's `before` is literally `null`). Editing an applied
     * season is therefore two operations — un-apply the old shape, apply the new — and the first is
     * impossible: there is no "before" to restore. Measured, not assumed; see docs/SEASON-EDIT-DELETE.md.
     *
     * ⚠️ SO AN APPLIED SEASON IS SUPERSEDED, NEVER EDITED. Authoring a new season over the same nights is
     * honest — it writes what it says it writes — whereas an "edit" would imply the old rates came back.
     *
     * ⚠️ AND EDITING DOES NOT APPLY. The create path is two-step on purpose: the blast radius is shown
     * BEFORE it lands. This saves the draft and returns to the preview, exactly as `store()` does — an
     * edit that wrote immediately would destroy the safety the create path was built to have.
     */
    public function update(Request $request, string $season): RedirectResponse
    {
        $model = app(RateScope::class)->season($season);   // DEC-l — inside the person's properties

        if ($refused = $this->paused($request)) {
            return $refused;
        }

        if ($model->applied_at !== null) {
            // ⚠️ The error key `season` is the contract (SeasonEditDeleteTest) — and since 2026-10-01 the page SHOWS it, as an
            //    alert at the top. Until then only `price` was rendered, so this refusal arrived silently.
            return $this->toPage($request)->withErrors([
                'season' => __('php/http/controllers/operator/season_action_controller.already_applied'),
            ]);
        }

        // ⚠️ The edit happens in a dialog that is closed again by the redirect, so its refusal is an ALERT at the top of the
        //    page — as an error bag it would print under the SAVE form, beside fields the operator never touched.
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:80'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'price' => ['required', 'string'],
        ], $this->messages());
        if ($validator->fails()) {
            return $this->toPage($request)->with('rates_error', (string) $validator->errors()->first());
        }
        $data = $validator->validated();

        $minor = MoneyInput::toMinor($data['price'], self::MAX_PRICE_MINOR);

        if ($minor === null || $minor <= 0) {
            return $this->toPage($request)->with('rates_error', __('php/http/controllers/operator/season_action_controller.price_out_of_range'));
        }

        // ⚠️ CAPTURED BEFORE THE WRITE. Phase 11 found 11 of 11 rate-path call sites passing `before => null`,
        // which is precisely why an undo cannot be built there. Here the previous values are already loaded,
        // so there is no excuse for a null — an edit records what it changed FROM as well as TO.
        $before = $this->seasonState($model);

        $model->forceFill([
            'name' => $data['name'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'price_minor' => $minor,
        ])->save();

        $this->audit->record('rate_season.updated', $model, $before, $this->seasonState($model->refresh()));

        return $this->toPage($request)->with('rates_notice', __('php/http/controllers/operator/season_action_controller.season_updated'));
    }

    /**
     * Delete a season — ⚠️⚠️ WHICH IS NOT AN UNDO, AND THE MESSAGE SAYS SO.
     *
     * ⚠️ Deleting removes the season record. It does **not** revert nightly rates an applied season already
     * wrote: those live in `rate_calendar`, the season is not consulted at price time, and nothing recorded
     * what they replaced. Proven by acting — applying moved a night from €200.00 to €333.00 and deleting
     * the season left it at €333.00.
     *
     * ⚠️ A CONTROL MAY NOT IMPLY MORE THAN IT DOES, so the confirmation states the difference rather than
     * letting an operator infer that prices came back.
     *
     * ⚠️ HARD DELETE, DELIBERATELY. `RateSeason` carries no `SoftDeletes`, and adding it would repeat a
     * recorded defect exactly: a soft-deleted PLAN kept selling because a `withoutGlobalScopes()` call
     * dropped the soft-delete scope along with the tenant one. A season that is gone must be gone from
     * every query, including any that drops scopes.
     */
    public function destroy(Request $request, string $season): RedirectResponse
    {
        $model = app(RateScope::class)->season($season);   // DEC-l — inside the person's properties

        if ($refused = $this->paused($request)) {
            return $refused;
        }
        $wasApplied = $model->applied_at !== null;
        $name = (string) $model->name;

        // ⚠️ The row is about to be gone for good (hard delete, deliberately) — so the audit row is the ONLY
        // remaining record of what existed. Captured before the delete, and `after` is honestly null.
        $before = $this->seasonState($model);

        $model->delete();

        $this->audit->record('rate_season.deleted', $model, $before, null);

        return $this->toPage($request)->with('rates_notice', $wasApplied
            ? __('php/http/controllers/operator/season_action_controller.deleted_after_apply', ['season' => $name])
            : __('php/http/controllers/operator/season_action_controller.deleted_not_applied', ['season' => $name]));
    }

    /**
     * Materialize the season. ⚠️ THE MONEY STEP — it replaces every night in the range, including any
     * weekend-uplifted ones (season-over-weekend is authoring order, which the confirmation states).
     */
    public function apply(Request $request, SeasonalRateService $seasons, string $season): RedirectResponse
    {
        $model = app(RateScope::class)->season($season);   // DEC-l — inside the person's properties

        if ($refused = $this->paused($request)) {
            return $refused;
        }

        $preview = $seasons->preview($model);

        // ⚠️⚠️ PRECEDENCE ORDER, NOT A BARE APPLY (2026-09-01). The calendar is materialised, so the only way
        // a precedence rule can govern the PRICE is to control WRITE ORDER: the whole covering set is
        // re-applied weakest-first through the certified writer, so the strongest season lands last. Calling
        // `apply()` alone here would have left the price decided by whichever button was pressed first, while
        // the owner's PEAK column claimed otherwise. ⚠️ `SeasonalRateService::apply()` itself is UNCHANGED —
        // this orchestrates it, it does not replace it.
        $written = $seasons->applyInPrecedenceOrder($model);

        $notice = __('php/http/controllers/operator/season_action_controller.applied', [
            'season' => (string) $model->name,
            'nights' => trans_choice('php/http/controllers/operator/season_action_controller.nights_count', (int) $preview['nights'], ['count' => (int) $preview['nights']]),
            'units' => trans_choice('php/http/controllers/operator/season_action_controller.units_count', (int) $preview['units'], ['count' => (int) $preview['units']]),
        ]);

        // ⚠️ SAY WHEN MORE THAN THIS SEASON WAS WRITTEN. Re-applying a neighbour is not something an operator
        // should discover from the calendar. Counted from what was ACTUALLY written, never assumed.
        if (count($written) > 1) {
            $notice .= ' '.trans_choice('php/http/controllers/operator/season_action_controller.overlapping_reapplied', count($written) - 1, ['count' => count($written) - 1]);
        }

        // ⚠️ 2026-10-04 (his next report) — and from the Calendar's New reservation dialog, back INTO it ({@see CalendarReturn}).
        if ($this->fromWizard($request) || CalendarReturn::wanted($request)) {
            // ⚠️ DERIVED, NOT ASSUMED (the review, M1): the Rates page's own sentence of what was written (a stronger season
            //    that wins some nights, and the overlapping ones re-applied, are in it), then what the stay still lacks — its
            //    nights counted on the season's plan AFTER the write, never inferred from the season's dates.
            $in = $this->dateString($request->input('return_check_in'));
            $out = $this->dateString($request->input('return_check_out'));
            $stay = '';
            if ($in !== null && $out !== null && $out > $in) {
                $missing = (int) CarbonImmutable::parse($in)->diffInDays(CarbonImmutable::parse($out))
                    - RateCalendar::query()->where('rate_plan_id', $model->rate_plan_id)->where('date', '>=', $in)->where('date', '<', $out)->count();
                $stay = ' '.($missing <= 0
                    ? __('php/http/controllers/operator/season_action_controller.pick_unit_to_continue')
                    : trans_choice('php/http/controllers/operator/season_action_controller.stay_nights_unpriced', $missing, ['count' => $missing]));
                // the Calendar's dialog says what comes next itself, from what it shows (a unit may already be picked)
                if ($missing <= 0 && CalendarReturn::wanted($request)) {
                    $stay = '';
                }
            }

            return CalendarReturn::wanted($request)
                ? redirect()->to(CalendarReturn::dialog($request))->with('calendar_dialog_ok', $notice.$stay)
                : $this->toWizard($request)->with('new_reservation_ok', $notice.$stay);
        }

        return $this->toPage($request)->with('rates_notice', $notice);
    }
}
