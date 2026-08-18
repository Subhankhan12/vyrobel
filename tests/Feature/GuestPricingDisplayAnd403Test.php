<?php

declare(strict_types=1);

use App\Models\Fee;
use App\Models\Tax;
use App\Support\ErrorRealm;
use App\Support\Folio\StayEstimator;
use App\Support\Money\Money;

// GUEST FIX PASS — F2 (the card sublabel enumerates what the total folds in) + F5 (the 403 body is
// realm-branched). From the sealed guest deep-parity audit.
//
// ⚠️⚠️ F3 (VAT-INCLUSIVE ROOM-RATE DISPLAY) IS **NOT** IMPLEMENTED HERE, DELIBERATELY — it was STOPPED AND
// SURFACED, because the system already has a real inclusive mode and using it MOVES THE TOTAL. Test 1 is the
// guard that pins that: the total is invariant. See the pass report for the mechanism.
//
// ⚠️ THE ORDER OF THE GUARDS MATTERS. Test 1 (total invariant) and test 3 (VAT still itemised) are green
// BEFORE and AFTER this change — they exist to RED if a future edit reprices or folds VAT away. Tests 2 and 4
// red before and green after: they are what this pass actually changed.

/** A property carrying the real shape the audit measured: 23% exclusive VAT · per-person-night city tax · flat cleaning. */
function gpdProperty(): array
{
    $s = publicPropertyScenario(units: 1);

    Tax::create(['property_id' => $s['property']->id, 'name' => 'VAT', 'mode' => 'percentage',
        'rate' => 23.0, 'inclusive' => false, 'currency' => 'EUR']);
    Tax::create(['property_id' => $s['property']->id, 'name' => 'City tax', 'mode' => 'per_person_night',
        'amount_minor' => 250, 'currency' => 'EUR']);
    Fee::create(['property_id' => $s['property']->id, 'name' => 'Cleaning fee', 'mode' => 'flat',
        'amount_minor' => 4500, 'optional' => false, 'currency' => 'EUR']);

    return $s;
}

// ── 1. ⚠️⚠️ THE MONEY GUARD — the displayed total is INVARIANT (green before AND after) ───────────────────
it('holds the grand total byte-identical: room + charged VAT + city tax + cleaning, to the cent', function () {
    $s = gpdProperty();

    $room = Money::of(25500, 'EUR');                     // 3 nights × €85.00 ex-VAT — the audit's worked example
    $estimate = StayEstimator::estimate(
        Tax::query()->where('property_id', $s['property']->id)->get(),
        Fee::query()->where('property_id', $s['property']->id)->get(),
        $room, nights: 3, persons: 2, currency: 'EUR',
    );

    // ⚠️ THE FIGURE THE AUDIT MEASURED, PINNED. €255.00 + €58.65 VAT + €15.00 city tax + €45.00 cleaning.
    // A reprice — flipping VAT to inclusive, or grossing the stored rate up — REDS this line.
    expect($estimate['total']->amountMinor())->toBe(37365);

    // …and the components genuinely sum to it, so the total is not a coincidence of one wrong pair.
    $sum = 0;
    foreach ($estimate['lines'] as $line) {
        if (! $line['informational']) {
            $sum += $line['amount']->amountMinor();
        }
    }
    expect($sum)->toBe(37365);

    // ⚠️ THE OWNER'S EX-VAT ECONOMICS ARE UNCHANGED — the room line is still the stored ex-VAT figure.
    expect($estimate['lines'][0]['amount']->amountMinor())->toBe(25500);
});

// ── 2. ⚠️⚠️ F2 — the card sublabel ENUMERATES (reds before this pass) ─────────────────────────────────────
it('enumerates every charged component on the storefront card, not just the cleaning fee', function () {
    $s = gpdProperty();

    $html = test()->get("/book/{$s['property']->slug}?check_in=2026-09-01&check_out=2026-09-04&adults=2")
        ->assertOk()->getContent();

    // ⚠️ THE PREMISE: the card really rendered a priced offer, so an absence below would be a real absence.
    expect($html)->toContain('incl.');

    // ⚠️ BEFORE THIS PASS the note read only "incl. €45.00 cleaning" — VAT and the city tax were folded into
    // the total with no mention, which is exactly what the audit filed (F2).
    expect($html)->toContain('VAT')
        ->and($html)->toContain('City tax')
        ->and($html)->toContain('Cleaning fee');
});

// ── 3. ⚠️⚠️ THE COMPLIANCE GUARD — VAT stays a SEPARATE line (green before AND after) ─────────────────────
it('keeps VAT itemised as its own charged line rather than folding it into the room', function () {
    $s = gpdProperty();

    $estimate = StayEstimator::estimate(
        Tax::query()->where('property_id', $s['property']->id)->get(),
        Fee::query()->where('property_id', $s['property']->id)->get(),
        Money::of(25500, 'EUR'), nights: 3, persons: 2, currency: 'EUR',
    );

    // ⚠️ A VAT invoice must show the tax amount SEPARATELY. This is the shared source the card, the
    // pre-reserve breakdown and the tax documents all read — folding VAT into the room line to make a
    // "VAT-inclusive" display would REV this test, which is the point of it.
    $vat = collect($estimate['lines'])->firstWhere('label', 'VAT');

    expect($vat)->not->toBeNull()
        ->and($vat['amount']->amountMinor())->toBe(5865)
        ->and($vat['informational'])->toBeFalse()          // charged, not merely informational
        ->and($vat['detail'])->toContain('added');         // and labelled honestly for the current basis
});

// ── 4. ⚠️⚠️ F5 — the 403 body is realm-branched (reds before this pass) ───────────────────────────────────
it('speaks to a guest in guest terms and never about roles or teams they do not have', function () {
    $guest = ErrorRealm::forbiddenMessage('guest');

    // ⚠️ BEFORE THIS PASS every realm got the operator copy — a guest was told to ask "an administrator on
    // your team" to grant a "permission", advice a guest cannot act on.
    expect($guest)->not->toContain('your team')
        ->and($guest)->not->toContain('Your role')
        ->and($guest)->not->toContain('permission')
        // …and it says something USEFUL, so this is not passing on an empty string.
        ->and($guest)->toContain('booking');

    // The owner realm is branched too — an owner's capabilities come from their primary owner, not a team.
    expect(ErrorRealm::forbiddenMessage('owner'))->toContain('primary owner')
        ->and(ErrorRealm::forbiddenMessage('owner'))->not->toContain('your team');

    // ⚠️ THE OPERATOR REALM IS UNCHANGED — this pass reframed the other realms, it did not rewrite the
    // operator's message, which was correct for an actor that genuinely has a role and a team.
    expect(ErrorRealm::forbiddenMessage('app'))->toBe(
        'Your role doesn’t include this area. If you need it, ask an administrator on your team to grant the permission.'
    );
    expect(ErrorRealm::forbiddenMessage('admin'))->toBe(ErrorRealm::forbiddenMessage('app'));
});

// ── 5. ⚠️ THE BACK-LINK AND THE BODY READ THE SAME DERIVATION (the anti-drift point of the helper) ────────
it('derives the back-link and the 403 body from one realm source so they cannot disagree', function () {
    foreach ([['guest', '/guest/trips'], ['owner', '/owner'], ['app', '/app/dashboard'], ['admin', '/admin'], ['public', '/']] as [$realm, $home]) {
        expect(ErrorRealm::home($realm)[0])->toBe($home);
    }

    // ⚠️ A realm the layout knows about is a realm the body knows about — that is the whole reason the
    // derivation moved into one class instead of being matched twice.
    expect(ErrorRealm::forbiddenMessage('guest'))->not->toBe(ErrorRealm::forbiddenMessage('app'));
});
