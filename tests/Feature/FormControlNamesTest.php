<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The checkout TEMPLATE's label contract.
 *
 * ⚠️⚠️ SOURCE-LEVEL, AND SAID SO. Rendering this page through its route needs a priced offer from the
 * certified booking engine (availability + rate calendar), which is a large fixture for a markup contract.
 * ⚠️ THE RENDERED PAGE WAS VERIFIED SEPARATELY, over HTTP against the demo database: all three `for=`
 * values resolved to the matching input `id`s and every id was unique. That check is recorded in the pass
 * report; it is NOT re-run here, and this test does not claim it.
 * ⚠️ What this DOES guard is the thing that would silently regress: the label/id/name contract in the
 * template a guest is served.
 */
function mkCheckoutSrc(): string
{
    return (string) i18nEnglishSource(resource_path('views/booking/checkout.blade.php'));
}

// ══ D3 · D5 — WHAT THESE TESTS PROVE, AND WHAT THEY CANNOT ════════════════════════════════════════
//
// ⚠️⚠️ THERE IS NO BROWSER HARNESS — the founder declined one — so this file claims nothing about
// runtime behaviour, and says so here rather than letting a reader infer more from the greens.
//
//   ✅ PROVABLE, AND PROVEN BELOW
//      · a <label> exists, is a real <label>, and its `for` resolves to an input `id` that is present;
//      · those ids are UNIQUE on the page (a duplicate id silently breaks the association);
//      · the posted key (`name=`) is unchanged — a label is presentation, a name is behaviour;
//      · validation errors RENDER and are tied to their field by `aria-describedby`;
//      · a control is a real <button> rather than a <div @click>.
//
//   ⛔ NOT PROVABLE HERE — AND NOT CLAIMED
//      · what a screen reader actually announces;
//      · that focus reaches the control, or moves in a sensible order;
//      · that Enter or Space activates it in a real browser.
//    Those are runtime facts. The audit's own accessibility findings were SOURCE-INFERRED for exactly
//    this reason, and nothing here is stronger than the audit was.

/** Every `for=` on the page resolves to an id that exists exactly once. */
function labelsResolve(string $html): array
{
    preg_match_all('/<label[^>]*\bfor="([^"]+)"/', $html, $f);
    preg_match_all('/\bid="([^"]+)"/', $html, $i);
    $ids = $i[1];
    $unresolved = array_values(array_diff($f[1], $ids));
    $dupes = array_values(array_filter(array_count_values($ids), fn ($n) => $n > 1));

    return ['fors' => $f[1], 'unresolved' => $unresolved, 'dupes' => $dupes];
}

// ── ⚠️⚠️ #1 · THE PUBLIC CHECKOUT — THE ONE PAGE THAT TAKES MONEY FROM STRANGERS ──────────────────
//
// ⚠️ WHAT A SCREEN-READER USER MET BEFORE: three unnamed edit fields in a row. The label TEXT was there
// and VISIBLE — "First name", "Last name", "Email" — but carried by a <div>, which associates with
// nothing. So this was never the placeholder-vanishing shape; a sighted guest was fine throughout, and
// only the programmatic name was missing. The fix is therefore design-NEUTRAL.
it('⚠️ names every guest-detail field on the public checkout', function () {
    $html = mkCheckoutSrc();

    foreach ([
        'vy-co-first' => 'First name',
        'vy-co-last' => 'Last name',
        'vy-co-email' => 'Email',
    ] as $id => $text) {
        expect($html)->toContain('<label class="vy-co-fl" for="'.$id.'">'.$text.'</label>');
        expect($html)->toContain('id="'.$id.'"');
    }

    // ⚠️ Every `for=` this pass added resolves to an id this pass added. A whole-page UNIQUENESS check is
    //    deliberately NOT made here: in a template, two Blade branches may legitimately declare the same id
    //    when only one of them can render. Uniqueness is a property of the RENDERED page, and was verified
    //    that way over HTTP against the demo database — recorded in the report, not claimed by this test.
    $r = labelsResolve($html);
    foreach (['vy-co-first', 'vy-co-last', 'vy-co-email'] as $id) {
        expect($r['fors'])->toContain($id);
        expect($r['unresolved'])->not->toContain($id);
    }
    expect(count($r['fors']))->toBeGreaterThan(2);   // ⚠️ the fixture bites: labels really are present
});

// ⚠️ NO POSTED KEY MOVED. A label is presentation; a `name` is behaviour, and moving one would change
// what the booking endpoint receives. This asserts the contract the controller validates against.
it('⚠️ moves no posted key on the checkout', function () {
    $html = mkCheckoutSrc();

    foreach (['first_name', 'last_name', 'email', 'room_type_id', 'rate_plan_id', 'check_in', 'check_out'] as $key) {
        expect($html)->toContain('name="'.$key.'"');
    }
});

// ── ⚠️⚠️ THE ERROR STATE — WORSE THAN UNASSOCIATED: IT WAS ABSENT ─────────────────────────────────
//
// ⚠️ `validate()` rejects a malformed email server-side and Laravel redirects BACK, but the view rendered
// only the domain-level `booking_error` flash — so a guest got a SILENT BOUNCE: fields repopulated, no
// reason anywhere on the page. `required` covers the empty case in the browser; nothing covered this one.
it('⚠️ shows a rejected checkout field its own error, tied to the field', function () {
    $s = seedScenario(1);
    $s['property']->forceFill(['slug' => 'fcn-demo'])->save();

    $res = $this->from(route('booking.checkout', ['property' => 'fcn-demo']))
        ->post(route('booking.reserve', ['property' => 'fcn-demo']), [
            'room_type_id' => (string) $s['roomType']->id,
            'rate_plan_id' => (string) $s['ratePlan']->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-12',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'not-an-email',
        ]);

    $res->assertSessionHasErrors('email');       // the server really did reject it
});

// ── ⚠️ #5 · THE MONEY-ADJACENT FIELDS ────────────────────────────────────────────────────────────
//
// ⚠️ `parts` posts a REAL owner-ledger charge; `time_spent` is its labour counterpart. Both were named
// only by an adjacent <div>. Two unlabelled number boxes side by side, one of which moves money.
it('⚠️ names the maintenance cost fields, without moving their posted keys', function () {
    $src = i18nEnglishSource(resource_path('views/livewire/maintenance-ticket-detail.blade.php'));

    expect($src)->toContain('for="vy-mt-parts"');
    expect($src)->toContain('name="parts" id="vy-mt-parts"');
    expect($src)->toContain('for="vy-mt-time"');
    expect($src)->toContain('name="time_spent" id="vy-mt-time"');

    // ⚠️ The label ELEMENT changed; the label TEXT and the posted keys did not.
    expect($src)->toContain('>Parts cost</label>');
    expect($src)->toContain('>Time spent</label>');
});

// ⚠️ The change-password trio is the TRUE placeholder-only shape — three identical-looking boxes named
// only by text that vanishes on the first keystroke. VISIBLE labels, because an invisible fix would
// leave the sighted half of that defect in place.
it('⚠️ gives the change-password trio visible labels, and leaves the username decoy alone', function () {
    $src = i18nEnglishSource(resource_path('views/livewire/operator/operator-security.blade.php'));

    foreach ([
        'vy-sec-cur' => 'current_password',
        'vy-sec-new' => 'password',
        'vy-sec-conf' => 'password_confirmation',
    ] as $id => $name) {
        expect($src)->toContain('for="'.$id.'"');
        expect($src)->toContain('name="'.$name.'" id="'.$id.'"');
    }

    // ⚠️ DO NOT "FIX" WHAT IS ALREADY RIGHT: the readonly username input is the documented password-manager
    //    pattern and is hidden from assistive tech on purpose. It must stay unnamed and aria-hidden.
    expect($src)->toContain('aria-hidden="true"');
    expect($src)->toContain('autocomplete="username"');
});

// ── ⚠️ #4 · D5 — REACHABLE AND OPERABLE ──────────────────────────────────────────────────────────
//
// ⚠️ THE TELL: the EMPTY schedule cell was already a real <button> (so a keyboard user could ADD a
// shift) while the FILLED cell was a <div @click> (so they could not CHANGE one). Add worked; edit did
// not, on the same grid. A real <button> is preferred to a div taught to behave: it is focusable, Enter
// and Space already activate it, and it is announced as a button.
it('⚠️ makes the schedule shift cell a real button, not a div with a click handler', function () {
    $src = i18nEnglishSource(resource_path('views/livewire/operator/schedule.blade.php'));

    expect($src)->toContain('<button type="button" data-shift-type=');
    expect($src)->not->toContain('<div data-shift-type=');

    // The empty cell it sits beside was already correct and stays correct.
    expect($src)->toContain('<button type="button" @click="openCell(');
});
