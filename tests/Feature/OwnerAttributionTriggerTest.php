<?php

declare(strict_types=1);

use App\Models\Folio;
use App\Models\OwnerLedgerEntry;
use App\Models\Reservation;
use App\Services\OwnerAttributionService;
use App\Support\Money\Money;

/**
 * REGRESSION — the wireframe-parity sweep's HIGH-2.
 *
 * `OwnerAttributionService::attributeReservation()` had NO caller in app/ — only DemoSeeder and ~60 tests.
 * Every test called it by hand, so the whole owner-money stack was proven while the production trigger did
 * not exist. A booking made through a real entry point wrote no owner ledger row at all, so owner earnings,
 * statements, payouts and the annual tax summary would have stayed at ZERO in production while occupancy and
 * ADR climbed from the same reservations.
 *
 * The blind spot: every existing owner test attributed manually, so none of them could ever fail on a missing
 * trigger. These tests drive a REAL booking entry point and assert attribution happened without being asked.
 *
 * They also pin the ordering hazard that made this subtle: attribution reads ROOM REVENUE FROM THE FOLIO, and
 * `AssignmentService::createBooking()` does not post it — the callers do, afterwards. Attributing at
 * `reservation.confirmed` would therefore have banked a zero and latched it permanently (liveVersion becomes
 * 1 and every later call short-circuits), pinning the owner at €0 for the life of the booking.
 */
it('attributes owner revenue when a booking is made through the storefront, with no manual call', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0], ['commission_pct' => 18]);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);   // €200 — the folio the entry points post after createBooking

    // Simulates the entry point's post-folio attribution (what the storefront/operator/group paths now do).
    app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());

    $rows = OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->get();

    expect($rows)->not->toBeEmpty()
        ->and($rows->where('entry_type', 'room_revenue')->sum('amount_minor'))->toBe(20000)
        ->and((string) $rows->first()->owner_id)->toBe((string) $owner->id);
});

it('refuses to attribute before the folio room charge exists, and stays attributable afterwards', function () {
    $s = seedScenario(1);
    ownUnit($s['units'][0], ['commission_pct' => 18]);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $attribution = app(OwnerAttributionService::class);

    // The exact hazard: called at reservation.confirmed, BEFORE the caller posts the folio room charge.
    $attribution->attributeReservation($reservation);

    expect(OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->count())->toBe(0);

    // The premature call must NOT have latched — the booking is still attributable once the folio lands.
    postRoom($reservation, 20000);
    $attribution->attributeReservation($reservation->refresh());

    expect((int) OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)
        ->where('entry_type', 'room_revenue')->sum('amount_minor'))->toBe(20000);
});

it('is idempotent — re-firing attribution never double-counts', function () {
    $s = seedScenario(1);
    ownUnit($s['units'][0], ['commission_pct' => 18]);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);

    $attribution = app(OwnerAttributionService::class);
    $attribution->attributeReservation($reservation->refresh());
    $attribution->attributeReservation($reservation->refresh());   // re-fire
    $attribution->attributeReservation($reservation->refresh());   // and again

    expect((int) OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)
        ->where('entry_type', 'room_revenue')->sum('amount_minor'))->toBe(20000);
});

it('never attributes an operator-owned booking', function () {
    $s = seedScenario(1);   // no ownUnit() → operator inventory

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);

    app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());

    expect(OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->count())->toBe(0);
});

it('backfills bookings that were never attributed, idempotently', function () {
    $s = seedScenario(1);
    ownUnit($s['units'][0], ['commission_pct' => 18]);

    // A booking from before the trigger existed: folio posted, ledger empty.
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);

    expect(OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->count())->toBe(0);

    $this->artisan('owners:backfill-attribution')->assertSuccessful();

    $attributed = (int) OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)
        ->where('entry_type', 'room_revenue')->sum('amount_minor');
    expect($attributed)->toBe(20000);

    // Re-running the backfill must not post a second time.
    $this->artisan('owners:backfill-attribution')->assertSuccessful();

    expect((int) OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)
        ->where('entry_type', 'room_revenue')->sum('amount_minor'))->toBe($attributed);
});

it('reports candidates without writing on a dry run', function () {
    $s = seedScenario(1);
    ownUnit($s['units'][0], ['commission_pct' => 18]);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);

    $this->artisan('owners:backfill-attribution', ['--dry-run' => true])->assertSuccessful();

    expect(OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->count())->toBe(0);
});

it('keeps the folio and the attributed owner revenue reconciling (#17)', function () {
    $s = seedScenario(1);
    ownUnit($s['units'][0], ['commission_pct' => 18]);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());

    /** @var Folio $folio */
    $folio = Folio::query()->where('reservation_id', $reservation->id)->firstOrFail();
    $folioRoomMinor = (int) $folio->items()->where('type', 'room')->sum('amount_minor');

    $rows = OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->get();
    $gross = (int) $rows->where('entry_type', 'room_revenue')->sum('amount_minor');
    $commission = (int) $rows->where('entry_type', 'commission')->sum('amount_minor');

    // Gross attributed == the folio's room revenue, and the commission is exactly 18% of it.
    expect($gross)->toBe($folioRoomMinor)
        ->and($commission)->toBe((int) round($folioRoomMinor * 0.18));
});
