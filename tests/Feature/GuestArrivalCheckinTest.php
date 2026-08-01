<?php

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Unit;
use App\Services\AssignmentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// GUEST CHECK-IN + ARRIVAL INFO (Phase 4b · Sub-gate 4). Two guarantees are proven here:
//   • ONLINE CHECK-IN is the GUEST's pre-check-in intent (stamps guest_checked_in_at) — it is NOT the operator's
//     authoritative check-in (status stays 'confirmed'); passport/ID is an honest KYC stub (nothing captured).
//   • DOOR-CODE ISOLATION: the arrival pack — especially the door code — shows ONLY for the authed guest's OWN
//     booking (foreign → 404), and only inside the arrival window (withheld before it; never fabricated).

if (! function_exists('arrGuest')) {
    function arrGuest(Guest $g, string $email): Guest
    {
        $g->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $g->password = 'guest-password-1234';
        $g->email_verified_at = now();
        $g->save();

        return $g->fresh();
    }
}

if (! function_exists('arrBook')) {
    function arrBook(array $s, Guest $guest, Unit $unit, string $in = '2026-09-05', string $out = '2026-09-08'): Reservation
    {
        return app(AssignmentService::class)->createBooking(
            ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $in, 'check_out' => $out, 'lead_guest_id' => $guest->getKey(), 'source' => 'direct'],
            [['room_type_id' => $s['roomType']->id, 'unit_id' => $unit->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => $in, 'check_out' => $out, 'price_minor' => 10000, 'currency' => 'EUR']],
        );
    }
}

// ─────────────────────────────── ONLINE CHECK-IN ───────────────────────────────

// ── The guest's pre-check-in stamps a flag — it is NOT the operator's authoritative check-in ──────
it('records a guest pre-check-in without performing the operator’s authoritative check-in', function () {
    $s = seedScenario(1);
    $a = arrGuest($s['guest'], 'ada@example.test');
    $reservation = arrBook($s, $a, $s['units'][0]);

    $this->actingAs($a, 'guest')->post(route('guest.checkin.submit', $reservation->getKey()))->assertRedirect();

    $fresh = $reservation->fresh();
    expect($fresh->guest_checked_in_at)->not->toBeNull();   // the guest's submitted intent is recorded…
    expect($fresh->status)->toBe('confirmed');              // …but the booking's status is UNTOUCHED (operator owns that)
});

// ── The passport / ID capture is an honest KYC stub — nothing is captured ─────────────────────────
it('shows the check-in review with an honest ID-verification stub', function () {
    $s = seedScenario(1);
    $a = arrGuest($s['guest'], 'ada@example.test');
    $reservation = arrBook($s, $a, $s['units'][0]);

    $this->actingAs($a, 'guest')->get(route('guest.checkin', $reservation->getKey()))
        ->assertOk()
        ->assertSee('Online check-in')
        ->assertSee('coming soon');   // ID verification deferred honestly
});

// ── A second submit is idempotent (no re-stamp, no error) ─────────────────────────────────────────
it('is idempotent on a repeated pre-check-in', function () {
    $s = seedScenario(1);
    $a = arrGuest($s['guest'], 'ada@example.test');
    $reservation = arrBook($s, $a, $s['units'][0]);

    $this->actingAs($a, 'guest')->post(route('guest.checkin.submit', $reservation->getKey()))->assertRedirect();
    $first = $reservation->fresh()->guest_checked_in_at;
    $this->actingAs($a, 'guest')->post(route('guest.checkin.submit', $reservation->getKey()))->assertRedirect();

    expect($reservation->fresh()->guest_checked_in_at->equalTo($first))->toBeTrue();   // unchanged
});

// ── ISOLATION: a guest can't check in / view another guest's booking ──────────────────────────────
it('404s when a guest checks in another guest’s booking', function () {
    $s = seedScenario(2);
    $a = arrGuest($s['guest'], 'ada@example.test');
    $b = Guest::create(['email' => 'bob@example.test', 'first_name' => 'Bob', 'last_name' => 'Vale']);
    $bRes = arrBook($s, $b, $s['units'][1]);

    $this->actingAs($a, 'guest')->get(route('guest.checkin', $bRes->getKey()))->assertNotFound();
    $this->actingAs($a, 'guest')->post(route('guest.checkin.submit', $bRes->getKey()))->assertNotFound();
    expect($bRes->fresh()->guest_checked_in_at)->toBeNull();   // untouched
});

// ─────────────────────────────── ARRIVAL INFO / DOOR CODE ───────────────────────────────

// ── Within the arrival window, the owned booking's real door code + wifi show ─────────────────────
it('shows the door code and wifi for an owned booking inside the arrival window', function () {
    $s = seedScenario(1);
    $a = arrGuest($s['guest'], 'ada@example.test');
    $s['units'][0]->update(['door_code' => '2468', 'wifi_ssid' => 'Seaside-Guest', 'wifi_password' => 'welcome2026']);
    $reservation = arrBook($s, $a, $s['units'][0]);

    $this->travelTo(CarbonImmutable::parse('2026-09-06'));   // inside the stay
    $this->actingAs($a, 'guest')->get(route('guest.arrival', $reservation->getKey()))
        ->assertOk()
        ->assertSee('2468')            // the real door code
        ->assertSee('Seaside-Guest');  // the wifi
    $this->travelBack();
});

// ── TIMING: the same owned booking withholds the code well before arrival (never fabricated) ──────
it('withholds the door code before the arrival window opens', function () {
    $s = seedScenario(1);
    $a = arrGuest($s['guest'], 'ada@example.test');
    $s['units'][0]->update(['door_code' => '2468', 'wifi_ssid' => 'Seaside-Guest', 'wifi_password' => 'welcome2026']);
    $reservation = arrBook($s, $a, $s['units'][0]);

    $this->travelTo(CarbonImmutable::parse('2026-08-01'));   // weeks before check-in
    $this->actingAs($a, 'guest')->get(route('guest.arrival', $reservation->getKey()))
        ->assertOk()
        ->assertDontSee('2468')                        // code withheld
        ->assertSee('closer to arrival');              // honest copy instead
    $this->travelBack();
});

// ── No fabricated code: an owned booking whose unit has no code says so honestly ──────────────────
it('never fabricates a door code when the unit has none', function () {
    $s = seedScenario(1);
    $a = arrGuest($s['guest'], 'ada@example.test');
    // units[0] has NO door_code set
    $reservation = arrBook($s, $a, $s['units'][0]);

    $this->travelTo(CarbonImmutable::parse('2026-09-06'));
    $this->actingAs($a, 'guest')->get(route('guest.arrival', $reservation->getKey()))
        ->assertOk()
        ->assertSee('Collect your key at reception');   // honest — no invented code
    $this->travelBack();
});

// ── DOOR-CODE ISOLATION (the sensitive one, mutation-proven): A cannot see B's arrival/door code ──
it('404s when a guest opens another guest’s arrival info (door code isolation)', function () {
    $s = seedScenario(2);
    $a = arrGuest($s['guest'], 'ada@example.test');
    $b = Guest::create(['email' => 'bob@example.test', 'first_name' => 'Bob', 'last_name' => 'Vale']);
    $s['units'][0]->update(['door_code' => 'AAA-OWN']);
    $s['units'][1]->update(['door_code' => 'BBB-SECRET']);
    $aRes = arrBook($s, $a, $s['units'][0]);
    $bRes = arrBook($s, $b, $s['units'][1]);

    $this->travelTo(CarbonImmutable::parse('2026-09-06'));
    $this->actingAs($a, 'guest')->get(route('guest.arrival', $aRes->getKey()))->assertOk()->assertSee('AAA-OWN');   // own → shown
    $this->actingAs($a, 'guest')->get(route('guest.arrival', $bRes->getKey()))->assertNotFound();                   // foreign → 404
    $this->travelBack();
});

// ─────────────────────────────── ARRIVAL GUIDE PDF (GATE 2) ───────────────────────────────

// ── The printable guide downloads for an owned booking (real PDF, guest-scoped) ────────────────────
it('downloads the arrival guide PDF for an owned booking', function () {
    $s = seedScenario(1);
    $a = arrGuest($s['guest'], 'ada@example.test');
    $s['units'][0]->update(['door_code' => '2468', 'wifi_ssid' => 'Seaside-Guest', 'wifi_password' => 'welcome2026']);
    $reservation = arrBook($s, $a, $s['units'][0]);

    $this->travelTo(CarbonImmutable::parse('2026-09-06'));
    $response = $this->actingAs($a, 'guest')->get(route('guest.arrival.pdf', $reservation->getKey()));
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('attachment');
    $this->travelBack();
});

// ── 🔒 #33 — the PDF shows the code INSIDE the window but OMITS it before (same gate as the screen) ──
it('gates the door code on the arrival PDF exactly like the screen', function () {
    $s = seedScenario(1);
    $a = arrGuest($s['guest'], 'ada@example.test');
    $s['units'][0]->update(['door_code' => '2468', 'wifi_ssid' => 'Seaside-Guest', 'wifi_password' => 'welcome2026']);
    $reservation = arrBook($s, $a, $s['units'][0])->load(['property', 'reservationUnits.unit', 'leadGuest']);

    // Inside the window → the guide carries the real code + wifi.
    $inside = view('pdfs.arrival', \App\Support\Pdf\ArrivalPackDocument::data($reservation, CarbonImmutable::parse('2026-09-06')))->render();
    expect($inside)->toContain('2468')->toContain('Seaside-Guest');

    // Weeks before → the shareable file NEVER carries the code; it prints the honest state.
    $before = view('pdfs.arrival', \App\Support\Pdf\ArrivalPackDocument::data($reservation, CarbonImmutable::parse('2026-08-01')))->render();
    expect($before)->not->toContain('2468')->toContain('unlock');
});

// ── ISOLATION: a guest cannot download another guest's arrival PDF ─────────────────────────────────
it('404s downloading another guest’s arrival PDF', function () {
    $s = seedScenario(2);
    $a = arrGuest($s['guest'], 'ada@example.test');
    $b = Guest::create(['email' => 'bob@example.test', 'first_name' => 'Bob', 'last_name' => 'Vale']);
    $s['units'][1]->update(['door_code' => 'BBB-SECRET']);
    $bRes = arrBook($s, $b, $s['units'][1]);

    $this->travelTo(CarbonImmutable::parse('2026-09-06'));
    $this->actingAs($a, 'guest')->get(route('guest.arrival.pdf', $bRes->getKey()))->assertNotFound();
    $this->travelBack();
});
