<?php

use App\Models\Guest;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * R-SUSPEND (founder, 2026-09-19) — A SUSPENDED ORGANISATION STOPS TAKING BOOKINGS.
 *
 * ⚠️⚠️ F-48: until this ruling, suspension took the operator's back office away and LEFT THEIR SHOP OPEN.
 * All nine `book/{property}` routes resolved through `ResolvePublicProperty`, which filtered
 * `properties.status` and never read the organisation — while `/stay/{organization}` already 404'd the same
 * organisation. The product contradicted itself on two public surfaces.
 *
 * ⚠️⚠️ THE RULING CLOSES NEW BOOKINGS. IT DOES NOT STRAND EXISTING GUESTS, and that is the half a guard like
 * this gets wrong: a realm-wide refusal that catches the people it was never aimed at. Both directions are
 * asserted IN THE SAME RUN below, because a guard proven only in DENY may be denying everyone.
 */
/**
 * ⚠️⚠️ `publicPropertyScenario()` AND NOT `seedScenario()` — AND MY OWN POSITIVE CONTROL IS WHY. The first
 * draft of this file used `seedScenario()`, whose property has NO SLUG and NO STATUS, so `book/{slug}` 404s
 * whatever the organisation is doing. Every "the door is shut" assertion PASSED while proving nothing: the
 * door was never open. The control — an ACTIVE organisation must still take a booking — is what caught it.
 */
function socWorld(): array
{
    return publicPropertyScenario(units: 1);
}

function socSuspend(array $s): void
{
    // ⚠️ THE FIXTURE IS BUILT, NEVER BORROWED. No seeded tenant is ever suspended by a test.
    Organization::query()->whereKey($s['org']->id)->update(['status' => 'suspended']);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1 · THE DOOR IS SHUT — and it names no reason
// ─────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ closes EVERY public booking route for a suspended organisation', function (string $path) {
    $s = socWorld();
    $slug = $s['property']->slug;
    socSuspend($s);

    $this->get(str_replace('{slug}', $slug, $path))->assertNotFound();
})->with([
    'book/{slug}',
    'book/{slug}/search',
    'book/{slug}/rooms/does-not-matter',
]);

/**
 * ⚠️⚠️ THE GUEST IS A STRANGER TO THE BILLING RELATIONSHIP. Publishing its state to the operator's own
 * customers would damage the operator. This asserts the ABSENCE of the reason — the assertion that would
 * catch a well-meaning "helpful" rewrite.
 */
it('⚠️⚠️ never tells the guest WHY, anywhere in the response', function () {
    $s = socWorld();
    $slug = $s['property']->slug;
    socSuspend($s);

    $body = $this->get("book/{$slug}")->assertNotFound()->getContent();

    // ⚠️⚠️ SINGLE-ARGUMENT, AND THAT IS NOT A STYLE CHOICE. `toContain()` is VARIADIC and takes no message,
    //    so `->not->toContain($needle, 'a message')` adds the MESSAGE AS A SECOND NEEDLE and passes as soon
    //    as either is absent — making it UNCONDITIONALLY TRUE. 🔬 My first draft did exactly that and
    //    `NegatedVariadicMatcherGuardTest` caught it: the leak assertion below was proving nothing at all.
    $lower = mb_strtolower($body);
    foreach (['suspend', 'unpaid', 'overdue', 'billing', 'account closed', 'subscription', 'payment failed'] as $leak) {
        expect(str_contains($lower, $leak))->toBeFalse("the closed page must never say '{$leak}'");
    }
});

/**
 * ⚠️ AND IT MUST NOT MERELY LOOK BROKEN. A blank body, a 500, or an empty calendar that reads as "no
 * availability" are each worse than an honest closed sign, because the guest concludes the product is
 * broken or the hotel is full rather than that booking is closed.
 */
it('⚠️ shows an honest closed sign with a real next step, not a blank or a 500', function () {
    $s = socWorld();
    $slug = $s['property']->slug;
    socSuspend($s);

    $body = $this->get("book/{$slug}")->assertNotFound()->getContent();

    expect($body)->toContain('Online booking isn’t available here');
    expect($body)->toContain('confirmation email');       // the existing-booking next step
    expect($body)->toContain('contact the property');     // the new-enquiry next step
    expect(mb_strlen(trim(strip_tags($body))))->toBeGreaterThan(80, 'a near-empty body reads as broken');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2 · THE POSITIVE CONTROL, AND IT CAN FAIL
// ─────────────────────────────────────────────────────────────────────────────

it('✅ POSITIVE CONTROL — an ACTIVE organisation still takes bookings', function () {
    $s = socWorld();
    // ⚠️ deliberately NOT suspended — and this assertion is what exposed the vacuous fixture above.
    expect((string) Organization::query()->whereKey($s['org']->id)->value('status'))->not->toBe('suspended');

    $this->get("book/{$s['property']->slug}")->assertOk();
});

/**
 * ⚠️⚠️ THIS CASE REPLACES A PINNED ANSWER, AND THE RED THAT FORCED THE EDIT WAS THE PIN WORKING.
 *
 * Until 2026-09-19 it read "acts on `suspended` ONLY — `cancelled` and `trialing` are untouched" and asserted
 * that a CANCELLED organisation **still took bookings**. FIX 36 pinned that deliberately, in its own words
 * "so a later change is deliberate", because inferring that a cancelled account should behave like a
 * suspended one would have been guessing a commercial policy.
 *
 * ⚠️⚠️ **F-51 — THE FOUNDER RULED IT, 2026-09-19: `cancelled` CLOSES THE BOOKING PATH TOO.** So the old
 * expectation is replaced here rather than quietly deleted, and `trialing` — still unruled — keeps its own
 * assertion below so the two cannot be confused for one decision.
 */
it('⚠️⚠️ RULED 2026-09-19 (F-51) — a CANCELLED organisation stops taking bookings, exactly like a suspended one', function () {
    $s = socWorld();
    Organization::query()->whereKey($s['org']->id)->update(['status' => 'cancelled']);

    $this->get("book/{$s['property']->slug}")->assertNotFound();
});

/**
 * ⚠️⚠️ `trialing` IS STILL UNRULED AND STILL SELLS — a trialing organisation is mid-trial and genuinely
 * trading. ⚠️ Pinned separately from the F-51 case above so nobody reads "two states close" as "three do".
 */
it('⚠️ `trialing` is UNRULED and untouched — a trialing organisation still takes bookings', function () {
    $s = socWorld();
    Organization::query()->whereKey($s['org']->id)->update(['status' => 'trialing']);

    $this->get("book/{$s['property']->slug}")->assertOk();
});

/**
 * ⚠️⚠️ THE STRONGEST FORM OF F-51, AND WHY IT IS ONE TEST RATHER THAN TWO: it asserts that `suspended` and
 * `cancelled` are INDISTINGUISHABLE on the public surface — same status, same body, byte for byte. A future
 * divergence then breaks visibly here instead of drifting quietly on one state.
 */
it('⚠️⚠️ F-51 — `suspended` and `cancelled` are INDISTINGUISHABLE to the public', function () {
    $a = socWorld();
    Organization::query()->whereKey($a['org']->id)->update(['status' => 'suspended']);
    $suspended = $this->get("book/{$a['property']->slug}");

    $b = socWorld();
    Organization::query()->whereKey($b['org']->id)->update(['status' => 'cancelled']);
    $cancelled = $this->get("book/{$b['property']->slug}");

    expect($cancelled->getStatusCode())->toBe($suspended->getStatusCode());

    // ⚠️ THE BODIES TOO — a reader must not be able to tell WHICH state closed the door.
    expect($cancelled->getContent())->toBe($suspended->getContent());
});

// ─────────────────────────────────────────────────────────────────────────────
// 3 · THE OVER-REACH CONTROL — an existing guest is NOT stranded
// ─────────────────────────────────────────────────────────────────────────────

/**
 * ⚠️⚠️ THE HALF THAT MATTERS MOST, AND IT RUNS BESIDE THE REFUSAL. Suspension is between Vyrobel and the
 * operator; a guest who has already paid is not the lever. 🔬 Structurally none of the 58 `guest/` routes
 * runs `ResolvePublicProperty`, so this cannot break by construction — asserted anyway, because "cannot
 * happen" is what every stranding bug says beforehand.
 */
it('⚠️⚠️ does NOT strand an existing guest — they reach their stay while a stranger is refused, in ONE run', function (string $status) {
    $s = seedScenario(1);          // the guest-portal fixture; the stranger half uses its own bookable world
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $shop = socWorld();

    $guest = Guest::withoutGlobalScopes()->whereKey($s['guest']->id)->first();
    $guest->forceFill(['password' => 'claimed-Passw0rd!', 'email_verified_at' => now()])->save();

    // ⚠️ BOTH CLOSED STATES — F-51 (2026-09-19) made `cancelled` behave like `suspended`, and a cancelled
    //    account's guests are no less owed the stay they already hold.
    Organization::query()->whereKey($s['org']->id)->update(['status' => $status]);
    Organization::query()->whereKey($shop['org']->id)->update(['status' => $status]);

    // ⚠️ THE STRANGER IS REFUSED …
    $this->get("book/{$shop['property']->slug}")->assertNotFound();

    // ⚠️ … AND THE GUEST WHO ALREADY HOLDS A STAY IS NOT.
    $this->actingAs($guest, 'guest')->get('/guest/trips')->assertOk();
    $this->actingAs($guest, 'guest')->get('/guest/reservation/'.$reservation->id)->assertOk();

    // ⚠️ AND NOTHING WAS DONE TO THEIR BOOKING — the ruling closes the shop, it does not cancel stays.
    expect((string) $reservation->fresh()->status)->toBe('confirmed');
})->with(['suspended', 'cancelled']);
