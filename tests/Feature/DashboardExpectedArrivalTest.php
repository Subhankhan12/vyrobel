<?php

/*
 * D1-3 — `expected_arrival` ON THE OPERATOR DASHBOARD'S ARRIVALS (2026-08-15).
 *
 * ⚠️ A ONE-COLUMN-AWAY WIN. `reservations.expected_arrival` is real, GDPR-covered (`Guest::anonymize()`
 * nulls it) and already displayed on FRONT DESK — the dashboard's arrivals payload simply never selected it,
 * so the same operator saw the detail at the desk and lost it on the dashboard.
 *
 * ⚠️ A PREMISE CORRECTION FROM THE LEDGER. It describes the column as "real, POPULATED" — on the demo data it
 * is populated **0 times out of 41**. It is written at guest online check-in, so production carries values and
 * the demo does not. ⚠️ That INVERTS which branch is the unrendered sibling: the demo renders only the NULL
 * case, so it is the POPULATED case that has never appeared on a demo page and must be forced. Both are below.
 *
 * ⚠️ FREE TEXT, NOT A CLOCK. The value is whatever the guest typed — "afternoon", "after 6", "late". It is
 * passed through verbatim: never parsed, never formatted as a time, never defaulted to an hour. A guest who
 * wrote "afternoon" has not told us 12:00, and printing one would be a fabricated fact about a real person's
 * plans. ⚠️ `FrontDesk::parseArrivalMinutes()` DOES fall back to the property's default check-in — but only to
 * position a DOT on the timeline axis. A plotted guess must never become displayed text.
 *
 * ⚠️ ONE RENDERING, ENFORCED BY TEST RATHER THAN BY EXTRACTION. There is no shared arrival-row component, and
 * this pass was scoped to leave Front Desk untouched — so the dashboard MATCHES Front Desk's idiom (identical
 * `trim()`-to-null normalisation, identical "Arrival · " label, identical omit-when-null) and the parity test
 * below reads BOTH sources so the two cannot drift apart unnoticed. Extracting a shared component is logged
 * as the §N.8f follow-up (D1-3a), not done here.
 */

use App\Models\Reservation;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/** ⚠️ Prefixed — a top-level const in a Pest file is GLOBAL and has collided in this suite before. */
const D13_FREE_TEXT = 'late afternoon, after 6';

/** An operator who can see both the dashboard and Front Desk. */
function d13Operator(array $s)
{
    return operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');
}

/**
 * A reservation arriving TODAY (so it lands in the dashboard's arrivals list).
 *
 * ⚠️ NOT via the `book()` helper: its overrides apply to the LINE only — the reservation header's
 * check_in/check_out are hardcoded to 2026-09-01, so a "today" override silently produced a September
 * booking that never appeared in today's arrivals. Built through the certified AssignmentService instead.
 */
function d13ArrivingToday(array $s, ?string $expectedArrival): Reservation
{
    $today = now()->toDateString();
    $out = now()->addDays(2)->toDateString();

    // The seeded spine covers the scenario's own window; an arrival TODAY needs today's rows published first.
    app(AvailabilityService::class)->recompute($s['roomType']->id, $today, now()->addDays(3)->toDateString());

    $reservation = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $today, 'check_out' => $out,
            'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'rate_plan_id' => $s['ratePlan']->id,
            'check_in' => $today, 'check_out' => $out, 'price_minor' => 10000]],
    );

    // Written directly: the column is set at guest online check-in, which is not what this item is about.
    Reservation::withoutGlobalScopes()->whereKey($reservation->getKey())
        ->update(['expected_arrival' => $expectedArrival]);

    return $reservation->fresh();
}

it('⚠️ D1-3 — a POPULATED expected_arrival renders on the dashboard (the branch the demo never shows)', function () {
    $s = seedScenario(1);
    $reservation = d13ArrivingToday($s, D13_FREE_TEXT);

    // ⚠️ NON-VACUITY: the value really is stored, so a passing assertion below means the payload carried it.
    expect((string) $reservation->expected_arrival)->toBe(D13_FREE_TEXT);

    $res = $this->actingAs(d13Operator($s))->get('/app/dashboard')->assertOk();

    $res->assertSee('Arrival · '.D13_FREE_TEXT, escape: false);
    assertNoRawBlade($res);
});

it('⚠️ D1-3 — the guest’s words are printed VERBATIM: never parsed, formatted or defaulted to an hour', function () {
    $s = seedScenario(1);
    d13ArrivingToday($s, 'afternoon');

    $html = (string) $this->actingAs(d13Operator($s))->get('/app/dashboard')->assertOk()->getContent();

    // The guest's actual word survives…
    expect($html)->toContain('Arrival · afternoon');

    // …and no clock value was invented from it. ⚠️ Scoped to the arrivals block, because the dashboard
    // legitimately prints times elsewhere (the occupancy week, KPI labels) — a page-wide ban on `HH:mm`
    // would fail on correct markup, the over-broad-literal trap the register already records.
    preg_match('/Arrival · afternoon.{0,200}/s', $html, $m);
    expect($m[0] ?? '')->not->toBeEmpty()
        ->and($m[0])->not->toMatch('/\d{1,2}:\d{2}/');
});

it('⚠️ D1-3 — a NULL expected_arrival omits the line entirely (no fake time, no time-like dash)', function () {
    $s = seedScenario(1);
    d13ArrivingToday($s, null);

    $res = $this->actingAs(d13Operator($s))->get('/app/dashboard')->assertOk();

    // ⚠️ NON-VACUITY: the arrival IS on the page — the row renders, only the arrival LINE is absent.
    $html = (string) $res->getContent();
    expect($html)->toContain('vy-arr-row')
        ->and($html)->not->toContain('Arrival · ');

    assertNoRawBlade($res);
});

it('⚠️ D1-3 — an EMPTY-STRING value is treated as absent, exactly as Front Desk treats it', function () {
    // The `trim()`-to-null normalisation, which is the half of the idiom easiest to omit by accident.
    $s = seedScenario(1);
    d13ArrivingToday($s, '   ');

    $html = (string) $this->actingAs(d13Operator($s))->get('/app/dashboard')->assertOk()->getContent();
    expect($html)->not->toContain('Arrival · ');
});

it('⚠️ D1-3 — the dashboard and FRONT DESK render the same value identically (§N.8f parity)', function () {
    /*
     * ⚠️ THE DRIFT GUARD. With no shared component, "matching Front Desk" is a claim about two files that a
     * future edit to either could quietly break. This reads BOTH — the same reservation through both pages —
     * and additionally pins the two source idioms, so a divergence fails here rather than in production.
     */
    $s = seedScenario(1);
    d13ArrivingToday($s, D13_FREE_TEXT);
    $operator = d13Operator($s);

    $dashboard = (string) $this->actingAs($operator)->get('/app/dashboard')->assertOk()->getContent();
    $frontDesk = (string) $this->actingAs($operator)->get('/app/front-desk')->assertOk()->getContent();

    // The same label + the same verbatim value on both surfaces.
    expect($dashboard)->toContain('Arrival · '.D13_FREE_TEXT)
        ->and($frontDesk)->toContain('Arrival · '.D13_FREE_TEXT);

    // And the two PAYLOAD normalisations are the same expression, not merely similar.
    $dashSrc = (string) i18nEnglishSource(app_path('Livewire/Operator/OperatorDashboard.php'));
    $fdSrc = (string) i18nEnglishSource(app_path('Livewire/FrontDesk.php'));
    $idiom = "trim((string) (\$reservation->expected_arrival ?? ''))) !== '' ? \$ea : null";
    expect($dashSrc)->toContain($idiom)
        ->and($fdSrc)->toContain($idiom);

    // Both Blades omit-when-null with the same label — the rendering half of the idiom.
    foreach (['livewire/operator/dashboard', 'livewire/front-desk'] as $view) {
        $blade = (string) i18nEnglishSource(resource_path('views/'.$view.'.blade.php'));
        expect(str_contains($blade, "@if (\$a['expected_arrival'])") || str_contains($blade, "@if (\$row['expected_arrival'])"))
            ->toBeTrue("{$view} does not omit the arrival line when null");
        expect($blade)->toContain('Arrival · ');
    }
});

it('⚠️ D1-3 — GDPR: the field reaches the SAME audience as Front Desk, and no wider', function () {
    /*
     * ⚠️ A GDPR-COVERED FIELD MUST NOT GAIN A BROADER AUDIENCE. Both routes sit in one middleware group
     * (`auth` + EnsureBackOfficeAccess + …) and NEITHER carries an extra `can:` permission, so any
     * back-office user who can open the dashboard can already open Front Desk and see this value there.
     * Adding it here exposes nothing new — asserted rather than assumed, because "same audience" was the
     * precondition for doing this at all.
     */
    $routes = (string) file_get_contents(base_path('routes/web.php'));

    foreach (["Route::get('/dashboard', OperatorDashboard::class)", "Route::get('/front-desk', FrontDesk::class)"] as $decl) {
        expect($routes)->toContain($decl);
        // Neither declaration carries its own permission gate — they share the group's.
        $line = (string) (preg_match('/^.*'.preg_quote($decl, '/').'.*$/m', $routes, $m) ? $m[0] : '');
        expect($line)->not->toBeEmpty()
            ->and($line)->not->toContain('can:');
    }

    // And it IS covered by the erase path — the reason it counts as GDPR data in the first place.
    expect((string) i18nEnglishSource(app_path('Models/Guest.php')))->toContain("'expected_arrival' => null");
});

it('⚠️ D1-3 — the other arrivals fields are untouched', function () {
    // A payload addition must not disturb what was already correct.
    $s = seedScenario(1);
    $reservation = d13ArrivingToday($s, D13_FREE_TEXT);

    $html = (string) $this->actingAs(d13Operator($s))->get('/app/dashboard')->assertOk()->getContent();

    expect($html)->toContain((string) $reservation->reference)   // reference
        ->toContain('2 nights')                                   // nights
        ->toContain((string) $s['units'][0]->code);               // unit
});

it('⚠️ D1-3 — no fixture residue (raw query, independent of any scope)', function () {
    expect((int) DB::table('reservations')->whereNotNull('expected_arrival')->count())->toBe(0);
});
