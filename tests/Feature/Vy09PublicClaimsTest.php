<?php

declare(strict_types=1);

// ⚠️⚠️ VY-09 (audit of 2026-11-24, seen live) — SIXTEEN PUBLIC STATEMENTS DESCRIBED THINGS THE CODE DOES NOT DO.
//
// Each was read on the live site and checked against the code. They are corrected in the pages, and guarded HERE, at the RENDERED
// layer (`mkPage()` renders the view the router serves) — the layer every earlier claims test in this suite reads, because a
// guard on the design master or on the converter says nothing about what a visitor is shown.
//
// ⚠️ THE CONVERTER (`tools/marketing/convert.py`) DOES NOT CARRY THESE CORRECTIONS. They were made in the pages, like every marketing
//    edit since 2026-09-23. A regeneration from the design masters would bring the old sentences back — and turn this file red,
//    which is what it is for.
//
// ⚠️ NOT NEGATED, REMOVED. A page must not carry the false words at all (a denial leaves the phrase for a skimmer), so each case
//    asserts the words are GONE; where the page now says something in their place, that it says the true thing.

/** @return array<string, array{0: string, 1: list<string>, 2: list<string>}> page → [key, gone, present] */
dataset('vy09 claims', [
    '1 · "GDPR-ready, hosted in the EU" (home)' => ['home', ['hosted in the EU', 'GDPR-ready'], []],
    '2 · "Hosting is not chosen yet" (security)' => ['security', ['Hosting is not chosen yet'], ['shared hosting provided by Hostinger', 'no data-residency commitment yet']],
    '3 · the door code changes itself on checkout (for apartments)' => ['for-apartments', ['The code expires', 'The next guest gets a different one', 'On checkout, automatically'], ['your team changes the code between stays']],
    '4 · the code releases "at the hour you choose" (for apartments)' => ['for-apartments', ['at the hour you choose'], ['a set number of days before arrival']],
    '4 · …"the afternoon before" (product)' => ['product', ['the afternoon before'], ['a set number of days before arrival, once the balance is settled']],
    '5 · "The night audit runs on its own" (for hotels)' => ['for-hotels', ['night audit', 'the afternoon before'], []],
    '6 · "Automatic monthly statements" (features)' => ['features', ['Automatic monthly statements'], ['Monthly statements']],
    '7 · "Every module is in every plan" (modules)' => ['modules', ['Every module is in every plan', 'no channel manager sold separately'], ['The one exception is the channel manager, which is part of the Pro plan']],
    '7 · "All eighteen, in every plan" (the menu on every page)' => ['home', ['All eighteen, in every plan'], []],
    '8 · one inbox across Airbnb and Booking.com, with delivery states (modules)' => ['modules', ['across direct, Airbnb and Booking.com', 'delivery states'], ['Airbnb, Booking.com and WhatsApp conversations are not connected yet']],
    '8 · …(features)' => ['features', ["Every channel's messages", 'delivery states'], []],
    '8 · …(for apartments)' => ['for-apartments', ['Direct, Airbnb and Booking.com conversations', 'delivery states'], ['Airbnb and Booking.com inboxes are not connected yet']],
    '9 · "It stops selling on every channel in the same second" (home)' => ['home', ['on every channel in the same second', 'pushed to Airbnb and Booking.com'], ['Your direct storefront stops offering it at once']],
    '9 · …an owner block on Airbnb and Booking.com (for villas)' => ['for-villas', ['in the same second', 'It comes off sale everywhere'], ['your storefront stops offering the dates at once']],
    '10 · "A cleaner … marks the unit out of service from the room" (home)' => ['home', ['A cleaner photographs it', 'out of service from the room'], []],
    '11 · "Vyrobel imports from a CSV" (switching)' => ['switching', ['imports from a CSV', 'We import a plain-text archive'], ['There is no file import yet']],
    '11 · "we\'ll import your bookings" (pricing — the page and its FAQ data)' => ['pricing', ['import your bookings'], ['start with one property, no card']],
    '11 · …(how it works)' => ['how-it-works', ['import your bookings', 'Import your bookings', 'importing your units'], []],
    '12 · "That has happened twice" (switching)' => ['switching', ['That has happened twice', 'a rate mapping we'], []],
    '13 · "Vyrobel pulls new reviews going forward" (switching)' => ['switching', ['pulls new reviews'], ['it does not import reviews from a channel']],
    '14 · "what is live today is … Stripe payments" (integrations)' => ['integrations', ['the direct storefront, Stripe payments', 'connects to today: Stripe payments'], ['live card payments are not switched on yet']],
    '15 · "house rules accepted with a timestamp" at check-in (features)' => ['features', ['house rules accepted with a timestamp', 'the afternoon before'], ['booking terms accepted with a timestamp when they book']],
    '16 · "in real time" (home)' => ['home', ['in real time'], []],
    '16 · …(product)' => ['product', ['in real time'], []],
]);

it('⚠️⚠️ VY-09: the page no longer makes the statement — and says the true thing where one replaced it', function (string $key, array $gone, array $present) {
    // the visible text AND the head (descriptions, the FAQ's structured data): a claim removed from the body and left in a
    // `<meta>` or a JSON-LD block is still published
    $page = html_entity_decode(mkPage($key), ENT_QUOTES | ENT_HTML5);
    $page = str_replace(["\u{2019}", '\\\''], "'", $page);

    foreach ($gone as $words) {
        expect($page)->not->toContain($words);
    }
    foreach ($present as $words) {
        expect($page)->toContain($words);
    }
})->with('vy09 claims');

it('VY-09 CONTROL: the integrations page still says, plainly, that no OTA channel can be connected yet (accurate, and must stay)', function () {
    expect(mkPage('integrations'))->toContain('No OTA channel can be connected yet');
});
