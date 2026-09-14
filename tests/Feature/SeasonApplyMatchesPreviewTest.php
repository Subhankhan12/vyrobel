<?php

use App\Models\RateCalendar;
use App\Models\RateSeason;
use App\Services\SeasonalRateService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

/**
 * ⚠️⚠️ THE SEASON APPLY — the one action this product cannot undo, pinned after being executed for the
 * first time (FIX 20, 2026-09-14, in tenant two).
 *
 * The two-step exists so the blast radius is shown BEFORE it lands. The preview had been verified against
 * the database; the APPLY had never run, so nobody had ever checked that the promise matches the result.
 * 🔬 It does: a preview of "4 of 4 nights" wrote exactly 4 rows, and nothing outside the range moved.
 *
 * ⚠️⚠️ THE WRONG-FIX MUTATION IS WHY THIS ASSERTS ROWS. "The season was applied" — i.e. `applied_at` is
 * stamped — passes while ZERO rate rows changed. `applied_at` is written by `apply()` regardless of what
 * the rate writer did, so it is evidence of an attempt, not of an effect. Every assertion below reads
 * `rate_calendar`.
 */
function f20Season(array $s, string $from, string $to, int $priceMinor): RateSeason
{
    return RateSeason::create([
        'property_id' => $s['property']->id,
        'rate_plan_id' => $s['ratePlan']->id,
        'name' => 'Pinned season',
        'starts_on' => $from, 'ends_on' => $to,
        'price_minor' => $priceMinor, 'currency' => 'EUR',
    ]);
}

it('⚠️⚠️ the preview\'s night count is exactly what the apply writes — and nothing outside it moves', function () {
    $s = seedScenario(1);
    $seasons = app(SeasonalRateService::class);

    // A uniform calendar, so any change is unambiguous. 10 nights at 9000.
    for ($d = 1; $d <= 10; $d++) {
        RateCalendar::create([
            'rate_plan_id' => $s['ratePlan']->id,
            'date' => sprintf('2026-11-%02d', $d),
            'price_minor' => 9000, 'currency' => 'EUR',
        ]);
    }

    // The season covers 4 of those 10 nights.
    $season = f20Season($s, '2026-11-04', '2026-11-07', 15000);

    $preview = $seasons->preview($season);

    // ⚠️ NON-VACUITY: the preview must actually promise something, or "it matched" is meaningless.
    expect($preview['nights'])->toBeGreaterThan(0);

    $seasons->applyInPrecedenceOrder($season);

    // ── THE PROOF IS THE ROWS, not `applied_at`.
    $changed = RateCalendar::query()->where('rate_plan_id', $s['ratePlan']->id)
        ->where('price_minor', 15000)->count();

    expect($changed)->toBe($preview['nights'], 'the preview promised '.$preview['nights'].' nights and '.$changed.' rows changed');

    // ── THE POSITIVE CONTROL: the six nights outside the range must be untouched, or "the rows changed"
    //    and "the whole calendar was rewritten" look identical.
    $untouched = RateCalendar::query()->where('rate_plan_id', $s['ratePlan']->id)
        ->where('price_minor', 9000)->count();
    expect($untouched)->toBe(6);

    // …and no row was created or destroyed — an UPDATE, not an insert.
    expect(RateCalendar::query()->where('rate_plan_id', $s['ratePlan']->id)->count())->toBe(10);
});

it('⚠️ the apply records NO prior value — the irreversibility sentence is true', function () {
    $s = seedScenario(1);
    RateCalendar::create([
        'rate_plan_id' => $s['ratePlan']->id, 'date' => '2026-11-04',
        'price_minor' => 9000, 'currency' => 'EUR',
    ]);

    $season = f20Season($s, '2026-11-04', '2026-11-04', 15000);
    app(SeasonalRateService::class)->applyInPrecedenceOrder($season);

    // The dialog promises: "The prices it replaces are not stored anywhere. This cannot be undone."
    // 🔬 Measured after a real apply: the audit row's `before` is NULL and the payload carries only the NEW
    //    price. This test pins that the claim stays TRUE — if a provenance column is ever added, it fails
    //    and the dialog's wording must change with it.
    $audit = DB::table('audit_events')->where('action', 'rate_calendar.updated')
        ->orderByDesc('created_at')->first();

    expect($audit)->not->toBeNull();
    expect($audit->before)->toBeNull('a prior value is now recorded — the dialog must stop saying it is not');
    expect((string) $audit->after)->not->toContain('9000');
});
