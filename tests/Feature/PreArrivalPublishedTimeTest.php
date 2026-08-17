<?php

use App\Http\Controllers\Guest\GuestCheckinController;
use App\Mail\PreArrivalMail;
use App\Models\Reservation;
use App\Support\StayTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// Items 22 + 23 — the last two invented '15:00' sites, both GUEST-FACING.
//
// ⚠️⚠️ AN EMAIL CANNOT BE RECALLED. The pre-arrival mail read `?? '15:00'` / `?? '11:00'`, so a property
// that had published no times sent every guest an arrival hour nobody set — and a guest PLANS around a
// pre-arrival time. That is the difference between this and the page-level siblings already fixed.
//
// ⚠️ 15:00 IS NEVER "THE DEFAULT", AND THAT IS THE WHOLE POINT. `properties.check_in_time` is nullable with
// NO schema default and NO config default. The two demo properties publish exactly 15:00/11:00 — which is
// precisely why the fabrication survived review: it matched the fixture (#39).
//
// ⚠️ EVERY FIXTURE BELOW PUBLISHES NON-DEFAULT TIMES (14:00 / 10:30). Against a 15:00 property the invention
// and the fact are indistinguishable, and a restored fallback would pass happily — the D8-2 M3 lesson.
//
// ⚠️ THE ASSERTIONS READ THE RENDERED EMAIL BODY, NEVER THE SOURCE. The fix leaves "15:00" in an explanatory
// Blade comment; a source-scanning guard would read its own needle (§N.27.5). Blade comments do not survive
// compilation, so only the rendered bytes can answer this.

const PAT_IN = '14:00:00';      // ⚠️ deliberately NOT 15:00

const PAT_OUT = '10:30:00';     // ⚠️ deliberately NOT 11:00

/** A real reservation whose property publishes (or does not publish) times. */
function patReservation(?string $in, ?string $out): Reservation
{
    $s = seedScenario(1);

    // ⚠️ THE FIXTURE TRAP: seedScenario publishes NO times, so a test that skips this exercises only the
    // absent branch and never proves a real time flows through to the email.
    DB::table('properties')->where('id', $s['property']->id)
        ->update(['check_in_time' => $in, 'check_out_time' => $out]);

    return Reservation::create([
        'property_id' => $s['property']->id,
        'reference' => 'VYR-PAT-1',
        'status' => 'confirmed',
        'source' => 'direct',
        'lead_guest_id' => $s['guest']->id,
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-03',
        'currency' => 'EUR',
    ]);
}

/** ⚠️ THE RENDERED EMAIL BODY — the actual bytes a guest receives. */
function patBody(?string $in, ?string $out): string
{
    return (new PreArrivalMail(patReservation($in, $out)))->render();
}

// ── ITEM 23 · THE PRE-ARRIVAL EMAIL ───────────────────────────────────────────────────────────────

// ⚠️⚠️ 1. THE PUBLISHED TIMES ARE SENT — and they are not 15:00/11:00
it('sends the property published check-in and check-out times', function () {
    $body = patBody(PAT_IN, PAT_OUT);

    expect($body)->toContain('In from 14:00')
        ->and($body)->toContain('out by 10:30')
        // ⚠️ Neither invention appears in the bytes that go out.
        ->and($body)->not->toContain('In from 15:00')
        ->and($body)->not->toContain('out by 11:00')
        // …and the honest-absence line is absent, because nothing is absent.
        ->and($body)->not->toContain('will confirm your check-in');
});

// ⚠️⚠️ 2. NO PUBLISHED TIMES → AN HONEST ABSENCE IS SENT, not an invented hour
it('sends an honest absence when the property publishes no times', function () {
    $body = patBody(null, null);

    // The sentence still reads and still carries the real DATES — it simply claims no hour.
    expect($body)->toContain('In on')
        ->and($body)->toContain('out on')
        ->and($body)->toContain('The property will confirm your check-in and check-out times.')
        // ⚠️ THE FABRICATIONS ARE GONE FROM THE SENT BYTES.
        ->and($body)->not->toContain('In from 15:00')
        ->and($body)->not->toContain('out by 11:00')
        // ⚠️ AND NOT A DIFFERENT LIE — midnight is not an honest stand-in for "unpublished".
        ->and($body)->not->toContain('In from 00:00')
        ->and($body)->not->toContain('out by 00:00');
});

// ⚠️ 3. THE TWO TIMES ARE INDEPENDENT FACTS — one published, one not
it('treats the check-in and check-out times as independent facts in the email', function () {
    $body = patBody(PAT_IN, null);

    expect($body)->toContain('In from 14:00')
        ->and($body)->toContain('out on')
        ->and($body)->toContain('The property will confirm your check-in and check-out times.')
        ->and($body)->not->toContain('out by 11:00');
});

// ⚠️ 4. THE REST OF THE EMAIL IS UNDISTURBED — this was a VALUE fix, not a restyle
it('leaves the rest of the pre-arrival email intact', function () {
    $body = patBody(PAT_IN, PAT_OUT);

    expect($body)->toContain('Check-in &amp; out')          // the card's own heading
        ->and($body)->toContain('Arriving early?')          // the line directly below the fix
        ->and($body)->toContain('VYR-PAT-1');               // the booking it is about
});

// ── ITEM 22 · THE GUEST CHECK-IN CONTROLLER (the dormant sibling) ─────────────────────────────────

// ⚠️⚠️ 5. THE METHOD NO LONGER INVENTS AN HOUR — proven through the REAL private method
it('resolves the published check-in time or null, never a hardcoded hour', function () {
    $r = new ReflectionMethod(GuestCheckinController::class, 'timeLabel');
    $r->setAccessible(true);
    $c = (new ReflectionClass(GuestCheckinController::class))->newInstanceWithoutConstructor();

    // ⚠️ A published time flows through the ONE idiom…
    expect($r->invoke($c, PAT_IN))->toBe('14:00')
        ->and($r->invoke($c, PAT_IN))->toBe(StayTime::display(PAT_IN, 'H:i'))
        // …and every absent/unparseable case is NULL, not an invented hour.
        ->and($r->invoke($c, null))->toBeNull()
        ->and($r->invoke($c, ''))->toBeNull()
        ->and($r->invoke($c, 'not a time'))->toBeNull();
});

// ⚠️ 6. THE CHECK-IN PAGE STILL RENDERS, and shows no invented hour
it('renders the guest check-in page without any fabricated arrival hour', function () {
    $res = patReservation(null, null);
    $guest = $res->leadGuest;

    // ⚠️ THE NAMED ROUTE, never a hand-typed path — the first draft guessed `/guest/checkin/{id}` and got a
    // 404, which "failed" for a reason that had nothing to do with a fabricated hour.
    $html = test()->actingAs($guest, 'guest')->get(route('guest.checkin', $res->getKey()));

    // ⚠️ The page may legitimately redirect if the stay is not yet checkinable — either way it must not 500,
    // and if it renders it must carry no invented hour.
    expect(in_array($html->status(), [200, 302], true))->toBeTrue();

    if ($html->status() === 200) {
        expect($html->getContent())->not->toContain('from 15:00')
            ->and($html->getContent())->not->toContain('by 11:00');
    }
});
