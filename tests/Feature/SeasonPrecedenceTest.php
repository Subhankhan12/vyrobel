<?php

use App\Models\RateSeason;
use App\Services\RateAndRestrictionService;
use App\Services\SeasonalRateService;
use App\Support\Money\Money;
use App\Support\Pricing\SeasonPrecedence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// ══ SEASON PRECEDENCE — ONE RULE, AND IT HAS TO REACH THE MONEY ═══════════════════════════════════
//
// ⚠️⚠️ THE DEFECT. Precedence was answered twice: the owner's PEAK column took the EARLIEST-STARTING season
// while the PRICE came from whichever season had been applied LAST. So the column could name one season
// while the guest paid another's price, and which one depended on the order somebody clicked.
//
// ⚠️ THE RULE: the shorter season wins the nights they share; equal length falls to the newer one.
//
// ⚠️⚠️ WHY WRITE ORDER AND NOT A READ-TIME CHOICE. A season is MATERIALISED into `rate_calendar`, so once
// written no rule changes those nights — only another write does. `applyInPrecedenceOrder()` therefore
// re-applies the covering set WEAKEST FIRST through the UNCHANGED certified `apply()`, so the strongest
// lands last. Nothing certified was modified; the order around it is what changed.

const SP_BROAD_MINOR = 10000;   // €100.00 — a broad summer rate
const SP_PEAK_MINOR = 30000;    // €300.00 — a short peak inside it

function spWorld(): array
{
    $s = seedScenario(1);
    app(PermissionRegistrar::class)->setPermissionsTeamId((string) $s['org']->id);
    $operator = operatorUser($s, ['name' => 'Rita Rates'], 'Property Owner');

    app(RateAndRestrictionService::class)->setRate(
        $s['ratePlan'], '2026-06-01', '2026-09-01', Money::of(5000, 'EUR'),
    );

    return compact('s', 'operator');
}

function spSeason(array $w, string $name, string $from, string $to, int $minor): RateSeason
{
    return RateSeason::create([
        'rate_plan_id' => $w['s']['ratePlan']->id, 'name' => $name,
        'starts_on' => $from, 'ends_on' => $to, 'price_minor' => $minor, 'currency' => 'EUR',
    ]);
}

function spPriceOn(array $w, string $date): int
{
    return (int) DB::table('rate_calendar')
        ->where('rate_plan_id', $w['s']['ratePlan']->id)->where('date', $date)->value('price_minor');
}

// ── ⚠️⚠️ THE RULE REACHES THE MONEY, IN BOTH APPLY ORDERS ────────────────────────────────────────
//
// ⚠️ THIS IS THE WHOLE PASS. Before it, these two tests produced DIFFERENT calendars.
it('⚠️ gives the shorter season the shared nights — broad applied first', function () {
    $w = spWorld();
    $broad = spSeason($w, 'Summer', '2026-06-01', '2026-08-31', SP_BROAD_MINOR);
    $peak = spSeason($w, 'August peak', '2026-08-01', '2026-08-15', SP_PEAK_MINOR);

    app(SeasonalRateService::class)->applyInPrecedenceOrder($broad);
    app(SeasonalRateService::class)->applyInPrecedenceOrder($peak);

    expect(spPriceOn($w, '2026-07-15'))->toBe(SP_BROAD_MINOR);   // broad-only night
    expect(spPriceOn($w, '2026-08-10'))->toBe(SP_PEAK_MINOR);    // shared night → the SHORTER season
    expect(spPriceOn($w, '2026-08-20'))->toBe(SP_BROAD_MINOR);   // after the peak, broad again
});

it('⚠️ gives the shorter season the shared nights — peak applied first (the order that used to lose)', function () {
    $w = spWorld();
    $broad = spSeason($w, 'Summer', '2026-06-01', '2026-08-31', SP_BROAD_MINOR);
    $peak = spSeason($w, 'August peak', '2026-08-01', '2026-08-15', SP_PEAK_MINOR);

    app(SeasonalRateService::class)->applyInPrecedenceOrder($peak);
    app(SeasonalRateService::class)->applyInPrecedenceOrder($broad);

    // ⚠️⚠️ THE SAME CALENDAR AS ABOVE. Before this pass the broad season, applied second, flattened the
    //    peak to €100.00 — the price depended on which button was pressed first.
    expect(spPriceOn($w, '2026-07-15'))->toBe(SP_BROAD_MINOR);
    expect(spPriceOn($w, '2026-08-10'))->toBe(SP_PEAK_MINOR);
    expect(spPriceOn($w, '2026-08-20'))->toBe(SP_BROAD_MINOR);
});

// ── ⚠️⚠️ THE WRONG-FIX GUARD: DISPLAY AND MONEY MUST NOT DIVERGE ─────────────────────────────────
//
// ⚠️ This is the defect itself, defended directly. It compares what the PEAK column would name against what
// the CALENDAR actually charges, night by night. Making `covering()` use any other rule than the write path
// turns this red — which is precisely what a future "tidy-up" of the two would do.
it('⚠️ makes the column name the season the calendar actually charges, every night', function () {
    $w = spWorld();
    $broad = spSeason($w, 'Summer', '2026-06-01', '2026-08-31', SP_BROAD_MINOR);
    $peak = spSeason($w, 'August peak', '2026-08-01', '2026-08-15', SP_PEAK_MINOR);

    app(SeasonalRateService::class)->applyInPrecedenceOrder($peak);
    app(SeasonalRateService::class)->applyInPrecedenceOrder($broad);

    $seasons = app(SeasonalRateService::class);
    $checked = 0;

    foreach (['2026-06-15', '2026-07-15', '2026-08-01', '2026-08-10', '2026-08-15', '2026-08-16', '2026-08-31'] as $date) {
        $named = $seasons->covering((string) $w['s']['ratePlan']->id, $date);
        expect($named)->not->toBeNull();

        // ⚠️ THE SEASON THE COLUMN NAMES MUST BE THE ONE WHOSE PRICE IS IN THE CALENDAR.
        expect(spPriceOn($w, $date))->toBe((int) $named->price_minor);
        $checked++;
    }

    expect($checked)->toBe(7);
});

// ── ⚠️ THE RULE ITSELF, AND ITS TIEBREAK ─────────────────────────────────────────────────────────
it('⚠️ ranks the shorter season first', function () {
    $w = spWorld();
    $broad = spSeason($w, 'Summer', '2026-06-01', '2026-08-31', SP_BROAD_MINOR);
    $peak = spSeason($w, 'August peak', '2026-08-01', '2026-08-15', SP_PEAK_MINOR);

    expect(SeasonPrecedence::nights($peak))->toBe(15);
    expect(SeasonPrecedence::nights($broad))->toBe(92);
    expect(SeasonPrecedence::winnerOn([$broad, $peak], '2026-08-10')?->name)->toBe('August peak');
    // Outside the peak, the broad season is the only contender.
    expect(SeasonPrecedence::winnerOn([$broad, $peak], '2026-07-01')?->name)->toBe('Summer');
});

// ⚠️ A RULE WITH AN UNDEFINED CASE IS NOT A RULE. Equal length falls through to the newer season.
it('⚠️ breaks a same-length tie with the more recently created season', function () {
    $w = spWorld();

    // ⚠️⚠️ THE ROW WITH THE LATER `created_at` IS CREATED **FIRST**, ON PURPOSE — and this is the whole
    //    value of the test. ULIDs are monotonic, so a fixture that creates them in timestamp order gives the
    //    newer season BOTH the later `created_at` and the larger id. Deleting the `created_at` tiebreak then
    //    changed nothing: the id fallback picked the same winner and the mutation came back GREEN. Building
    //    them in OPPOSITE orders makes the two signals disagree, so only the real tiebreak can pass.
    $newer = spSeason($w, 'Newer', '2026-08-01', '2026-08-07', 22200);   // smaller ULID
    $older = spSeason($w, 'Older', '2026-08-01', '2026-08-07', 11100);   // larger ULID
    $newer->forceFill(['created_at' => now()])->save();
    $older->forceFill(['created_at' => now()->subDay()])->save();

    expect((string) $newer->getKey())->toBeLessThan((string) $older->getKey());   // the ids DISAGREE with age

    expect(SeasonPrecedence::nights($older->fresh()))->toBe(SeasonPrecedence::nights($newer->fresh()));
    expect(SeasonPrecedence::winnerOn([$older->fresh(), $newer->fresh()], '2026-08-03')?->name)->toBe('Newer');

    // ⚠️ AND THE ORDER IS TOTAL — the answer does not depend on which order the set is read in.
    expect(SeasonPrecedence::winnerOn([$newer->fresh(), $older->fresh()], '2026-08-03')?->name)->toBe('Newer');
});

// ── ⚠️⚠️ THE TWO-STEP SURVIVES: an UNAPPLIED neighbour is never written by someone else's apply ──
it('⚠️ never writes a season the operator has not applied', function () {
    $w = spWorld();
    $saved = spSeason($w, 'Saved only', '2026-06-01', '2026-08-31', SP_BROAD_MINOR);
    $peak = spSeason($w, 'August peak', '2026-08-01', '2026-08-15', SP_PEAK_MINOR);

    app(SeasonalRateService::class)->applyInPrecedenceOrder($peak);

    // The peak's own nights were written…
    expect(spPriceOn($w, '2026-08-10'))->toBe(SP_PEAK_MINOR);
    // …and the merely-SAVED season wrote nothing, anywhere.
    expect(spPriceOn($w, '2026-07-15'))->toBe(5000);
    expect($saved->fresh()->applied_at)->toBeNull();
});

// ── ⚠️ THE CONFLICT IS DERIVED, AND ON THE PAGE ──────────────────────────────────────────────────
it('⚠️ reports the conflict from preview(), and renders it', function () {
    $w = spWorld();
    $broad = spSeason($w, 'Summer', '2026-06-01', '2026-08-31', SP_BROAD_MINOR);
    $peak = spSeason($w, 'August peak', '2026-08-01', '2026-08-15', SP_PEAK_MINOR);
    app(SeasonalRateService::class)->applyInPrecedenceOrder($peak);

    $preview = app(SeasonalRateService::class)->preview($broad->fresh());

    // ⚠️ The broad season loses exactly the peak's 15 nights — computed per night by the ONE rule.
    expect($preview['overlaps'])->toBe(1);
    expect($preview['losing_nights'])->toBe(15);
    expect($preview['losing_to'])->toBe('August peak');
    // …and applying the broad one will also re-write the applied peak's 15 nights.
    expect($preview['rewritten_nights'])->toBe(15);

    $html = test()->actingAs($w['operator'])->get(route('app.rates'))->assertOk()->getContent();
    expect($html)->toContain($preview['losing_nights'].' of these nights are governed by August peak');
    expect($html)->toContain('re-writes '.$preview['rewritten_nights'].' nights from overlapping seasons');
    // The rule's own sentence, from the rule — not retyped.
    expect($html)->toContain(SeasonPrecedence::SENTENCE);
});

// ── ⚠️ THE CERTIFIED WRITERS ARE UNTOUCHED — precedence is ORDER, not a new writer ───────────────
it('⚠️ still routes every night through the certified writer', function () {
    $w = spWorld();
    $broad = spSeason($w, 'Summer', '2026-06-01', '2026-08-31', SP_BROAD_MINOR);
    $peak = spSeason($w, 'August peak', '2026-08-01', '2026-08-15', SP_PEAK_MINOR);

    app(SeasonalRateService::class)->applyInPrecedenceOrder($broad);
    app(SeasonalRateService::class)->applyInPrecedenceOrder($peak);

    // ⚠️ `setRate()` is what records this event — one per season written, and no other writer exists.
    expect(DB::table('audit_events')->where('action', 'rate_calendar.updated')->count())->toBeGreaterThanOrEqual(3);
    expect($broad->fresh()->applied_at)->not->toBeNull();
    expect($peak->fresh()->applied_at)->not->toBeNull();
});

/**
 * ⚠️⚠️⚠️ RULED BY THE FOUNDER, 2026-09-19 — R-SEASON: NO GUARD ON RE-APPLY. THIS IS THE SHARP ONE.
 *
 * ⚠️⚠️ APPLYING A SEASON RE-APPLIES THE WHOLE COVERING SET, WEAKEST-FIRST, EVERY TIME — AND THAT IS THE
 * DESIGN, NOT A LOOSE EDGE. The calendar is materialised, so the ONLY way a precedence rule can govern the
 * PRICE is to control WRITE ORDER. Re-applying the covering set is how the strongest season lands last.
 *
 * ⚠️⚠️ A FUTURE PASS WILL SEE `apply()` WRITING AGAIN ON AN ALREADY-APPLIED SEASON, READ IT AS A MISSING
 * IDEMPOTENCY GUARD, AND ADD ONE. THAT WOULD BREAK PRICING — the covering set would stop being re-ordered and
 * the price would revert to "whichever button was pressed first", which is the exact defect
 * `SeasonPrecedence` was built to end. **The founder has confirmed the behaviour. Do not guard it.**
 *
 * ⚠️ (Its other half is already closed by code: EDITING an applied season is refused, with an explanation,
 * in `SeasonActionController::update()`. Re-APPLYING is the half that stays open, deliberately.)
 *
 * ⚠️ THE MUTATION, AND WHY IT DISCRIMINATES: guard `apply()` on `applied_at !== null` → the re-apply below
 * is skipped, the corrupted night is never rewritten, and the final assertion reads 111 instead of 7000 → RED.
 * ⚠️ A weaker test that merely re-applied and checked the price was still right would stay GREEN under that
 * mutation, because skipping a write leaves a correct value in place. The night is corrupted FIRST precisely
 * so that "did nothing" and "re-applied" are distinguishable.
 */
it('⚠️⚠️ RULED 2026-09-19 (R-SEASON) — a season RE-APPLIES the whole covering set BY DESIGN, and is deliberately unguarded', function () {
    $w = spWorld();
    $season = spSeason($w, 'Summer', '2026-07-01', '2026-08-31', 7000);

    app(SeasonalRateService::class)->applyInPrecedenceOrder($season);
    expect(spPriceOn($w, '2026-07-15'))->toBe(7000);
    expect($season->fresh()->applied_at)->not->toBeNull();

    // ⚠️ CORRUPT ONE NIGHT so that "skipped" and "re-applied" cannot look alike.
    DB::table('rate_calendar')
        ->where('rate_plan_id', $w['s']['ratePlan']->id)->where('date', '2026-07-15')
        ->update(['price_minor' => 111]);

    // ⚠️ THE SECOND APPLY — permitted, unguarded, and it WRITES AGAIN.
    app(SeasonalRateService::class)->applyInPrecedenceOrder($season->fresh());

    expect(spPriceOn($w, '2026-07-15'))
        ->toBe(7000, 'R-SEASON: re-applying rewrites the covering set — a guard here would break precedence');
});
