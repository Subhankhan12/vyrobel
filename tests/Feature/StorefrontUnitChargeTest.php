<?php

use App\Models\Folio;
use App\Models\Reservation;
use App\Models\UnitRateOverride;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

// ⚠️ RefreshDatabase, AND ITS ABSENCE WAS A REAL REGRESSION I CAUSED. Without it this file COMMITS its orgs
// and properties, and TenantIsolationTest counts properties across all tenants under an explicit super-admin
// context — so every scenario built here inflated its total and red it. The storefront flow needs no genuine
// commit (unlike BookingImportTest, whose webhook enqueues afterCommit), so a transaction is correct here.

// D18-5 phase 3 — the storefront charge, proven CONTROL-FIRST.
//
// ⚠️⚠️ STEP ZERO IS A TEST THAT TOUCHES NO NEW CODE. The prior attempt (5329211, reverted) wrote the wiring
// soundly and then could not judge it: its harness never produced a folio, so every assertion failed on
// `null` — including a type-only control that exercised nothing that had changed. A proof that cannot book
// cannot speak about pricing. So the first test here books through the REAL route against UNCHANGED code and
// asserts a folio exists at today's price. Only once that is green does any unit code get written, and it
// stays afterwards as the zero-movement assertion.
//
// ⚠️⚠️ AND STEP ZERO IMMEDIATELY CORRECTED THE CANON. Both the brief and my own note on 5329211 recorded the
// storefront as TWO posts — `/checkout` to mint the quote, then `/book` to reserve and charge. It is ONE:
// `POST book/{property}/checkout` (`booking.reserve`) calls `quote()` AND `book()` in the same request and
// the folio exists when it returns. `booking.book` is a JSON API returning JsonResponse that **no view posts
// to** — grep `resources/views` for it and nothing comes back. That wrong model is exactly why the previous
// harness could not book: it posted to `/book`, which demands a `payment_token` the storefront never sends.

uses(RefreshDatabase::class);

const SUC_IN = '2026-09-02';

const SUC_OUT = '2026-09-04';            // 2 nights

const SUC_TYPE_MINOR = 12000;            // the room-type nightly rate the fixture publishes

const SUC_NIGHTS = 2;

/** Book through the REAL storefront route, exactly as the checkout form posts it. */
function sucBook(array $s, array $extra = []): void
{
    test()->post(route('booking.reserve', $s['property']->slug), array_merge([
        'room_type_id' => (string) $s['roomType']->id,
        'rate_plan_id' => (string) $s['ratePlan']->id,
        'check_in' => SUC_IN,
        'check_out' => SUC_OUT,
        'adults' => 2,
        'first_name' => 'Ada',
        'last_name' => 'Traveller',
        'email' => 'ada+'.uniqid().'@example.test',
        // ⚠️ Pay on arrival — a REAL choice the form offers, and the one that keeps the folio local: the card
        // path redirects away to Stripe, which would tell us nothing about what was charged.
        'payment_option' => 'on_arrival',
    ], acceptedTerms($s['property']), $extra));
}

/** ⚠️ RAW — the room charge the guest actually incurred, read from the folio itself. */
function sucRoomChargeMinor(): ?int
{
    $folio = Folio::query()->latest('id')->first();
    if (! $folio instanceof Folio) {
        return null;
    }

    return (int) DB::table('folio_items')->where('folio_id', $folio->getKey())
        ->where('type', 'room')->sum('amount_minor');
}

// ── STEP ZERO ⚠️⚠️ THE HARNESS ITSELF — a type-only booking must produce a folio, on UNCHANGED code ──
it('books a type-only storefront stay through the real route and writes a folio at the type price', function () {
    $s = publicPropertyScenario(units: 2, nightlyMinor: SUC_TYPE_MINOR);

    sucBook($s);

    // ⚠️ THE RESERVATION EXISTS — if this fails, the harness cannot book and NOTHING it says about pricing
    // means anything. That is the whole lesson of the reverted attempt.
    $reservation = Reservation::query()->latest('id')->first();
    expect($reservation)->not->toBeNull();

    // ⚠️ RAW: the folio carries the real room charge at today's type rate — 2 nights × €120.00.
    expect(sucRoomChargeMinor())->toBe(SUC_TYPE_MINOR * SUC_NIGHTS);

    // …and it is genuinely a storefront booking through the certified path, not a fixture shortcut.
    expect((string) $reservation->source)->toBe('direct')
        ->and(DB::table('reservation_units')->where('reservation_id', $reservation->getKey())->count())->toBe(1);
});

const SUC_OVERRIDE_MINOR = 19500;        // the chosen unit's OWN nightly price

/** Give ONE unit its own price for every night of the stay (the D18-5 model — a row per unit per night). */
function sucOverride(array $s, int $unitIndex, int $minor = SUC_OVERRIDE_MINOR): string
{
    $unitId = (string) $s['units'][$unitIndex]->id;
    foreach ([SUC_IN, '2026-09-03'] as $date) {
        UnitRateOverride::create([
            'organization_id' => $s['org']->id,
            'rate_plan_id' => $s['ratePlan']->id,
            'unit_id' => $unitId,
            'date' => $date,
            'price_minor' => $minor,
            'currency' => 'EUR',
        ]);
    }

    return $unitId;
}

/** ⚠️ The price the guest was SHOWN — the quote's own total, read raw. */
function sucQuotedMinor(): ?int
{
    $v = DB::table('booking_quotes')->latest('id')->value('total_minor');

    return $v === null ? null : (int) $v;
}

/** The unit the booking actually occupied. */
function sucBookedUnitId(): ?string
{
    $r = Reservation::query()->latest('id')->first();

    return $r === null ? null : (string) DB::table('reservation_units')->where('reservation_id', $r->getKey())->value('unit_id');
}

// ── 2. ⚠️⚠️ THE MONEY PROOF — a real override-unit booking, its FOLIO read ─────────────────────────
it('charges the chosen unit its own rate, and the folio equals the price shown', function () {
    $s = publicPropertyScenario(units: 2, nightlyMinor: SUC_TYPE_MINOR);
    $unitId = sucOverride($s, 0);

    sucBook($s, ['unit_id' => $unitId]);

    // ⚠️ RAW: the guest was charged the UNIT's price, not the room type's.
    expect(sucRoomChargeMinor())->toBe(SUC_OVERRIDE_MINOR * SUC_NIGHTS)
        ->and(sucRoomChargeMinor())->not->toBe(SUC_TYPE_MINOR * SUC_NIGHTS)
        // ⚠️ CHARGED = SHOWN. The folio and the quote agree to the cent — this is the whole guarantee, and
        // the pair is what makes it meaningful: either alone could be right while the other drifted.
        ->and(sucQuotedMinor())->toBe(SUC_OVERRIDE_MINOR * SUC_NIGHTS)
        // …and the booking really occupied the unit that was priced.
        ->and(sucBookedUnitId())->toBe($unitId);

    // ⚠️⚠️ THE STORED LINE PRICE, AND THIS ASSERTION EXISTS BECAUSE A MUTATION EXPOSED ITS ABSENCE.
    // Removing the resolver from book()'s `price_minor` left every test above GREEN — because the folio
    // charge is `postCharge($folio, 'room', …, $total)` where `$total` IS the QUOTE's total, so the folio
    // can never disagree with the shown price. But `price_minor` is not inert: AssignmentService writes it
    // to `reservation_units.price_minor`, so an unwrapped book() would have stored the room-type rate
    // against a unit the guest paid its own rate for — a quiet second version of the truth. This is what
    // separates "redundant belt-and-braces" from "unwired path"; it was the latter for THIS column.
    expect((int) DB::table('reservation_units')->where('unit_id', $unitId)->value('price_minor'))
        ->toBe(SUC_OVERRIDE_MINOR * SUC_NIGHTS);
});

// ── 3. ⚠️⚠️ ZERO MOVEMENT — a unit with no override charges byte-identically to today ──────────────
it('charges a no-override unit exactly what the room type has always charged', function () {
    $s = publicPropertyScenario(units: 2, nightlyMinor: SUC_TYPE_MINOR);
    $priced = sucOverride($s, 0);                       // unit 0 is priced…
    $plain = (string) $s['units'][1]->id;               // …unit 1 is not

    sucBook($s, ['unit_id' => $plain]);

    // ⚠️ The helper returns NULL for a unit with no override, so the charge takes the identical priceOver()
    // path it took before this feature existed. Zero movement is by construction, not by coincidence.
    expect(sucRoomChargeMinor())->toBe(SUC_TYPE_MINOR * SUC_NIGHTS)
        ->and(sucQuotedMinor())->toBe(SUC_TYPE_MINOR * SUC_NIGHTS)
        ->and(sucBookedUnitId())->toBe($plain)
        // ⚠️ …and the neighbouring override genuinely exists, so this is not passing for lack of data.
        ->and((int) DB::table('unit_rate_overrides')->where('unit_id', $priced)->count())->toBe(SUC_NIGHTS);
});

// ── 4. ⚠️ SHOWN = ENFORCED — a unit that is not genuinely free is dropped, never trusted ───────────
it('ignores a submitted unit that is not actually free and books type-level instead', function () {
    $s = publicPropertyScenario(units: 2, nightlyMinor: SUC_TYPE_MINOR);
    $unitId = sucOverride($s, 0);

    // Take the unit first, through the certified path — now it is genuinely occupied for these dates.
    sucBook($s, ['unit_id' => $unitId]);
    expect(sucBookedUnitId())->toBe($unitId);

    // A stale tab submits the SAME unit again. It is no longer free, so it must be dropped — and the guest
    // must NOT be charged that unit's rate for a unit they cannot have.
    sucBook($s, ['unit_id' => $unitId]);

    expect(sucRoomChargeMinor())->toBe(SUC_TYPE_MINOR * SUC_NIGHTS)
        ->and(sucBookedUnitId())->not->toBe($unitId);
});
