<?php

declare(strict_types=1);

use App\Models\Fee;
use App\Models\Folio;
use App\Models\Reservation;
use App\Models\Tax;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

// GUEST MONEY-PATH PROOF — the storefront's DISPLAYED grand total is what the folio actually CHARGES.
//
// ⚠️⚠️ WHAT WAS ALREADY PROVEN, AND WHY THIS FILE IS NARROWER THAN THE BRIEF ASSUMED. Confirm-first found the
// storefront money path is NOT unproven: {@see StorefrontUnitChargeTest} "step zero" already books a type-only
// stay through the real `booking.reserve` route and asserts a real folio, and {@see SeasonalRateTierTest}
// already asserts the full canon chain — folio room charge === `reservation_units.price_minor` ===
// `booking_quotes.total_minor` — through that same storefront path. `book()` has run live in this suite for a
// while; the guest deep-parity audit's "UNVERIFIED-WITHOUT-MUTATION" was a statement about what a READ-ONLY
// audit could see, not about the system.
//
// ⚠️⚠️ THE GENUINE GAP, WHICH THIS FILE CLOSES. Both existing proofs run on `publicPropertyScenario`, which
// creates **no taxes and no fees**, and both sum ONLY `folio_items.type = 'room'`. So the chain was proven for
// the ROOM LINE on a tax-free property. What had never been proven is the thing the whole storefront display
// rests on: that the **grand total a guest is SHOWN** — room + VAT + city tax + cleaning, the €373.65 shape the
// audit measured and the F2/F3 card copy describes — is the amount the folio actually CHARGES. That is #17
// through the storefront path with taxes and fees present.
//
// ⚠️ MONEY-AS-SEEDED: RefreshDatabase — the booking lives and dies inside the transaction; the persisted demo
// database never sees it.
//
// ⚠️ SCOPE OF "CHARGED": the folio's charge amount, not a live Stripe capture (go-live keys are not set). The
// booking uses `payment_option = on_arrival`, the real choice that keeps the folio local — the card path
// redirects to Stripe and would say nothing about what was charged.

uses(RefreshDatabase::class);

const SGT_IN = '2026-09-02';

const SGT_OUT = '2026-09-05';           // 3 nights

const SGT_NIGHTS = 3;

const SGT_NIGHTLY = 12000;              // €120.00 — the fixture's own published rate, never hardcoded elsewhere

/** A property priced like the real one the audit measured: 23% exclusive VAT · per-person-night city tax · flat cleaning. */
function sgtProperty(): array
{
    $s = publicPropertyScenario(units: 2, nightlyMinor: SGT_NIGHTLY);

    Tax::create(['property_id' => $s['property']->id, 'name' => 'VAT', 'mode' => 'percentage',
        'rate' => 23.0, 'inclusive' => false, 'currency' => 'EUR']);
    Tax::create(['property_id' => $s['property']->id, 'name' => 'City tax', 'mode' => 'per_person_night',
        'amount_minor' => 250, 'currency' => 'EUR']);
    Fee::create(['property_id' => $s['property']->id, 'name' => 'Cleaning fee', 'mode' => 'flat',
        'amount_minor' => 4500, 'optional' => false, 'currency' => 'EUR']);

    return $s;
}

/** The grand total the guest is SHOWN on the storefront card, parsed from the rendered page. */
function sgtDisplayedTotalMinor(array $s, int $adults = 2): int
{
    $html = test()->get("/book/{$s['property']->slug}?check_in=".SGT_IN.'&check_out='.SGT_OUT."&adults={$adults}")
        ->assertOk()->getContent();

    // ⚠️ READ WHAT THE GUEST READS. Deriving the expected figure from StayEstimator here would make this test
    // agree with the estimator by construction and prove nothing about the SHOWN price.
    expect($html)->toContain('total');
    preg_match('/€([\d.,]+) total/', $html, $m);
    expect($m)->not->toBeEmpty();

    return (int) round((float) str_replace(',', '', $m[1]) * 100);
}

/** Book through the REAL storefront route, exactly as the checkout form posts it. */
function sgtBook(array $s, array $extra = []): void
{
    test()->post(route('booking.reserve', $s['property']->slug), array_merge([
        'room_type_id' => (string) $s['roomType']->id,
        'rate_plan_id' => (string) $s['ratePlan']->id,
        'check_in' => SGT_IN,
        'check_out' => SGT_OUT,
        'adults' => 2,
        'first_name' => 'Ada',
        'last_name' => 'Traveller',
        'email' => 'ada+'.uniqid().'@example.test',
        'payment_option' => 'on_arrival',
    ], $extra));
}

// ── 1. ⚠️⚠️ THE PROOF — what the storefront SHOWS is what the folio CHARGES, taxes and fees included ──────
it('charges exactly the grand total the storefront displayed, with VAT, city tax and cleaning on the folio', function () {
    $s = sgtProperty();

    $displayed = sgtDisplayedTotalMinor($s);

    // ⚠️ THE DISPLAY IS REAL AND COMPOSITE — room 36000 + VAT 8280 + city tax 1500 + cleaning 4500. If the
    // fixture ever loses its taxes/fees this figure collapses to the room alone and the test would be
    // proving nothing, so it is asserted explicitly rather than merely captured.
    expect($displayed)->toBe(SGT_NIGHTLY * SGT_NIGHTS + 8280 + 1500 + 4500)
        ->and($displayed)->toBe(50280);

    sgtBook($s);

    // ⚠️⚠️ PROBE THE POSITIVE — a `booking.reserve` POST returns 200 when it re-renders on a validation
    // error, having created NOTHING. The artifacts are the evidence, never the status code.
    $reservation = Reservation::query()->latest('id')->first();
    expect($reservation)->not->toBeNull();
    $folio = Folio::query()->where('reservation_id', $reservation->getKey())->first();
    expect($folio)->not->toBeNull();

    // ⚠️⚠️ THE HEADLINE: the WHOLE folio — every item, not just the room line — equals what was displayed.
    $folioSum = (int) DB::table('folio_items')->where('folio_id', $folio->getKey())->sum('amount_minor');
    expect($folioSum)->toBe($displayed);

    // …and the folio's own stored total agrees with its items (#17 internally).
    expect((int) $folio->total_minor)->toBe($folioSum);

    // ⚠️ THE TAXES AND FEES REALLY LANDED as their own lines — a folio that charged the right total by
    // charging one fat room line would pass the sum above while destroying the tax documents.
    $byType = DB::table('folio_items')->where('folio_id', $folio->getKey())
        ->select('type', DB::raw('SUM(amount_minor) as amt'))->groupBy('type')->pluck('amt', 'type');
    expect((int) $byType['room'])->toBe(SGT_NIGHTLY * SGT_NIGHTS)
        ->and($byType->keys()->count())->toBeGreaterThan(1);
});

// ── 2. ⚠️⚠️ THE CANON CHAIN, with taxes present — folio room === price_minor === quote total ──────────────
it('reconciles the room charge across the folio, the reservation unit and the materialised quote', function () {
    $s = sgtProperty();

    sgtBook($s);

    $reservation = Reservation::query()->latest('id')->first();
    expect($reservation)->not->toBeNull();

    $folio = Folio::query()->where('reservation_id', $reservation->getKey())->firstOrFail();
    $roomCharge = (int) DB::table('folio_items')->where('folio_id', $folio->getKey())
        ->where('type', 'room')->sum('amount_minor');

    $priceMinor = (int) DB::table('reservation_units')
        ->where('reservation_id', $reservation->getKey())->value('price_minor');

    // ⚠️⚠️ CLOSING AUDIT F4. The seeded demo has ZERO `booking_quotes` rows because the seeder books through
    // the OPERATOR path (`AssignmentService`), which mints no quote — so a read-only audit could never verify
    // this link. Driving the STOREFRONT path is what writes it, and here it is.
    $quoteTotal = DB::table('booking_quotes')->latest('id')->value('total_minor');
    expect($quoteTotal)->not->toBeNull();

    // ⚠️ THE CANON MONEY-PROOF SHAPE, now with taxes and fees on the property.
    expect($roomCharge)->toBe(SGT_NIGHTLY * SGT_NIGHTS)
        ->and($priceMinor)->toBe($roomCharge)
        ->and((int) $quoteTotal)->toBe($roomCharge);
});

// ── 3. ⚠️ THE QUOTE IS MATERIALISED BY THIS PATH — one booking, one quote row ─────────────────────────────
it('materialises exactly one booking_quotes row when a guest books through the storefront', function () {
    $s = sgtProperty();

    // ⚠️ THE PREMISE: none exist before — so a row afterwards is genuinely written by this path, which is the
    // whole content of the F4 finding.
    expect(DB::table('booking_quotes')->count())->toBe(0);

    sgtBook($s);

    expect(DB::table('booking_quotes')->count())->toBe(1);

    $quote = DB::table('booking_quotes')->first();
    expect((string) $quote->property_id)->toBe((string) $s['property']->id)
        ->and((int) $quote->total_minor)->toBe(SGT_NIGHTLY * SGT_NIGHTS);
});
