<?php

use App\Models\Guest;
use App\Models\Owner;
use App\Models\PayoutMethod;
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

// ═══════════════════════════════════════════════════════════════════════════════════════════════════
// P28 (design-parity) — ⚠️ THE PII PERIMETER. Search is the one surface that aggregates guests,
// reservations, owners and units into a single list, so it is the easiest place to widen #28 by
// accident. The DESIGN's guest row carries `lukas.meyer@mail.com · Munich, Germany`; contact PII
// renders on the Guests CRM ONLY. These pin the refusal so a future edit cannot quietly add it back.
// ═══════════════════════════════════════════════════════════════════════════════════════════════════

it('never reveals a guest\'s stored contact on a search row (#28)', function () {
    $s = searchScenario();
    Guest::create([
        'first_name' => 'Lukas', 'last_name' => 'Meyer',
        'email' => 'lukas.meyer@mail.test', 'phone' => '+49301234567',
    ]);
    $manager = operatorUser($s, [], 'Hotel Manager');

    // Searching by NAME: the row shows the name and REVEALS neither stored contact value.
    $this->actingAs($manager)->get('/app/search?q=Meyer')
        ->assertOk()
        ->assertSee('Lukas Meyer')                      // the NAME is the permitted field
        ->assertDontSee('lukas.meyer@mail.test')        // ⚠️ the design puts the email here — refused
        ->assertDontSee('+49301234567')
        ->assertDontSee('mailto:');

    // ⚠️ Searching BY the email is different, and the distinction is the point: the query is echoed
    // back into the input's value, so the string is on the page — but that is the operator's OWN
    // INPUT reflected, not the product disclosing something they did not already have. What must
    // still never appear is a contact value they did NOT type — here, the phone.
    $this->actingAs($manager)->get('/app/search?q=lukas.meyer%40mail.test')
        ->assertOk()
        ->assertSee('Lukas Meyer')                      // matched via the blind index
        ->assertDontSee('+49301234567')                 // ⚠️ never volunteered alongside the match
        ->assertDontSee('mailto:');
});

it('never renders a guest\'s contact on a RESERVATION row either (no contact rides along) (#28)', function () {
    $s = searchScenario();
    $s['guest']->update([
        'first_name' => 'Zeta', 'last_name' => 'Searchable',
        'email' => 'zeta.rider@hidden.test', 'phone' => '+441112223334',
    ]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $manager = operatorUser($s, [], 'Hotel Manager');

    $this->actingAs($manager)->get('/app/search?q='.$reservation->reference)
        ->assertOk()
        ->assertSee('Zeta Searchable')                      // lead-guest NAME on the reservation row
        ->assertDontSee('zeta.rider@hidden.test')
        ->assertDontSee('+441112223334');
});

it('never renders an owner\'s contact or BANK details on a search row (#28/#42)', function () {
    $s = searchScenario();
    $owner = Owner::factory()->create([
        'name' => 'Ironbank Holdings', 'email' => 'treasury@ironbank.test', 'phone' => '+35191234567',
    ]);
    // The IBAN lives on payout_methods (encrypted + an iban_last4 for masked display), NOT on owners.
    PayoutMethod::create([
        'owner_id' => $owner->id, 'label' => 'Main', 'account_holder' => 'Ironbank Holdings',
        'iban' => 'PT50000201231234567890154', 'iban_last4' => '0154', 'iban_country' => 'PT',
        'currency' => 'EUR', 'is_default' => true,
    ]);
    $manager = operatorUser($s, [], 'Hotel Manager');

    $this->actingAs($manager)->get('/app/search?q=Ironbank')
        ->assertOk()
        ->assertSee('Ironbank Holdings')                    // the NAME is the permitted field
        ->assertDontSee('PT50000201231234567890154')        // ⚠️ never the IBAN…
        ->assertDontSee('0154')                             // ⚠️ …nor even the masked tail, on THIS surface
        ->assertDontSee('treasury@ironbank.test')
        ->assertDontSee('+35191234567');
});

// ── The filter chips must stay GET links: /livewire/update does NOT run the property-context
//    middleware, so a wire:click filter would drop the scope (#20). ────────────────────────────────
it('drives every filter through a GET link that carries the query and the other filters (#20)', function () {
    $s = searchScenario();
    $manager = operatorUser($s, [], 'Hotel Manager');

    $page = $this->actingAs($manager)->get('/app/search?q=Searchable&status=confirmed')->assertOk();

    // A source chip carries q AND the already-active status — filters compose, never clobber.
    $page->assertSee('q=Searchable&amp;status=confirmed&amp;source=direct', false);
    // Clearing one key keeps the query.
    $page->assertSee(route('app.search', ['q' => 'Searchable']), false);
});

it('404s the drill-down when a search result URL is aimed at another org\'s record (#38)', function () {
    $a = searchScenario();
    $guestA = Guest::create(['first_name' => 'Xan', 'last_name' => 'Alpha']);
    $ownerA = Owner::factory()->create(['name' => 'Alpha Holdings']);

    $b = seedScenario(1);                                   // a SECOND org (tenant context now B)
    $guestB = Guest::create(['first_name' => 'Xan', 'last_name' => 'Beta']);
    $ownerB = Owner::factory()->create(['name' => 'Beta Holdings']);

    $operatorA = operatorUser($a, [], 'Hotel Manager');

    // Own records resolve …
    $this->actingAs($operatorA)->get(route('app.guests.show', $guestA->id))->assertOk();
    $this->actingAs($operatorA)->get(route('app.owners.show', $ownerA->id))->assertOk();

    // … the same URL shape aimed at org B does not exist for this actor (tenant-scoped findOrFail).
    $this->actingAs($operatorA)->get(route('app.guests.show', $guestB->id))->assertNotFound();
    $this->actingAs($operatorA)->get(route('app.owners.show', $ownerB->id))->assertNotFound();
});
