<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Design pass · Booking Screen 2 (public room detail, /book/{slug}/rooms/{roomType}). PUBLIC + read-only.
// Locks: real room specs + real availability/price render for THIS property over dates, no auth; a room
// of another property/org 404s on this storefront; nothing fabricated (no fake amenities/photos/reviews);
// no inventory written.

// ── 1. Real room detail + real availability/price render — no auth ───────────────────────────────
it('renders real room specs, availability and price for the searched dates', function () {
    $a = publicPropertyScenario(units: 1, nightlyMinor: 12000);
    $a['roomType']->update(['name' => 'Seaview Studio']);   // max_adults 2 + max_children 2 = sleeps 4

    test()->get("/book/{$a['property']->slug}/rooms/{$a['roomType']->id}?check_in=2026-09-01&check_out=2026-09-03&adults=2")
        ->assertOk()
        ->assertSee('Seaview Studio')       // real room name
        ->assertSee('sleeps up to 4')       // real capacity (2 adults + 2 children)
        ->assertSee('€120.00')              // real nightly price
        ->assertSee('€240.00')              // real total (2 nights)
        ->assertSee('1 left for your dates')// real availability
        ->assertSee('Book this room')       // forward CTA (no write here)
        ->assertSee('Amenities')            // section present…
        ->assertSee('aren’t published yet') // …but honestly empty, not fabricated
        ->assertDontSee('Free WiFi')        // no invented amenity
        ->assertDontSee('Ocean view');
});

// ── 2. Cross-property isolation — a room of property Y 404s on property X's storefront ───────────
it('404s a room that belongs to another property', function () {
    $x = publicPropertyScenario();
    $y = publicPropertyScenario();

    // Y's room on X's storefront → 404 (property-scoped resolve).
    test()->get("/book/{$x['property']->slug}/rooms/{$y['roomType']->id}?check_in=2026-09-01&check_out=2026-09-03")
        ->assertNotFound();

    // X's own room resolves fine.
    test()->get("/book/{$x['property']->slug}/rooms/{$x['roomType']->id}?check_in=2026-09-01&check_out=2026-09-03")
        ->assertOk();

    // An unknown room id on X → 404.
    test()->get("/book/{$x['property']->slug}/rooms/nonexistent-room-id")->assertNotFound();
});

// ── 3. Nothing fabricated — no price/availability without a real sellable search ─────────────────
it('never fabricates price or availability', function () {
    $a = publicPropertyScenario();
    $a['roomType']->update(['name' => 'Garden Suite']);

    // No dates → a check-availability form, NO price / availability claim.
    test()->get("/book/{$a['property']->slug}/rooms/{$a['roomType']->id}")
        ->assertOk()
        ->assertSee('Garden Suite')
        ->assertSee('Check availability')
        ->assertDontSee('/ night')
        ->assertDontSee('left for your dates');

    // Dates with no availability → honest "not available", never invented.
    test()->get("/book/{$a['property']->slug}/rooms/{$a['roomType']->id}?check_in=2027-01-01&check_out=2027-01-03&adults=2")
        ->assertOk()
        ->assertSee('Not available for these dates')
        ->assertDontSee('Book this room');
});
