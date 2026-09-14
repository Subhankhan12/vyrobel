<?php

use App\Models\RateCalendar;
use App\Models\Unit;
use App\Models\UnitRateOverride;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use App\Support\Pricing\NightlyRate;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// D18-5 phase 1 — a rate may bind to a UNIT as an override of its room type's price.
//
// ⚠️ THE FALLBACK IS THE FEATURE AND THE SAFETY GUARANTEE AT ONCE. Because a unit with no override resolves
// to its type's price, introducing per-unit pricing cannot move a single existing figure: the override table
// starts empty, so every resolution takes the old path. These tests pin that, then pin that an override
// applies to exactly one unit and leaks to none.
//
// ⚠️ AND AN ABSENT OVERRIDE IS NOT €0. Reading a missing row as zero would sell a night for nothing. Only a
// missing TYPE price means genuinely unpriced, and that surfaces as NULL so a caller must decide.

// ⚠️ INSIDE THE FIXTURE'S PUBLISHED AVAILABILITY WINDOW (seedScenario opens 2026-09-01 → 2026-09-10). A date
// outside it books nothing at all, and the charge test would fail for a reason that has nothing to do with
// pricing — the fixture trap, which cost a debugging round here.
const URO_DATE = '2026-09-05';

const URO_TYPE_PRICE = 18000;

const URO_UNIT_PRICE = 24500;

/** A property with two units of ONE room type, and a type-level price for the night. */
function uroFixture(): array
{
    $s = seedScenario(2);                       // two units, same room type
    $plan = $s['ratePlan'];

    // The type price, written through the CERTIFIED writer — never a hand-inserted row.
    // ⚠️ The range is HALF-OPEN: start === end writes nothing, which reads exactly like a broken fixture.
    app(RateAndRestrictionService::class)->setRate($plan, URO_DATE, '2026-09-06', Money::of(URO_TYPE_PRICE, 'EUR'));

    expect((int) RateCalendar::query()->where('rate_plan_id', $plan->id)->whereDate('date', URO_DATE)->value('price_minor'))
        ->toBe(URO_TYPE_PRICE);

    return ['s' => $s, 'plan' => $plan, 'a' => $s['units'][0], 'b' => $s['units'][1]];
}

function uroOverride(array $f, Unit $unit, int $minor): UnitRateOverride
{
    return UnitRateOverride::create([
        'organization_id' => $f['s']['org']->id,
        'rate_plan_id' => $f['plan']->id,
        'unit_id' => $unit->id,
        'date' => URO_DATE,
        'price_minor' => $minor,
        'currency' => 'EUR',
    ]);
}

// ── 1. ⚠️⚠️ ZERO MOVEMENT — with no override, every unit resolves to today's type price ───────────
it('moves no price at all while no override exists', function () {
    $f = uroFixture();

    // ⚠️ THE WHOLE TABLE, hashed — not a spot-check. If the feature had altered rate_calendar in any way,
    // this fingerprint would change.
    $before = DB::table('rate_calendar')->orderBy('id')->get()->toJson();

    expect(NightlyRate::forUnit($f['plan']->id, $f['a']->id, URO_DATE))->toBe(URO_TYPE_PRICE)
        ->and(NightlyRate::forUnit($f['plan']->id, $f['b']->id, URO_DATE))->toBe(URO_TYPE_PRICE)
        // …identical to the type resolution itself, which is the definition of "nothing moved".
        ->and(NightlyRate::forType($f['plan']->id, URO_DATE))->toBe(URO_TYPE_PRICE);

    expect(DB::table('rate_calendar')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(UnitRateOverride::count())->toBe(0);
});

// ── 2. ⚠️⚠️ AN OVERRIDE APPLIES TO ITS UNIT — AND ITS SIBLING IS UNMOVED ──────────────────────────
it('prices one unit apart without touching its sibling', function () {
    $f = uroFixture();
    uroOverride($f, $f['a'], URO_UNIT_PRICE);

    expect(NightlyRate::forUnit($f['plan']->id, $f['a']->id, URO_DATE))->toBe(URO_UNIT_PRICE)
        // ⚠️ THE ISOLATION THAT MAKES THIS SAFE: the sibling shares the room type and has no override, so it
        // must still resolve to the type price. A leak here would silently re-price a unit nobody edited.
        ->and(NightlyRate::forUnit($f['plan']->id, $f['b']->id, URO_DATE))->toBe(URO_TYPE_PRICE)
        // …and the type price itself is untouched, so every OTHER consumer still sees what it always saw.
        ->and(NightlyRate::forType($f['plan']->id, URO_DATE))->toBe(URO_TYPE_PRICE);

    // Raw-verified: the override lives in its own table; rate_calendar still holds exactly one row for the night.
    expect((int) DB::table('rate_calendar')->where('rate_plan_id', $f['plan']->id)->whereDate('date', URO_DATE)->count())->toBe(1)
        ->and((int) DB::table('rate_calendar')->where('rate_plan_id', $f['plan']->id)->whereDate('date', URO_DATE)->value('price_minor'))->toBe(URO_TYPE_PRICE);
});

// ── 3. ⚠️ CLEARING AN OVERRIDE FALLS BACK — it does not leave the unit unpriced ───────────────────
it('falls back to the type price when an override is removed', function () {
    $f = uroFixture();
    $override = uroOverride($f, $f['a'], URO_UNIT_PRICE);
    expect(NightlyRate::forUnit($f['plan']->id, $f['a']->id, URO_DATE))->toBe(URO_UNIT_PRICE);

    $override->delete();

    expect(NightlyRate::forUnit($f['plan']->id, $f['a']->id, URO_DATE))->toBe(URO_TYPE_PRICE)
        ->and(NightlyRate::hasOverride($f['plan']->id, $f['a']->id, URO_DATE))->toBeFalse();
});

// ── 4. ⚠️⚠️ AN ABSENT OVERRIDE IS NOT €0 — and an unpriced night is NULL, not free ────────────────
it('never treats a missing price as zero', function () {
    $f = uroFixture();

    // No override for this unit → the type price, emphatically not 0.
    expect(NightlyRate::forUnit($f['plan']->id, $f['b']->id, URO_DATE))->not->toBe(0);

    // A night with NO type price either is genuinely unpriced → NULL, so a caller must decide rather than
    // selling the night for nothing.
    expect(NightlyRate::forUnit($f['plan']->id, $f['b']->id, '2027-01-01'))->toBeNull()
        ->and(NightlyRate::forType($f['plan']->id, '2027-01-01'))->toBeNull();
});

// ── 5. ⚠️⚠️ THE UNIQUE KEY IS REAL, NOT INERT — one override per unit per night ───────────────────
it('refuses a duplicate override for the same unit and night', function () {
    $f = uroFixture();
    uroOverride($f, $f['a'], URO_UNIT_PRICE);

    // ⚠️ Every column of the key is NOT NULL precisely so this fires. MySQL treats NULLs as distinct, so a
    // nullable "applies to all units" member would have made this index silently inert and allowed two
    // conflicting prices for one unit-night (the DO-CORRECTNESS-1 lesson, here on a money table).
    expect(fn () => uroOverride($f, $f['a'], 99999))->toThrow(QueryException::class);

    expect(UnitRateOverride::where('unit_id', $f['a']->id)->count())->toBe(1);
});

// ── 6. ⚠️ THE OVERRIDE IS TENANT-SCOPED like every other business row ─────────────────────────────
//
// ⚠️⚠️ THIS TEST WAS SKIPPED ON A FALSE JUSTIFICATION (audit 2026-08-29 · H-5). The skip read
// "covered by the shared BelongsToOrganization suite" — but TenantIsolationTest names FIVE models
// (Guest, Property, Reservation, ReservationUnit, Unit) while NINETY-SEVEN carry the trait, and
// `unit_rate_overrides` is not among the five. Verified by grepping the whole test tree: NOTHING
// anywhere asserted cross-org scoping for this table.
//
// ⚠️ THAT TABLE HOLDS `price_minor`. An unscoped read is another operator's PRICING.
//
// ⚠️ The original skip's stated reason was real, though its conclusion was not: seedScenario() sets the
// tenant context as a side effect, so "switch org by seeding another" is ambiguous about which context
// is live at assert time. Fixed by setting the context EXPLICITLY — the ambiguity was in the fixture,
// never in the boundary.
it('scopes overrides to the organization', function () {
    $f = uroFixture();
    $override = uroOverride($f, $f['a'], URO_UNIT_PRICE);

    $other = seedScenario(1);                                  // a genuinely separate tenant

    // ⚠️ EXPLICIT, not implied: act as org B.
    app(TenantContext::class)->set((string) $other['org']->id);

    // Org B cannot see org A's override…
    expect(UnitRateOverride::query()->count())->toBe(0)
        ->and(UnitRateOverride::query()->find($override->id))->toBeNull();

    // …and the row genuinely exists — so the 0 above is the SCOPE, not an empty table.
    // (An empty result from a filtered query is not an absence — §N.27.7.)
    expect((int) DB::table('unit_rate_overrides')->count())->toBe(1);

    // Back in org A it is visible again — proving the scope is a boundary, not a black hole.
    app(TenantContext::class)->set((string) $f['s']['org']->id);
    expect(UnitRateOverride::query()->count())->toBe(1);
});

// ── 7. ⚠️ THE MIGRATION IS REVERSIBLE WITH ZERO LOSS TO EXISTING PRICES ───────────────────────────
it('leaves every type price intact when the capability is rolled back', function () {
    $f = uroFixture();
    uroOverride($f, $f['a'], URO_UNIT_PRICE);

    $before = DB::table('rate_calendar')->orderBy('id')->get()->toJson();

    // ⚠️ Rolling back drops ONLY the new table — the prices every existing booking depends on are in
    // rate_calendar, which the migration never touched in either direction.
    DB::statement('DROP TABLE unit_rate_overrides');

    expect(DB::table('rate_calendar')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(NightlyRate::forType($f['plan']->id, URO_DATE))->toBe(URO_TYPE_PRICE);
});

// ═══ PHASE 2 — the override is CHARGED on the path where the unit is committed before the money ═══
//
// ⚠️ PHASE 1 REFUSED TO WIRE THIS, and the reason still governs where it may be wired. The storefront prices
// a ROOM TYPE and assigns a unit afterwards, so it cannot honour a per-unit price without asking the guest to
// choose a unit first. The operator's New Reservation path already validates a specific `unit_id` against the
// offer BEFORE charging — so there, and only there, the guest's actual room is known when money is decided.

// ── 8. ⚠️⚠️ ZERO MOVEMENT ON THE CHARGE PATH — no override ⇒ the caller's total stands untouched ───
it('leaves the stay total untouched when the unit has no override', function () {
    $f = uroFixture();

    // ⚠️ NULL, not a number. Re-deriving a total here would risk moving a DERIVED plan's price (rate plans
    // can carry `derived_from_rate_plan_id` + an adjustment), so "no override" must mean "don't touch it".
    expect(NightlyRate::stayTotalForUnit($f['plan']->id, $f['a']->id, URO_DATE, '2026-09-06'))->toBeNull()
        ->and(NightlyRate::stayTotalForUnit($f['plan']->id, $f['b']->id, URO_DATE, '2026-09-06'))->toBeNull();
});

// ── 9. ⚠️⚠️ AN OVERRIDE IS CHARGED — and the sibling's charge is unmoved ──────────────────────────
it('charges the per-unit price for an overridden unit and leaves its sibling alone', function () {
    $f = uroFixture();
    uroOverride($f, $f['a'], URO_UNIT_PRICE);

    // The overridden unit's stay total is its own price…
    expect(NightlyRate::stayTotalForUnit($f['plan']->id, $f['a']->id, URO_DATE, '2026-09-06'))->toBe(URO_UNIT_PRICE)
        // …and the sibling still has no per-unit figure at all, so its charge keeps whatever it was.
        ->and(NightlyRate::stayTotalForUnit($f['plan']->id, $f['b']->id, URO_DATE, '2026-09-06'))->toBeNull();
});

// ── 10. ⚠️ A PARTIAL OVERRIDE PRICES THE WHOLE STAY HONESTLY — not all-or-nothing ─────────────────
it('mixes an overridden night with type-priced nights', function () {
    $f = uroFixture();
    // Give the stay a second night at the type price…
    app(RateAndRestrictionService::class)->setRate($f['plan'], '2026-09-06', '2026-09-07', Money::of(URO_TYPE_PRICE, 'EUR'));
    // …and override only the FIRST night.
    uroOverride($f, $f['a'], URO_UNIT_PRICE);

    // ⚠️ night 1 = the unit's own price, night 2 = the type price. A stay is not all-or-nothing, and an
    // absent override on night 2 is emphatically not €0.
    expect(NightlyRate::stayTotalForUnit($f['plan']->id, $f['a']->id, URO_DATE, '2026-09-07'))
        ->toBe(URO_UNIT_PRICE + URO_TYPE_PRICE);
});

// ── 11. ⚠️⚠️ AN UNPRICED NIGHT IS REFUSED, NEVER SOLD FOR NOTHING ────────────────────────────────
it('refuses to total a stay containing an unpriced night', function () {
    $f = uroFixture();
    uroOverride($f, $f['a'], URO_UNIT_PRICE);

    // Night 2 has neither an override nor a type price. Returning URO_UNIT_PRICE here would silently give
    // the second night away free; NULL forces the caller to keep its own (unchanged) figure instead.
    expect(NightlyRate::stayTotalForUnit($f['plan']->id, $f['a']->id, URO_DATE, '2026-09-07'))->toBeNull();
});

// ── 12. ⚠️⚠️ THE CHARGE ITSELF — a real booking on an overridden unit is CHARGED the override ─────
// ⚠️ THIS TEST EXISTS BECAUSE A MUTATION EXPOSED ITS ABSENCE. Deleting the wiring in
// ReservationCreateController left every test above green: they proved the RESOLVER answers correctly and
// said nothing about whether the money path asks it. On a pricing change that is the only question that
// finally matters — so this books through the real operator route and reads the FOLIO.
it('charges a real booking the per-unit override, and a sibling the type price', function () {
    $f = uroFixture();
    $manager = operatorUser($f['s'], [], 'Hotel Manager');
    $session = ['current_property_id' => $f['s']['property']->id];

    // Price the whole stay window at the type price, then override unit A only.
    app(RateAndRestrictionService::class)->setRate($f['plan'], URO_DATE, '2026-09-07', Money::of(URO_TYPE_PRICE, 'EUR'));
    uroOverride($f, $f['a'], URO_UNIT_PRICE);

    $book = function (Unit $unit) use ($manager, $session, $f): ?int {
        test()->actingAs($manager)->withSession($session)->post(route('app.reservations.store'), [
            'room_type_id' => $f['s']['roomType']->id,
            'unit_id' => $unit->id,
            'rate_plan_id' => $f['plan']->id,
            'check_in' => URO_DATE,
            'check_out' => '2026-09-06',
            'guest_first_name' => 'Test', 'guest_last_name' => 'Guest', 'guest_email' => 't'.$unit->id.'@example.test',
            'adults' => 1, 'source' => 'direct', 'payment_method' => 'later',
        ]);

        // ⚠️ SELECTED BY UNIT, NOT BY "the newest row". Both bookings land in the same second, so ordering by
        // created_at re-read the FIRST reservation and the sibling appeared to be charged the override — a
        // test artefact that looked exactly like a money leak. The reservation is identified by the unit it
        // actually occupies.
        $reservationId = DB::table('reservation_units')->where('unit_id', $unit->id)->value('reservation_id');

        return $reservationId === null ? null : (int) DB::table('folio_items')
            ->join('folios', 'folios.id', '=', 'folio_items.folio_id')
            ->where('folios.reservation_id', $reservationId)->where('folio_items.type', 'room')
            ->sum('folio_items.amount_minor');
    };

    // ⚠️ THE OVERRIDDEN UNIT IS CHARGED ITS OWN PRICE — read from the folio, not from the resolver.
    expect($book($f['a']))->toBe(URO_UNIT_PRICE);

    // ⚠️ AND ITS SIBLING IS CHARGED THE TYPE PRICE — the override did not leak into another unit's money.
    expect($book($f['b']))->toBe(URO_TYPE_PRICE);
});
