<?php

use App\Models\Guest;
use App\Support\ErrorRealm;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

/**
 * F-47 — A GUEST WHO CANNOT CLAIM IS OFFERED A ROUTE THAT WORKS.
 *
 * ⚠️⚠️ THE COPY WAS NEVER THE PROBLEM. 🖥 The refusal is already good: a 403, no identity leak, the expiry
 * named, and a human route suggested ("reply to your booking confirmation email"). The defect was that its
 * only CLICKABLE route — "Back to your trips" → `/guest/trips` — REQUIRED THE ACCOUNT THE PERSON WAS TRYING
 * TO CREATE. Someone whose claim link expired met a login wall, and nothing in this product would ever
 * report that they left.
 *
 * ⚠️⚠️ SO THIS ASSERTS THE LINK WORKS, NOT THAT A LINK EXISTS. A markup-existence test is the shape that let
 * three flash findings hide: it would pass on a dead end. The case below FOLLOWS the offered destination
 * with NO SESSION and requires a usable page at the end of it.
 *
 * ⚠️ ENUMERATION DISCIPLINE (#6): the claim door is the same public page anyone may open, so pointing at it
 * discloses nothing about whether a booking exists. The wording does not change — only the destination, and
 * only when there is no guest session.
 */
it('offers a signed-out guest a destination that actually works', function () {
    // The realm's own derivation, with no guest session — the state a failed claimer is in.
    [$href, $label] = ErrorRealm::home('guest');

    expect($href)->toBe('/guest/claim');
    expect($label)->toBe('Access your bookings');

    // ⚠️ AND IT MUST RESOLVE FOR SOMEONE WITH NO ACCOUNT AND NO SESSION — the assertion the old link failed.
    $this->get($href)
        ->assertOk()
        ->assertSee('Access your bookings', false);
});

/**
 * ⚠️ THE CONTROL, AND IT COULD FAIL. A signed-in guest must still be sent to their trips: if the fix had
 * simply hardcoded the claim door for everyone, this goes RED. It also rules out the opposite mistake —
 * a change that broke the realm derivation entirely would fail here rather than pass silently.
 */
it('still sends a signed-in guest to their trips', function () {
    // A CLAIMED guest — the fixture is built here rather than borrowed from the seed, so the control
    // cannot pass or fail for reasons outside this test.
    $s = seedScenario(1);
    $guest = Guest::withoutGlobalScopes()->whereKey($s['guest']->id)->first();
    $guest->forceFill(['password' => 'claimed-Passw0rd!', 'email_verified_at' => now()])->save();

    $this->actingAs($guest, 'guest');

    [$href, $label] = ErrorRealm::home('guest');
    expect($href)->toBe('/guest/trips');
    expect($label)->toBe('Back to your trips');
});

/**
 * ⚠️ THE REFUSAL COPY IS UNTOUCHED — it was the best part of this screen and this is a DESTINATION fix.
 * Pinned so a later "tidy" has to break it deliberately.
 */
it('keeps the guest refusal wording, which was never the defect', function () {
    expect(ErrorRealm::forbiddenMessage('guest'))
        ->toContain('This page belongs to a different booking')
        ->toContain('the link may have expired');
});
