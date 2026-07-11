<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Design pass · Booking Screen 1 (public storefront — Browse / Search + sellable rooms, /book/{slug}).
// PUBLIC + unauthenticated: context is set by ResolvePublicProperty from the slug (NOT the operator app
// group / SetTenantForWebRequest, NO logged-in user). Locks: real sellable rooms + real availability/
// prices render for THIS property; a visitor for property X can never see property Y's inventory; the
// page needs no auth; nothing is fabricated (no invented rooms/prices/availability).

// ── 1. Real sellable rooms + real availability/prices render — no auth needed ────────────────────
it('renders real sellable rooms with real availability and prices for the searched dates', function () {
    $a = publicPropertyScenario(units: 1, nightlyMinor: 12000);   // €120/night, published 2026-09-01..10
    $a['roomType']->update(['name' => 'Seaview Studio']);

    // No actingAs — a public visitor. A 200 here proves the page needs no login.
    test()->get("/book/{$a['property']->slug}?check_in=2026-09-01&check_out=2026-09-03&adults=2")
        ->assertOk()
        ->assertSee('Public Hotel')       // the property storefront
        ->assertSee('Book direct')        // storefront chrome, not the operator app
        ->assertSee('Seaview Studio')     // REAL room type
        ->assertSee('1 left')             // REAL availability (1 unit)
        ->assertSee('€120.00')            // REAL nightly price (derived from the real stay total)
        ->assertSee('€240.00');           // REAL total for 2 nights
});

// ── 2. PUBLIC isolation — a visitor for property X never sees property Y's inventory ─────────────
it('isolates public properties — X\'s storefront never shows Y\'s rooms', function () {
    $x = publicPropertyScenario();
    $x['roomType']->update(['name' => 'AlphaRoom']);

    $y = publicPropertyScenario();
    $y['roomType']->update(['name' => 'BetaRoom']);

    $dates = 'check_in=2026-09-01&check_out=2026-09-03&adults=2';

    test()->get("/book/{$x['property']->slug}?{$dates}")
        ->assertOk()->assertSee('AlphaRoom')->assertDontSee('BetaRoom');

    test()->get("/book/{$y['property']->slug}?{$dates}")
        ->assertOk()->assertSee('BetaRoom')->assertDontSee('AlphaRoom');

    // An unknown property slug 404s (never leaks another property).
    test()->get('/book/no-such-property?'.$dates)->assertNotFound();
});

// ── 3. Nothing fabricated — teaser claims no price/availability; empty dates say so honestly ─────
it('never fabricates rooms, prices, or availability', function () {
    $a = publicPropertyScenario();
    $a['roomType']->update(['name' => 'Garden Suite']);

    // No date search yet → a names-only teaser, NO price / availability claim.
    test()->get("/book/{$a['property']->slug}")
        ->assertOk()
        ->assertSee('Our rooms')
        ->assertSee('Garden Suite')
        ->assertSee('Search dates above')
        ->assertDontSee('/ night')          // no fabricated price before a real search
        ->assertDontSee('rooms available'); // no fabricated availability/results before a real search

    // A search with no real availability shows an honest empty state, not invented rooms.
    test()->get("/book/{$a['property']->slug}?check_in=2027-01-01&check_out=2027-01-03&adults=2")
        ->assertOk()
        ->assertSee('No rooms available');
});
