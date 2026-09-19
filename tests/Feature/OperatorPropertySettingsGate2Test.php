<?php

use App\Models\Fee;
use App\Models\Folio;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Tax;
use App\Services\FolioService;
use App\Services\PropertyRemovalService;
use App\Support\Folio\FolioTotals;
use App\Support\Folio\StayEstimator;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #98 GATE 2 — Tax & Fees as the REAL folio source (inclusive VAT honored + fees posted; the sample-stay
// reconciles to a real folio to the cent, #17) + the careful Remove-property archive-not-destroy.

/** Post the room + the property's applicable taxes/fees to a folio using the SAME shared rules the wizard uses. */
function buildFolioFromConfig(Property $property, Money $room, int $nights, int $persons): Folio
{
    $folios = app(FolioService::class);
    $reservation = Reservation::create([
        'property_id' => $property->id, 'reference' => 'R-'.uniqid(), 'status' => 'confirmed',
        'check_in' => '2026-09-01', 'check_out' => '2026-09-05', 'currency' => 'EUR',
    ]);
    $folio = Folio::create(['reservation_id' => $reservation->id, 'currency' => 'EUR']);
    $folios->postCharge($folio, 'room', 'Room', $room);

    Tax::query()->where('property_id', $property->id)->get()
        ->filter(fn (Tax $t): bool => StayEstimator::taxApplies($t, 'EUR'))
        ->each(fn (Tax $t) => $folios->applyTax($folio, $t, $room, $nights, $persons));
    Fee::query()->where('property_id', $property->id)->get()
        ->filter(fn (Fee $f): bool => StayEstimator::feeApplies($f, 'EUR'))
        ->each(fn (Fee $f) => $folios->applyFee($folio, $f, $room, $nights, $persons));

    return $folio->refresh();
}

// ── ⚠️ THE SAMPLE-STAY RECONCILES TO A REAL FOLIO (inclusive VAT informational; cleaning posted) ────
it('reconciles the sample-stay to a real folio to the cent', function () {
    $s = seedScenario(1);
    $property = $s['property'];
    Tax::create(['property_id' => $property->id, 'name' => 'VAT', 'kind' => 'vat', 'mode' => 'percentage', 'rate' => 6.0, 'inclusive' => true]);
    Tax::create(['property_id' => $property->id, 'name' => 'City tax', 'kind' => 'city_tax', 'mode' => 'per_person_night', 'amount_minor' => 200, 'currency' => 'EUR']);
    Fee::create(['property_id' => $property->id, 'name' => 'Cleaning fee', 'kind' => 'cleaning', 'mode' => 'flat', 'amount_minor' => 4500, 'currency' => 'EUR', 'optional' => false]);

    $room = Money::of(60000, 'EUR');   // €150 × 4
    $estimate = StayEstimator::estimate(
        Tax::where('property_id', $property->id)->get(),
        Fee::where('property_id', $property->id)->get(),
        $room, 4, 2, 'EUR',
    );

    // Room 600 + city tax (2×2×4=16) + cleaning 45; VAT 6% INCLUSIVE is informational (not added) → €661.00.
    expect($estimate['total']->amountMinor())->toBe(66100);

    // A REAL folio built from the SAME config totals identically (#17).
    $folio = buildFolioFromConfig($property, $room, 4, 2);
    expect(FolioTotals::for($folio)['charges_minor'])->toBe($estimate['total']->amountMinor());
});

// ── Inclusive vs exclusive VAT — the folio HONORS the flag (the fix) ────────────────────────────────
it('does not charge an inclusive VAT, but charges an exclusive one', function () {
    $s = seedScenario(1);
    $property = $s['property'];
    $vat = Tax::create(['property_id' => $property->id, 'name' => 'VAT', 'kind' => 'vat', 'mode' => 'percentage', 'rate' => 10.0, 'inclusive' => true]);
    $room = Money::of(50000, 'EUR');

    // Inclusive → the folio total is just the room (VAT already inside it).
    expect(FolioTotals::for(buildFolioFromConfig($property, $room, 4, 2))['charges_minor'])->toBe(50000);

    // Flip to exclusive → the folio now adds 10% (€50).
    $vat->update(['inclusive' => false]);
    expect(FolioTotals::for(buildFolioFromConfig($property, $room, 4, 2))['charges_minor'])->toBe(55000);
});

// ── Add-a-fee creates a real fee that then posts to a folio ─────────────────────────────────────────
it('adds a custom fee that posts to a folio', function () {
    $s = seedScenario(1);
    $property = $s['property'];

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.settings.fees.add'), [
        'name' => 'Resort fee', 'basis' => 'per_night', 'amount' => '12.50',
    ])->assertRedirect(route('app.settings'))->assertSessionHas('settings_notice');

    $fee = Fee::query()->where('property_id', $property->id)->where('name', 'Resort fee')->firstOrFail();
    expect((string) $fee->mode)->toBe('per_night')->and((int) $fee->amount_minor)->toBe(1250);

    // It posts on a folio: €12.50 × 4 nights = €50.
    expect(FolioTotals::for(buildFolioFromConfig($property, Money::of(60000, 'EUR'), 4, 2))['charges_minor'])->toBe(65000);
});

// ── An OPTIONAL fee is NOT auto-charged ─────────────────────────────────────────────────────────────
it('does not auto-charge an optional-at-booking fee', function () {
    $s = seedScenario(1);
    $property = $s['property'];
    Fee::create(['property_id' => $property->id, 'name' => 'Spa', 'kind' => 'other', 'mode' => 'flat', 'amount_minor' => 3000, 'currency' => 'EUR', 'optional' => true]);

    // Optional → not applied → the folio is just the room.
    expect(FolioTotals::for(buildFolioFromConfig($property, Money::of(60000, 'EUR'), 4, 2))['charges_minor'])->toBe(60000);
});

// ── The page RENDERS the reconciling sample-stay (GET — guards the raw-blade / 500 trap) ───────────
it('renders the reconciling sample-stay on the settings page', function () {
    $s = seedScenario(1);
    $property = $s['property'];
    Tax::create(['property_id' => $property->id, 'name' => 'VAT', 'kind' => 'vat', 'mode' => 'percentage', 'rate' => 6.0, 'inclusive' => true]);
    Tax::create(['property_id' => $property->id, 'name' => 'City tax', 'kind' => 'city_tax', 'mode' => 'per_person_night', 'amount_minor' => 200, 'currency' => 'EUR']);
    Fee::create(['property_id' => $property->id, 'name' => 'Cleaning fee', 'kind' => 'cleaning', 'mode' => 'flat', 'amount_minor' => 4500, 'currency' => 'EUR', 'optional' => false]);

    $response = $this->actingAs(operatorUser($s, [], 'Property Owner'))->get('/app/settings');
    $response->assertOk()
        ->assertSee('On a sample 4-night stay')
        ->assertSee('Guest pays')
        ->assertSee('€661.00')          // room 600 + city 16 + cleaning 45 (VAT inclusive informational) — reconciles
        ->assertSee('Remove from group')
        ->assertSee('Type the property name');
    assertNoRawBlade($response);
});

// ── ⚠️ REMOVE = archive-not-destroy: scheduled, soft-deleted after effective, records RETAINED ──────
it('schedules removal then archives (soft-delete) without destroying records', function () {
    $s = seedScenario(1);
    $property = $s['property'];
    $reservation = Reservation::create([
        'property_id' => $property->id, 'reference' => 'R-KEEP', 'status' => 'checked_out',
        'check_in' => '2026-07-01', 'check_out' => '2026-07-03', 'currency' => 'EUR',
    ]);

    // Schedule via the real route (typed name must match).
    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.settings.remove.schedule'), [
        'confirm_name' => (string) $property->name,
    ])->assertRedirect(route('app.settings'))->assertSessionHas('settings_notice');
    expect($property->fresh()->removal_scheduled_at)->not->toBeNull();

    // ⚠️⚠️ RUN IT THE WAY THE SCHEDULER DOES (audit 2026-08-29 · M-8). This previously called
    // PropertyRemovalService::processDue() directly, INSIDE the tenant context that seedScenario() and
    // actingAs() leave behind — a shape the daily 02:00 run NEVER has. The command therefore threw
    // TenantContextMissingException every night while this test stayed green, because it was proving the
    // service works in a situation that does not occur.
    //
    // ⚠️ FAILURE CLASS: "a test that encodes the bug by exercising a shape production never takes". It is a
    // SIBLING of "a test proving something ADJACENT to what you think it proves" (the E-1 case) but not the
    // same: there the assertion was true and simply about the wrong thing; here the assertion is about the
    // right thing, under a SETUP that production never reproduces. The tell is different — an adjacent test
    // looks under-scoped, this one looks thorough.
    app(TenantContext::class)->forget();

    $this->artisan('properties:process-removals')->assertExitCode(0);

    // The COMMAND must run with no context; the test's own inspection below needs one restored.
    app(TenantContext::class)->set((string) $s['org']->id);

    // Archived = SOFT-deleted (trashed), NOT destroyed; the reservation record is RETAINED.
    expect(Property::withTrashed()->find($property->id)->trashed())->toBeTrue();
    expect(Reservation::find($reservation->id))->not->toBeNull();
});

// ── Wrong typed name does NOT schedule removal ──────────────────────────────────────────────────────
it('refuses removal when the typed name does not match', function () {
    $s = seedScenario(1);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.settings.remove.schedule'), [
        'confirm_name' => 'Wrong Name',
    ])->assertRedirect(route('app.settings'))->assertSessionHas('settings_error');

    expect($s['property']->fresh()->removal_scheduled_at)->toBeNull();
});

// ── A scheduled removal is cancellable ──────────────────────────────────────────────────────────────
it('cancels a scheduled removal', function () {
    $s = seedScenario(1);
    app(PropertyRemovalService::class)->schedule($s['property'], 'tester');
    expect($s['property']->fresh()->removal_scheduled_at)->not->toBeNull();

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.settings.remove.cancel'))
        ->assertRedirect(route('app.settings'))->assertSessionHas('settings_notice');
    expect($s['property']->fresh()->removal_scheduled_at)->toBeNull();
});

// ── ⚠️⚠️ F-58 — PER-PROPERTY REMOVAL VISIBILITY IS ENOUGH (founder ruling, 2026-09-19) ──────────────
/**
 * ⚠️⚠️ THIS PINS A DECISION, NOT A MECHANISM, AND IT IS THE DANGEROUS KIND. The founder ruled that seeing a
 * pending removal on that property's own Settings page is ENOUGH — **no portfolio-wide list of pending
 * removals, and no notification when one defers.** A later pass reading "you can only find this by opening
 * the property" as a gap would build one of those, which is work nobody asked for.
 *
 * ⚠️ WHAT THAT RULING RESTS ON IS THE THING ASSERTED HERE: the date and the cancel control genuinely RENDER.
 * 🔬 Nothing asserted that before FIX 46 — `RemovalDateIsReDerivedTest` names the sentence in a docblock and
 * pins the DATE BEING REWRITTEN, which is a different claim. If this page stopped showing it, per-property
 * visibility would be zero visibility and the ruling would rest on nothing.
 *
 * ⚠️ NOT A DUPLICATE: FIX 44 pinned that a deferral writes the new date forward. This pins that the operator
 * can see it at all.
 */
it('⚠️⚠️ RULED 2026-09-19 (F-58) — a pending removal is visible on its own property, and that is enough', function () {
    $s = seedScenario(1);

    app(PropertyRemovalService::class)->schedule($s['property']->fresh(), 'ops@example.test');

    $response = $this->actingAs(operatorUser($s, [], 'Property Owner'))->get('/app/settings');

    $response->assertOk()
        ->assertSee('Removal is scheduled')
        ->assertSee('the property stays live until the last checkout on')
        ->assertSee('You can cancel any time before it runs.');

    // ⚠️ AND THE DATE ITSELF IS RENDERED — the sentence without it would be an empty promise.
    $response->assertSee($s['property']->fresh()->removal_effective_at->format('j M Y'));

    assertNoRawBlade($response);
});
