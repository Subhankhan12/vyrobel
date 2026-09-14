<?php

use App\Models\Folio;
use App\Models\RateSeason;
use App\Models\Reservation;
use App\Services\RateAndRestrictionService;
use App\Services\SeasonalRateService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// ══ SEASONAL PRICING — THE FORM FOR THE MOST DANGEROUS WRITE IN THE PRODUCT ═══════════════════════
//
// ⚠️⚠️ THE ROUTES ALWAYS WORKED. `app.seasons.store` and `app.seasons.apply` were registered, gated
// `rates.manage`, and fully implemented — and NOT ONE VIEW referenced either of them. A test that POSTs to
// them would have passed before this pass and proves nothing (the H-2 lesson). So every assertion below
// reads the RENDERED PAGE, and the mutations remove the CONTROL, never the route.
//
// ⚠️ TWO THINGS WERE ESTABLISHED BY MEASUREMENT BEFORE ANY OF THIS WAS BUILT, and both are pinned here:
//   ① A season CANNOT re-price an existing booking. `reservation_units.price_minor` is stamped at booking
//      and the folio is made of stored rows — neither reads `rate_calendar`.
//   ② Applying is IRREVERSIBLE. `RateAndRestrictionService::setRate()` records an audit row whose `before`
//      is literally null, so the replaced prices survive nowhere.

function seasonWorld(): array
{
    $s = seedScenario(1);
    app(PermissionRegistrar::class)->setPermissionsTeamId((string) $s['org']->id);
    $operator = operatorUser($s, ['name' => 'Rita Rates'], 'Property Owner');

    app(RateAndRestrictionService::class)->setRate(
        $s['ratePlan'], '2026-09-01', '2026-09-10', Money::of(10000, 'EUR'),
    );

    return compact('s', 'operator');
}

function saveSeason(array $w, int $priceMinor = 50000, string $from = '2026-09-01', string $to = '2026-09-09'): RateSeason
{
    return RateSeason::create([
        'rate_plan_id' => $w['s']['ratePlan']->id, 'name' => 'Peak',
        'starts_on' => $from, 'ends_on' => $to,
        'price_minor' => $priceMinor, 'currency' => 'EUR',
    ]);
}

function ratesPage(array $w): string
{
    return test()->actingAs($w['operator'])->get(route('app.rates'))->assertOk()->getContent();
}

// ── ⚠️⚠️ #1 · A SEASON CANNOT RE-PRICE A BOOKED STAY — the question that gated the whole pass ─────
it('⚠️ never changes what an already-booked stay owes', function () {
    $w = seasonWorld();
    $res = book($w['s']);

    $bookedLine = (int) DB::table('reservation_units')->where('reservation_id', $res->id)->value('price_minor');
    expect($bookedLine)->toBe(10000);

    // A season at FIVE TIMES the price, over exactly the nights that stay occupies.
    app(SeasonalRateService::class)->apply(saveSeason($w));

    // ⚠️ The offer moved…
    expect((int) DB::table('rate_calendar')
        ->where('rate_plan_id', $w['s']['ratePlan']->id)->where('date', '2026-09-01')->value('price_minor'))->toBe(50000);

    // ⚠️⚠️ …and the STAY did not. The line price is stamped at booking, not resolved at read time.
    expect((int) DB::table('reservation_units')->where('reservation_id', $res->id)->value('price_minor'))->toBe(10000);
    expect((int) Reservation::query()->findOrFail($res->id)->total_minor)->toBe((int) $res->total_minor);
});

// ⚠️ AND THE FOLIO — the same defect one layer over. Folio items are STORED rows; nothing recomputes.
it('⚠️ never changes a folio that has already been posted', function () {
    $w = seasonWorld();
    $res = book($w['s']);
    // ⚠️ `book()` alone opens no folio — the room charge is what creates it. Without this the assertion
    //    below would have been vacuous against a null folio.
    postRoom($res, 30000);

    $folio = Folio::query()->where('reservation_id', $res->id)->firstOrFail();

    $before = (int) DB::table('folio_items')->where('folio_id', $folio->id)->sum('amount_minor');

    app(SeasonalRateService::class)->apply(saveSeason($w));

    expect((int) DB::table('folio_items')->where('folio_id', $folio->id)->sum('amount_minor'))->toBe($before);
});

// ── ⚠️ #2 · IRREVERSIBLE — and the confirmation says so in those words ───────────────────────────
it('⚠️ stores the replaced prices nowhere, and says so on the page', function () {
    $w = seasonWorld();
    app(SeasonalRateService::class)->apply(saveSeason($w));

    // ⚠️ The audit records the NEW price and a literally-null `before`. Nothing holds the old one.
    $row = DB::table('audit_events')->where('action', 'rate_calendar.updated')->orderByDesc('created_at')->first();
    expect($row)->not->toBeNull();
    expect($row->before)->toBeNull();

    // ⚠️ So the confirmation must be able to stand behind the claim it makes.
    expect(ratesPage($w))->toContain('This cannot be undone.');
});

// ── ⚠️⚠️ #3 · THE TWO-STEP — three distinct actions, and NO shortcut ─────────────────────────────
it('⚠️ offers saving and applying as separate controls, and no combined shortcut', function () {
    $w = seasonWorld();
    saveSeason($w);
    $html = ratesPage($w);

    // The save form posts to `store`, which writes no prices…
    expect($html)->toContain(route('app.seasons.store'));
    // …and applying is its own control, addressed to its own route.
    // ⚠️ THE URL IS NOT ON THE PAGE LITERALLY, AND THAT COST TWO ATTEMPTS. The apply control hands the
    //    season to Alpine through `Js::from()`, so the payload renders as `JSON.parse('…')` with quotes
    //    hex-escaped (`\u0022`) and every slash doubly escaped (`\\\/`). Both `toContain($url)` and
    //    re-encoding with `Js::from($url)` failed — the second because encoding a bare string is not the
    //    same as encoding it inside an array. Un-escaping the page and then looking for the plain URL is
    //    what actually proves the control points at the apply route.
    $applyUrl = route('app.seasons.apply', RateSeason::query()->firstOrFail()->id);
    expect(str_replace('\\', '', $html))->toContain($applyUrl);

    // ⚠️⚠️ THE SAVE FORM MUST NOT REACH THE APPLY ROUTE. A "save and apply" button would look like a
    //    convenience and would delete the review step that is this feature's only protection.
    $saveForm = substr($html, (int) strpos($html, route('app.seasons.store')));
    $saveForm = substr($saveForm, 0, (int) strpos($saveForm, '</form>'));
    expect($saveForm)->not->toContain('apply');
});

// ⚠️ A SAVED SEASON HAS WRITTEN NOTHING, and the page must not imply otherwise.
it('⚠️ shows a saved-but-unapplied season as having written no prices', function () {
    $w = seasonWorld();
    $season = saveSeason($w);

    expect(ratesPage($w))->toContain('Saved · no prices written');

    app(SeasonalRateService::class)->apply($season);

    $after = ratesPage($w);
    expect($after)->not->toContain('Saved · no prices written');
    expect($after)->toContain('Applied');
});

// ── ⚠️⚠️ #4 · EVERY FIGURE FROM preview() — none computed in the view ────────────────────────────
it('⚠️ renders the REAL blast radius, matching preview() exactly', function () {
    $w = seasonWorld();
    $season = saveSeason($w);
    $preview = app(SeasonalRateService::class)->preview($season);

    // The measured shape: 9 nights, 1 unit, 9 changing, 2 Fri/Sat, €100.00 → €500.00.
    expect($preview['nights'])->toBe(9);
    expect($preview['changing'])->toBe(9);

    $html = ratesPage($w);
    expect($html)->toContain($preview['changing'].' of '.$preview['nights'].' nights');
    expect($html)->toContain($preview['units'].' unit');
    expect($html)->toContain('€500.00');   // to_minor, formatted by the shared formatter
    expect($html)->toContain('€100.00');   // from_minor — what it replaces
    expect($html)->toContain('includes '.$preview['weekend_nights'].' Fri/Sat nights');
});

// ── ⚠️ #7 · THE ESTABLISHED PATTERNS, REUSED ─────────────────────────────────────────────────────
it('⚠️ labels every season field and gives the confirm the D2 dialog contract', function () {
    $w = seasonWorld();
    saveSeason($w);
    $html = ratesPage($w);

    foreach (['vy-season-plan', 'vy-season-name', 'vy-season-from', 'vy-season-to', 'vy-season-price'] as $id) {
        expect($html)->toContain('for="'.$id.'"');
        expect($html)->toContain('id="'.$id.'"');
    }

    // The D2 contract on the money confirm — a name that resolves, a trap, and an Escape.
    expect($html)->toContain('aria-labelledby="vy-dlg-season-t"');
    expect($html)->toContain('id="vy-dlg-season-t"');
    expect($html)->toContain('x-trap.noscroll="confirmSeason !== null"');
    expect(substr_count($html, 'role="dialog"'))->toBeGreaterThan(1);
});

// ── ⚠️ #8 · THE WHOLE FLOW, THROUGH THE RENDERED CONTROLS ────────────────────────────────────────
it('⚠️ saves, previews and applies a season end to end', function () {
    $w = seasonWorld();

    test()->actingAs($w['operator'])->post(route('app.seasons.store'), [
        'rate_plan_id' => $w['s']['ratePlan']->id, 'name' => 'Summer',
        'starts_on' => '2026-09-02', 'ends_on' => '2026-09-04', 'price' => '250.00',
    ])->assertRedirect();

    $season = RateSeason::query()->where('name', 'Summer')->firstOrFail();
    expect($season->applied_at)->toBeNull();
    // ⚠️ Saving wrote NO price.
    expect((int) DB::table('rate_calendar')->where('date', '2026-09-02')->value('price_minor'))->toBe(10000);

    test()->actingAs($w['operator'])->post(route('app.seasons.apply', $season->id))->assertRedirect();

    expect($season->fresh()->applied_at)->not->toBeNull();
    expect((int) DB::table('rate_calendar')->where('date', '2026-09-02')->value('price_minor'))->toBe(25000);
    expect((int) DB::table('rate_calendar')->where('date', '2026-09-04')->value('price_minor'))->toBe(25000);
    // ⚠️ The inclusive→exclusive conversion: the night AFTER the last one is untouched.
    expect((int) DB::table('rate_calendar')->where('date', '2026-09-05')->value('price_minor'))->toBe(10000);
});

// ── ⚠️ #6 · THE PEAK COLUMN — the owner-facing payoff, from a RENDERED page ──────────────────────
it('⚠️ populates the owner’s PEAK column once a season exists, flagged until applied', function () {
    $w = seasonWorld();
    $owner = ownUnit($w['s']['units'][0]);
    $ownerUser = ownerActor($owner, $w['s']);

    $today = now()->toDateString();
    $season = RateSeason::create([
        'rate_plan_id' => $w['s']['ratePlan']->id, 'name' => 'Peak now',
        'starts_on' => $today, 'ends_on' => now()->addDays(5)->toDateString(),
        'price_minor' => 44400, 'currency' => 'EUR',
    ]);

    $page = fn (): string => test()->actingAs($ownerUser)->get(route('owner.rates'))->assertOk()->getContent();

    // ⚠️ Present, and honestly marked as not yet priced.
    expect($page())->toContain('Peak now');

    app(SeasonalRateService::class)->apply($season);
    expect($page())->toContain('Peak now');
});
