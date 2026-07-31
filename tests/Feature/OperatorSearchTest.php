<?php

use App\Models\Guest;
use App\Models\Owner;
use App\Services\RateAndRestrictionService;
use App\Services\SearchService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OPERATOR GLOBAL SEARCH (Phase 1 · Sub-gate 2) — a real cross-entity search (reservations/guests/units/owners),
// TENANT-scoped, with the encrypted-contact honesty guard (guests by NAME only; email/phone can't be searched —
// blind index deferred; the limitation is surfaced, never a silent empty result).

/** seedScenario + a rate so a bookable reservation exists to find. */
function searchScenario(int $units = 2): array
{
    $s = seedScenario($units);
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-10', Money::of(10000, 'EUR'));

    return $s;
}

// ── 1. Reservations — found by reference AND by lead-guest name ───────────────────────────────────
it('finds a reservation by its reference and by the guest name', function () {
    $s = searchScenario();
    $s['guest']->update(['first_name' => 'Zeta', 'last_name' => 'Searchable']);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $manager = operatorUser($s, [], 'Hotel Manager');

    $this->actingAs($manager)->get('/app/search?q='.$reservation->reference)
        ->assertOk()->assertSee($reservation->reference)
        ->assertSee(route('app.reservations.show', $reservation->id));   // links to the real detail

    $this->actingAs($manager)->get('/app/search?q=Searchable')
        ->assertOk()->assertSee($reservation->reference);   // found via the guest name
});

// ── 2. ⚠️ Guests — by NAME and by EXACT email via the blind index (#102), never a ciphertext LIKE ──
it('searches guests by name and by their exact email via the blind index', function () {
    $s = searchScenario();
    Guest::create(['first_name' => 'Aria', 'last_name' => 'Nightingale', 'email' => 'aria.secret@hidden.test', 'phone' => '+441234567890']);
    $manager = operatorUser($s, [], 'Hotel Manager');

    // By name → found.
    $this->actingAs($manager)->get('/app/search?q=Nightingale')
        ->assertOk()->assertSee('Aria Nightingale');

    // By EXACT email → found via the deterministic blind index (email_hash), NOT a ciphertext LIKE (#28).
    $this->actingAs($manager)->get('/app/search?q=aria.secret@hidden.test')
        ->assertOk()->assertSee('Aria Nightingale');

    // A DIFFERENT email does not match — the blind index is exact, never a partial/ciphertext match.
    $this->actingAs($manager)->get('/app/search?q=aria.secret@other.test')
        ->assertOk()->assertDontSee('Aria Nightingale');
});

// ── 3. Units — found by code ─────────────────────────────────────────────────────────────────────
it('finds a unit by its code', function () {
    $s = searchScenario();
    $s['units'][0]->update(['code' => 'ZQ9']);
    $manager = operatorUser($s, [], 'Hotel Manager');

    $this->actingAs($manager)->get('/app/search?q=ZQ9')->assertOk()->assertSee('ZQ9');
});

// ── 4. Owners — found by name ────────────────────────────────────────────────────────────────────
it('finds an owner by name and links to the owner detail', function () {
    $s = searchScenario();
    $owner = Owner::factory()->create(['name' => 'Okonkwo Holdings']);
    $manager = operatorUser($s, [], 'Hotel Manager');

    $this->actingAs($manager)->get('/app/search?q=Okonkwo')
        ->assertOk()->assertSee('Okonkwo Holdings')
        ->assertSee(route('app.owners.show', $owner->id));
});

// ── 5. ⚠️ TENANT ISOLATION — another org's data never appears ─────────────────────────────────────
it('never returns another organization’s records', function () {
    $a = searchScenario();
    $guestA = Guest::create(['first_name' => 'Xander', 'last_name' => 'Crossborder']);   // org A

    $b = seedScenario(1);   // a SECOND org (tenant context now B)
    $guestB = Guest::create(['first_name' => 'Xander', 'last_name' => 'Crossborder']);   // org B

    $operatorA = operatorUser($a, [], 'Hotel Manager');   // belongs to org A

    // The A operator searches a term BOTH orgs' guests match — only org A's appears; org B's id never leaks.
    $this->actingAs($operatorA)->get('/app/search?q=Xander')
        ->assertOk()
        ->assertSee(route('app.guests.show', $guestA->id))
        ->assertDontSee($guestB->id);
});

// ── 6. PERMISSION — the page is gated on reservations.view ───────────────────────────────────────
it('refuses the search page to a role without reservations.view', function () {
    $s = searchScenario();
    $housekeeper = operatorUser($s, ['name' => 'Hana House-Keeping'], 'Housekeeping');

    $this->actingAs($housekeeper)->get('/app/search?q=anything')->assertForbidden();
});

// ── 7. PER-ENTITY SCOPING — a group appears only if the viewer holds its view permission ─────────
it('gates each result group on the viewer’s abilities', function () {
    $s = searchScenario();
    $s['units'][0]->update(['code' => 'ZQ9']);
    Guest::create(['first_name' => 'Zeta', 'last_name' => 'Searchable']);
    $search = app(SearchService::class);

    // With units OFF, a matching unit is NOT returned even though it exists; guests ON still returns.
    $withoutUnits = $search->search('Z', ['guests' => true, 'units' => false]);
    expect($withoutUnits['units'])->toBe([])
        ->and(collect($withoutUnits['guests'])->pluck('name'))->toContain('Zeta Searchable');

    // With units ON, the same query returns the unit.
    $withUnits = $search->search('ZQ9', ['guests' => true, 'units' => true]);
    expect(collect($withUnits['units'])->pluck('code'))->toContain('ZQ9');
});

// ── 8. Real role, per-entity in HTTP — a Reservation Agent (no units.view) gets no unit results ──
it('shows a Reservation Agent guests but not units (no units.view)', function () {
    $s = searchScenario();
    $s['units'][0]->update(['code' => 'ZUNIQUECODE']);
    Guest::create(['first_name' => 'Zeta', 'last_name' => 'Searchable']);
    $agent = operatorUser($s, [], 'Reservation Agent');

    // Guests DO show — the Reservation Agent holds guests.view.
    $this->actingAs($agent)->get('/app/search?q=Zeta')->assertOk()->assertSee('Zeta Searchable');

    // A UNIT-ONLY match returns nothing for a role without units.view → the honest empty state (not the unit).
    // (Asserting the empty state avoids the query echoing in the search box.)
    $this->actingAs($agent)->get('/app/search?q=ZUNIQUECODE')->assertOk()->assertSee('No matches for');
});
