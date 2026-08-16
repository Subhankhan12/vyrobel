<?php

use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use App\Support\StayTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// D8-2 follow-up — the New Reservation form fabricated a check-in time.
//
// ⚠️ A TIME IS A FACT OR NOTHING. Two sites defaulted a null `properties.check_in_time` to a hardcoded
// '15:00' (and, one line below the second, an identical '11:00' for check-out). That is exactly the
// invention D8-2 refused on the housekeeping board, alive on the form: it happens to be right for the demo
// properties — both open at 15:00 — and is silently wrong for any property that opens at a different hour.
// A fabricated fact that matches the fixture is the kind that survives review (#39).
//
// ⚠️ EVERY FIXTURE BELOW PUBLISHES A NON-15:00 TIME (14:00 / 10:30). With a 15:00 property the invention and
// the fact are indistinguishable and a restored '15:00' would pass happily — the D8-2 M3 lesson.
//
// ⚠️ THE ASSERTIONS READ RENDERED MARKUP, NEVER THE SOURCE. The fix leaves the string "15:00" in explanatory
// comments, and a source-scanning guard would read its own needle (§N.27.5).

const NRT_PUBLISHED_IN = '14:00:00';    // ⚠️ deliberately NOT 15:00

const NRT_PUBLISHED_OUT = '10:30:00';   // ⚠️ deliberately NOT 11:00

/** An operator who may create reservations, on a property whose published times we control. */
function nrtSetup(?string $checkIn, ?string $checkOut): array
{
    $s = seedScenario(1);

    // ⚠️ THE FIXTURE TRAP: seedScenario publishes NO times, so a test that skips this line exercises only
    // the absent branch and never proves the real time flows through.
    DB::table('properties')->where('id', $s['property']->id)
        ->update(['check_in_time' => $checkIn, 'check_out_time' => $checkOut]);

    $user = User::where('email', 'demo-staff@vyrobel-demo.test')->first()
        ?? operatorUser($s, [], 'Hotel Manager');

    return ['s' => $s, 'user' => $user, 'session' => ['current_property_id' => $s['property']->id]];
}

/** A real reservation, so the post-create confirmation panel (site 1) renders. */
function nrtReservation(array $f): Reservation
{
    return Reservation::create([
        'property_id' => $f['s']['property']->id,
        'reference' => 'VYR-NRT-1',
        'status' => 'confirmed',
        'source' => 'direct',
        'lead_guest_id' => $f['s']['guest']->id,
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-03',
        'currency' => 'EUR',
    ]);
}

// ── SITE 1 · the post-create confirmation panel ───────────────────────────────────────────────────

// ⚠️⚠️ 1. THE PUBLISHED TIME FLOWS THROUGH — and it is not 15:00
it('shows the property published check-in time on the confirmation panel', function () {
    $f = nrtSetup(NRT_PUBLISHED_IN, NRT_PUBLISHED_OUT);
    $res = nrtReservation($f);

    $html = test()->actingAs($f['user'])->withSession($f['session'])
        ->get('/app/reservations/create?created='.$res->getKey())
        ->assertOk()->getContent();

    // The REAL published hour, in the real sentence.
    expect($html)->toContain('online check-in from 14:00')
        // ⚠️ and the invention is absent from the RENDERED page (source comments are irrelevant here).
        ->and($html)->not->toContain('check-in from 15:00');
});

// ⚠️⚠️ 2. NO PUBLISHED TIME → THE CLAUSE IS OMITTED, not filled with an invented hour
it('omits the check-in clause entirely when the property publishes no time', function () {
    $f = nrtSetup(null, null);
    $res = nrtReservation($f);

    $html = test()->actingAs($f['user'])->withSession($f['session'])
        ->get('/app/reservations/create?created='.$res->getKey())
        ->assertOk()->getContent();

    // The sentence still reads — it simply makes no claim about an hour.
    expect($html)->toContain('scans to open online check-in —')
        ->and($html)->not->toContain('check-in from 15:00')
        // ⚠️ AND NOT A DIFFERENT LIE: midnight is not an honest stand-in for "unpublished".
        ->and($html)->not->toContain('check-in from 00:00');
});

// ── SITE 2 · the house-rules panel ────────────────────────────────────────────────────────────────

// ⚠️⚠️ 3. THE PUBLISHED TIMES FLOW THROUGH — check-in AND check-out
it('shows the property published times in the house rules', function () {
    $f = nrtSetup(NRT_PUBLISHED_IN, NRT_PUBLISHED_OUT);

    $html = test()->actingAs($f['user'])->withSession($f['session'])
        ->get('/app/reservations/create')->assertOk()->getContent();

    expect($html)->toContain('From 14:00')
        ->and($html)->toContain('By 10:30')
        // Neither hardcoded default appears anywhere in the rendered panel.
        ->and($html)->not->toContain('From 15:00')
        ->and($html)->not->toContain('By 11:00');
});

// ⚠️⚠️ 4. NO PUBLISHED TIMES → the panel's own absence idiom, not an invented hour
it('renders an honest absence in the house rules when no times are published', function () {
    $f = nrtSetup(null, null);

    $html = test()->actingAs($f['user'])->withSession($f['session'])
        ->get('/app/reservations/create')->assertOk()->getContent();

    // "—" is the idiom this very panel already uses for an unset minimum stay.
    expect($html)->toContain('<span>Check-in</span><strong>—</strong>')
        ->and($html)->toContain('<span>Check-out</span><strong>—</strong>')
        ->and($html)->not->toContain('From 15:00')
        ->and($html)->not->toContain('By 11:00')
        // ⚠️ and no midnight substitute.
        ->and($html)->not->toContain('From 00:00')
        ->and($html)->not->toContain('By 00:00');
});

// ⚠️ 5. A HALF-PUBLISHED PROPERTY — the two times are independent facts
it('treats check-in and check-out as independent facts', function () {
    $f = nrtSetup(NRT_PUBLISHED_IN, null);   // check-in published, check-out not

    $html = test()->actingAs($f['user'])->withSession($f['session'])
        ->get('/app/reservations/create')->assertOk()->getContent();

    expect($html)->toContain('From 14:00')
        ->and($html)->toContain('<span>Check-out</span><strong>—</strong>')
        ->and($html)->not->toContain('By 11:00');
});

// ── The blast radius ──────────────────────────────────────────────────────────────────────────────

// ⚠️ 6. NOTHING IS STORED — the form writes no time, so a null publishes no defect downstream
it('writes no time onto the reservation, so an unpublished property creates a valid booking', function () {
    $f = nrtSetup(null, null);
    $res = nrtReservation($f);

    // ⚠️ RAW: the reservations table carries a check-in DATE and no time column at all — which is why a null
    // published time cannot violate anything downstream. (This is what made null-safe the correct fix rather
    // than "store the real time instead".)
    $columns = array_map(fn (object $c): string => (string) $c->Field, DB::select('SHOW COLUMNS FROM reservations'));
    expect($columns)->toContain('check_in')
        ->and($columns)->not->toContain('check_in_time');

    expect(Reservation::query()->whereKey($res->getKey())->exists())->toBeTrue();
});

// ⚠️ 7. D8-2'S BOARD IS UNDISTURBED — it derives ready_by from the SAME property column, still correctly
it('leaves the housekeeping board deriving ready_by from the same published time', function () {
    $f = nrtSetup(NRT_PUBLISHED_IN, NRT_PUBLISHED_OUT);

    // The one derivation both surfaces read (§N.8f) — the form now uses exactly what the board uses.
    expect(StayTime::display(NRT_PUBLISHED_IN, 'H:i'))->toBe('14:00')
        ->and(StayTime::display(null, 'H:i'))->toBeNull();

    // And the property really does hold it (fixture is not vacuous).
    expect((string) Property::query()->whereKey($f['s']['property']->id)->value('check_in_time'))->toStartWith('14:00');
});
