<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\RatePlan;
use App\Models\RateSeason;
use App\Services\SeasonalRateService;
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

        RateSeason::create([
            'rate_plan_id' => (string) $plan->getKey(),
            'name' => $data['name'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'price_minor' => $minor,
            'currency' => (string) $plan->currency,
        ]);

        return back()->with('rates_notice', 'Season saved. Review the blast radius, then apply it to write those nights.');
    }

    /**
     * Materialize the season. ⚠️ THE MONEY STEP — it replaces every night in the range, including any
     * weekend-uplifted ones (season-over-weekend is authoring order, which the confirmation states).
     */
    public function apply(Request $request, SeasonalRateService $seasons, string $season): RedirectResponse
    {
        $model = RateSeason::query()->findOrFail($season);   // tenant-scoped → cross-org 404

        $preview = $seasons->preview($model);
        $seasons->apply($model);

        return back()->with('rates_notice', sprintf(
            '%s applied — %d night%s rewritten across %d unit%s.',
            $model->name,
            $preview['nights'],
            $preview['nights'] === 1 ? '' : 's',
            $preview['units'],
            $preview['units'] === 1 ? '' : 's',
        ));
    }
}
