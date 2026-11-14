<?php

declare(strict_types=1);

use App\Models\Folio;
use App\Models\RatePlan;
use App\Models\RateSeason;
use App\Models\Reservation;
use App\Models\UnitRateOverride;
use App\Services\AvailabilityService;
use App\Services\RateAndRestrictionService;
use App\Services\SeasonalRateService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// OWNER FIX 4b — the MATERIALIZED seasonal ("peak") tier.
//
// ⚠️⚠️ THE DESIGN IS THE SAFETY PROPERTY. A season is written into `rate_calendar` through the CERTIFIED
// writer, exactly as the weekend uplift is; nothing reads `rate_seasons` to price a stay. So there is still
// ONE price source, and the storefront, `priceOver()`, the folio and every grid agree without changing a
// line of the charging path — which test 1 proves by booking through the UNCHANGED storefront.
//
// ⚠️ THE REJECTED DESIGN (a read-time season inside NightlyRate) would have been INERT here:
// `stayTotalForUnit()` returns NULL for a unit with no override, so the default charge is
// `priceOver()` → `rate_calendar`. Test 1 would have failed against it, which is why it is written this way.
//
// ⚠️ DATE-DETERMINISTIC (§N.17.7): every date below is fixed, so a season never silently drifts relative to
// "today" and turns a red into a green next month.

const SRT_SEASON_START = '2026-07-01';

const SRT_SEASON_END = '2026-07-31';           // INCLUSIVE, the way a person reads a season

const SRT_BASE_MINOR = 12000;                  // the ordinary published nightly rate

const SRT_PEAK_MINOR = 47000;                  // the seasonal price

const SRT_OVERRIDE_MINOR = 51000;              // a per-unit override that must still beat the season

/** A property with a published base rate across July AND August — so "outside the range" is real data. */
function srtCast(): array
{
    $s = publicPropertyScenario(units: 2, nightlyMinor: SRT_BASE_MINOR);
    $plan = RatePlan::query()->where('room_type_id', $s['roomType']->id)->firstOrFail();

    // Publish a flat base across both months; the season will later rewrite ONLY July.
    app(RateAndRestrictionService::class)->setRate($plan, '2026-07-01', '2026-09-01',
        Money::of(SRT_BASE_MINOR, (string) $plan->currency));

    // ⚠️ AVAILABILITY MUST BE PUBLISHED FOR THE WINDOW OR NO BOOKING CAN HAPPEN — and a folio that never
    // exists reads exactly like "the season didn't charge", which is the false-negative that would have made
    // this whole file lie.
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-07-01', '2026-09-01');

    return ['s' => $s, 'plan' => $plan];
}

function srtSeason(array $f, int $minor = SRT_PEAK_MINOR): RateSeason
{
    return RateSeason::create([
        'rate_plan_id' => (string) $f['plan']->getKey(),
        'name' => 'Peak summer',
        'starts_on' => SRT_SEASON_START,
        'ends_on' => SRT_SEASON_END,
        'price_minor' => $minor,
        'currency' => (string) $f['plan']->currency,
    ]);
}

/** ⚠️ The whole calendar OUTSIDE the season — the byte-identity subject. */
function srtOutsideHash(array $f): string
{
    return md5(DB::table('rate_calendar')
        ->where('rate_plan_id', $f['plan']->getKey())
        ->where(fn ($q) => $q->where('date', '<', SRT_SEASON_START)->orWhere('date', '>', SRT_SEASON_END))
        ->orderBy('date')
        ->get(['date', 'price_minor', 'currency'])
        ->toJson());
}

/** Book through the REAL storefront — one POST, quote()+book() in the same request. */
function srtBook(array $f, string $in, string $out, ?string $unitId = null): void
{
    test()->post(route('booking.reserve', $f['s']['property']->slug), array_filter([
        'room_type_id' => (string) $f['s']['roomType']->id,
        'rate_plan_id' => (string) $f['plan']->getKey(),
        'check_in' => $in,
        'check_out' => $out,
        'adults' => 2,
        'first_name' => 'Ada',
        'last_name' => 'Traveller',
        'email' => 'ada+'.uniqid().'@example.test',
        'payment_option' => 'on_arrival',
        ...acceptedTerms($f['s']['property']),
        'unit_id' => $unitId,
    ]));
}

/** ⚠️ RAW — the room charge the guest actually incurred. */
function srtRoomCharge(): ?int
{
    $folio = Folio::query()->latest('id')->first();

    return $folio === null ? null : (int) DB::table('folio_items')
        ->where('folio_id', $folio->getKey())->where('type', 'room')->sum('amount_minor');
}

// ── 1. ⚠️⚠️ THE MONEY PROOF — an applied season charges through the UNCHANGED storefront ──────────────
it('charges the seasonal price at the folio after the season is applied', function () {
    $f = srtCast();
    $season = srtSeason($f);

    // ⚠️ THE PREMISE, PROVEN FIRST: before applying, those nights charge the ORDINARY rate. Without this the
    // assertion below could pass on a fixture that was seasonal all along.
    srtBook($f, '2026-07-10', '2026-07-12');
    expect(srtRoomCharge())->toBe(SRT_BASE_MINOR * 2);

    app(SeasonalRateService::class)->apply($season);

    srtBook($f, '2026-07-20', '2026-07-22');

    // ⚠️ RAW, ACROSS ALL THREE SURFACES — the folio, the stored line price, and the quote the guest was shown.
    // Charged = shown, and none of the storefront changed to achieve it.
    $reservation = Reservation::query()->latest('id')->first();
    expect(srtRoomCharge())->toBe(SRT_PEAK_MINOR * 2)
        ->and((int) DB::table('reservation_units')->where('reservation_id', $reservation->getKey())->value('price_minor'))
        ->toBe(SRT_PEAK_MINOR * 2)
        ->and((int) DB::table('booking_quotes')->latest('id')->value('total_minor'))->toBe(SRT_PEAK_MINOR * 2);
});

// ── 2. ⚠️⚠️ BYTE-IDENTICAL OUTSIDE THE SEASON'S RANGE ─────────────────────────────────────────────────
it('changes only the season nights and leaves every other night byte-identical', function () {
    $f = srtCast();
    $before = srtOutsideHash($f);
    $julyBefore = (int) DB::table('rate_calendar')->where('rate_plan_id', $f['plan']->getKey())
        ->whereBetween('date', [SRT_SEASON_START, SRT_SEASON_END])->sum('price_minor');

    app(SeasonalRateService::class)->apply(srtSeason($f));

    // ⚠️ EVERYTHING OUTSIDE THE RANGE IS UNTOUCHED — hashed before, hashed after.
    expect(srtOutsideHash($f))->toBe($before);

    // …and INSIDE the range every night really did move, so the hash above is not passing on a no-op.
    $julyAfter = (int) DB::table('rate_calendar')->where('rate_plan_id', $f['plan']->getKey())
        ->whereBetween('date', [SRT_SEASON_START, SRT_SEASON_END])->sum('price_minor');
    expect($julyAfter)->not->toBe($julyBefore)
        ->and($julyAfter)->toBe(SRT_PEAK_MINOR * 31);
});

// ── 3. ⚠️⚠️ A PER-UNIT OVERRIDE STILL BEATS A SEASON ──────────────────────────────────────────────────
it('charges the per-unit override on a night that is also seasonal', function () {
    $f = srtCast();
    app(SeasonalRateService::class)->apply(srtSeason($f));

    $unitId = (string) $f['s']['units'][0]->id;
    foreach (['2026-07-20', '2026-07-21'] as $date) {
        UnitRateOverride::create([
            'organization_id' => $f['s']['org']->id,
            'rate_plan_id' => $f['plan']->getKey(),
            'unit_id' => $unitId,
            'date' => $date,
            'price_minor' => SRT_OVERRIDE_MINOR,
            'currency' => 'EUR',
        ]);
    }

    srtBook($f, '2026-07-20', '2026-07-22', $unitId);

    // ⚠️ The override wins with NO new logic — it lives in a table that already beats `rate_calendar`.
    expect(srtRoomCharge())->toBe(SRT_OVERRIDE_MINOR * 2)
        ->and(srtRoomCharge())->not->toBe(SRT_PEAK_MINOR * 2);
});

// ── 4. ⚠️⚠️ THE SEASON IS WRITTEN ONLY THROUGH THE CERTIFIED WRITER ───────────────────────────────────
it('materializes the season through the certified writer, leaving its audit trail', function () {
    $f = srtCast();
    $season = srtSeason($f);

    // ⚠️ THIS TEST WAS VACUOUS IN ITS FIRST FORM, AND A MUTATION IS WHAT EXPOSED IT. It made the certified
    // writer UNRESOLVABLE and asserted that applying then wrote nothing — but the writer is CONSTRUCTOR-
    // injected, so an unresolvable binding kills SeasonalRateService at construction. Replacing the
    // `setRate()` call with a direct `RateCalendar::updateOrCreate()` loop — a real second write path, the
    // exact D4-1 hazard — still passed, because the constructor threw first. It proved the dependency was
    // CONSTRUCTED, never that it was USED.
    //
    // ⚠️ THE EVIDENCE IS NOW A SIDE EFFECT ONLY THE CERTIFIED WRITER PRODUCES: `setRate()` records a
    // `rate_calendar.updated` domain event and audit row. A bypass writes prices but leaves no trail, so it
    // cannot satisfy this — which is what "no second writer" actually has to mean.
    $before = (int) DB::table('domain_events')
        ->where('event_type', 'rate_calendar.updated')
        ->where('subject_id', (string) $f['plan']->getKey())
        ->count();

    app(SeasonalRateService::class)->apply($season);

    $after = (int) DB::table('domain_events')
        ->where('event_type', 'rate_calendar.updated')
        ->where('subject_id', (string) $f['plan']->getKey())
        ->count();

    expect($after)->toBe($before + 1);

    // …and the nights really carry the price, so the event is not a trail over a no-op.
    expect((int) DB::table('rate_calendar')->where('rate_plan_id', $f['plan']->getKey())
        ->where('date', '2026-07-15')->value('price_minor'))->toBe(SRT_PEAK_MINOR);

    // The audit row lands too — same certified path, both trails.
    expect((int) DB::table('audit_events')
        ->where('action', 'rate_calendar.updated')
        ->where('subject_id', (string) $f['plan']->getKey())
        ->count())->toBeGreaterThan(0);
});

// ── 5. ⚠️⚠️ APPLYING IS `rates.manage`-GATED, AND A REFUSED APPLY WRITES NOTHING ───────────────────────
it('refuses to apply a season without rates.manage and writes no price', function () {
    $f = srtCast();
    $season = srtSeason($f);
    $before = (int) DB::table('rate_calendar')->where('rate_plan_id', $f['plan']->getKey())
        ->whereBetween('date', [SRT_SEASON_START, SRT_SEASON_END])->sum('price_minor');

    $desk = operatorUser($f['s'], [], 'Front Desk');
    test()->actingAs($desk)->withSession(['current_property_id' => $f['s']['property']->id])
        ->post(route('app.seasons.apply', $season->getKey()))
        ->assertForbidden();

    // ⚠️ RAW: refused AND nothing written — a 403 that still moved money would be the worst of both.
    expect((int) DB::table('rate_calendar')->where('rate_plan_id', $f['plan']->getKey())
        ->whereBetween('date', [SRT_SEASON_START, SRT_SEASON_END])->sum('price_minor'))->toBe($before)
        ->and($season->fresh()->applied_at)->toBeNull();
});

// ── 6. ⚠️ THE BLAST RADIUS IS COMPUTED, NOT GUESSED — including the weekend nights it replaces ────────
it('previews the real blast radius before anything is written', function () {
    $f = srtCast();
    $season = srtSeason($f);

    $preview = app(SeasonalRateService::class)->preview($season);

    // 31 inclusive nights in July, across the room type's 2 real units.
    expect($preview['nights'])->toBe(31)
        ->and($preview['units'])->toBe(2)
        // Every night currently differs from the seasonal price, so all 31 change.
        ->and($preview['changing'])->toBe(31)
        // ⚠️ July 2026 has NINE Fri/Sat nights (5 Fridays + 4 Saturdays) — I first asserted 8 and the CODE
        // was right. Naming them is what makes season-over-weekend legible: both are materialized, so the
        // last write wins, and the operator should know which nights that touches.
        ->and($preview['weekend_nights'])->toBe(9)
        ->and($preview['to_minor'])->toBe(SRT_PEAK_MINOR);

    // ⚠️ AND PREVIEWING WROTE NOTHING — it is a read.
    expect($season->fresh()->applied_at)->toBeNull();
});

// ── 7. ⚠️ AN UNAPPLIED SEASON PRICES NOTHING — defining is free ───────────────────────────────────────
it('does not change any night until the season is applied', function () {
    $f = srtCast();
    $before = md5(DB::table('rate_calendar')->where('rate_plan_id', $f['plan']->getKey())
        ->orderBy('date')->get(['date', 'price_minor'])->toJson());

    srtSeason($f);   // defined, never applied

    expect(md5(DB::table('rate_calendar')->where('rate_plan_id', $f['plan']->getKey())
        ->orderBy('date')->get(['date', 'price_minor'])->toJson()))->toBe($before);

    // …and a booking in the range still charges the ordinary rate.
    srtBook($f, '2026-07-10', '2026-07-12');
    expect(srtRoomCharge())->toBe(SRT_BASE_MINOR * 2);
});
