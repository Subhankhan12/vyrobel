<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\RatePlan;
use App\Models\RateSeason;
use App\Services\SeasonalRateService;
use App\Support\Events\AuditEventRecorder;
use App\Support\Money\MoneyInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
    public function __construct(private AuditEventRecorder $audit) {}

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
        $data = $request->validate([
            'rate_plan_id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:80'],
            'starts_on' => ['required', 'date'],
            // ⚠️ INCLUSIVE, and it must not precede the start — a backwards range would write nothing at all
            // and read as "applied" afterwards.
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'price' => ['required', 'string'],
        ]);

        $plan = RatePlan::query()->findOrFail($data['rate_plan_id']);   // tenant-scoped → cross-org 404

        $minor = MoneyInput::toMinor($data['price']);
        if ($minor === null || $minor <= 0) {
            return back()->withErrors(['price' => 'Enter a nightly price greater than zero.']);
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

        return back()->with('rates_notice', 'Season saved. Review the blast radius, then apply it to write those nights.');
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
        $model = RateSeason::query()->findOrFail($season);   // tenant-scoped → cross-org 404

        if ($model->applied_at !== null) {
            return back()->withErrors([
                'season' => 'This season has already been applied, so it cannot be edited — the nightly rates it '
                    .'wrote have replaced whatever was there, and nothing recorded the previous prices. Author a new '
                    .'season over those nights instead.',
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'price' => ['required', 'string'],
        ]);

        $minor = MoneyInput::toMinor($data['price']);

        if ($minor === null || $minor <= 0) {
            return back()->withErrors(['price' => 'Enter a nightly price greater than zero.']);
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

        return back()->with('rates_notice', 'Season updated. Review the blast radius, then apply it to write those nights.');
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
    public function destroy(string $season): RedirectResponse
    {
        $model = RateSeason::query()->findOrFail($season);
        $wasApplied = $model->applied_at !== null;
        $name = (string) $model->name;

        // ⚠️ The row is about to be gone for good (hard delete, deliberately) — so the audit row is the ONLY
        // remaining record of what existed. Captured before the delete, and `after` is honestly null.
        $before = $this->seasonState($model);

        $model->delete();

        $this->audit->record('rate_season.deleted', $model, $before, null);

        return back()->with('rates_notice', $wasApplied
            ? sprintf('Season "%s" deleted. ⚠️ The nightly rates it already applied are unchanged — deleting a '
                .'season does not restore the prices it replaced. Set those nights yourself if they are wrong.', $name)
            : sprintf('Season "%s" deleted. It had not been applied, so no rates changed.', $name));
    }

    /**
     * Materialize the season. ⚠️ THE MONEY STEP — it replaces every night in the range, including any
     * weekend-uplifted ones (season-over-weekend is authoring order, which the confirmation states).
     */
    public function apply(Request $request, SeasonalRateService $seasons, string $season): RedirectResponse
    {
        $model = RateSeason::query()->findOrFail($season);   // tenant-scoped → cross-org 404

        $preview = $seasons->preview($model);

        // ⚠️⚠️ PRECEDENCE ORDER, NOT A BARE APPLY (2026-09-01). The calendar is materialised, so the only way
        // a precedence rule can govern the PRICE is to control WRITE ORDER: the whole covering set is
        // re-applied weakest-first through the certified writer, so the strongest season lands last. Calling
        // `apply()` alone here would have left the price decided by whichever button was pressed first, while
        // the owner's PEAK column claimed otherwise. ⚠️ `SeasonalRateService::apply()` itself is UNCHANGED —
        // this orchestrates it, it does not replace it.
        $written = $seasons->applyInPrecedenceOrder($model);

        $notice = sprintf(
            '%s applied — %d night%s rewritten across %d unit%s.',
            $model->name,
            $preview['nights'],
            $preview['nights'] === 1 ? '' : 's',
            $preview['units'],
            $preview['units'] === 1 ? '' : 's',
        );

        // ⚠️ SAY WHEN MORE THAN THIS SEASON WAS WRITTEN. Re-applying a neighbour is not something an operator
        // should discover from the calendar. Counted from what was ACTUALLY written, never assumed.
        if (count($written) > 1) {
            $notice .= sprintf(
                ' %d overlapping season%s re-applied in precedence order.',
                count($written) - 1,
                count($written) === 2 ? ' was' : 's were',
            );
        }

        return back()->with('rates_notice', $notice);
    }
}
