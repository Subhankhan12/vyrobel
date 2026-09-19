<?php

use App\Models\RateCalendar;
use App\Models\Restriction;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OPERATOR RATES GRID (Wave 4 · Sub-gate 1) — the styled rate/availability grid + the FIRST-EVER production
// caller of RateAndRestrictionService (setRate / setRestriction). This file proves the four things the build
// must be true about:
//   • PER-ROOM-TYPE (STRUCT-1): the grid reads a room type's primary rate plan; a write moves that plan.
//   • INTEGER MONEY (#17): the € the operator types persists as EXACT minor units — no float drift.
//   • PERMISSION (#30): the write is gated on rates.manage — Reservation Agent (rates.view only) is refused,
//     the editor control is hidden from them, and the page itself is gated on rates.view.
//   • TENANT (#26 + global scope): a cross-org room_type id 404s for a fully-permitted actor.
// The design's fabricated chrome (smart pricing, "+8% vs Aug" trends, channel-sync pills) must not render.

/** Seed a nightly rate for the scenario's plan through the certified service, so the READ reflects a WRITE. */
function seedRate(array $s, string $start, string $endExclusive, int $minor): void
{
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], $start, $endExclusive, Money::of($minor, 'EUR'));
}

// ── 1. THE POINT OF THE PAGE — real rates, per room type, over the real design system ────────────
it('renders the per-room-type grid with rates the service wrote', function () {
    $s = seedScenario(3);                       // one room type, THREE units under it
    seedRate($s, '2026-09-01', '2026-09-08', 12500);   // €125.00 for 09-01…09-07

    $response = $this->actingAs(operatorUser($s, ['name' => 'Mo Manager'], 'Hotel Manager'))
        ->get('/app/rates?from=2026-09-01');

    $response->assertOk()
        ->assertSee('Std')                      // the room type name
        ->assertSee('€125.00')                  // the real rate, formatted from integer minor units
        ->assertSee('3 units')                  // the per-room-type sub-label — honest about STRUCT-1
        ->assertSee('Rates & availability');    // the screen title (assertSee escapes the needle → matches &amp;)

    assertNoRawBlade($response);
});

// ── 2. THE WRITE IS REAL — a POST persists via setRate, INTEGER minor units, over the inclusive range ──
it('writes a nightly rate through setRate as exact minor units across the inclusive range', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, ['name' => 'Mo Manager'], 'Hotel Manager');

    $this->actingAs($manager)->post('/app/rates', [
        'room_type_id' => $s['roomType']->id,
        'start_date' => '2026-09-05',
        'end_date' => '2026-09-07',     // INCLUSIVE — the operator means 5th, 6th AND 7th
        'set_rate' => '1',
        'rate' => '125.50',
    ])->assertRedirect(route('app.rates', ['from' => '2026-09-05']))
        ->assertSessionHas('rates_notice');

    // Three nights, each EXACTLY 12550 minor — never 12549 (the float-multiply bug 125.50 * 100 would risk).
    $rows = RateCalendar::query()
        ->where('rate_plan_id', $s['ratePlan']->id)
        ->whereIn('date', ['2026-09-05', '2026-09-06', '2026-09-07'])
        ->pluck('price_minor', 'date');

    expect($rows)->toHaveCount(3)
        ->and((int) $rows['2026-09-05'])->toBe(12550)
        ->and((int) $rows['2026-09-06'])->toBe(12550)
        ->and((int) $rows['2026-09-07'])->toBe(12550);

    // The half-open bound held: the day AFTER the inclusive end was NOT written.
    expect(RateCalendar::query()->where('rate_plan_id', $s['ratePlan']->id)->where('date', '2026-09-08')->exists())
        ->toBeFalse();
});

// ── 3. RESTRICTIONS ARE REAL — min-stay + stop-sell via setRestriction, at the room-type grain ───
it('writes min-stay and stop-sell through setRestriction', function () {
    $s = seedScenario(1);

    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->post('/app/rates', [
        'room_type_id' => $s['roomType']->id,
        'start_date' => '2026-09-10',
        'end_date' => '2026-09-10',
        'set_min_stay' => '1',
        'min_stay' => '2',
        'set_stop_sell' => '1',
        'stop_sell' => '1',
    ])->assertRedirect();

    // Room-type-wide (rate_plan_id NULL) — the grain the grid reads and writes, so a badge can't drift.
    $restriction = Restriction::query()
        ->where('room_type_id', $s['roomType']->id)
        ->whereNull('rate_plan_id')
        ->where('date', '2026-09-10')
        ->firstOrFail();

    expect((int) $restriction->min_stay)->toBe(2)
        ->and((bool) $restriction->stop_sell)->toBeTrue();
});

// ── 4. ⚠️ INTEGER MONEY — the string parser, not a float multiply (#17) ──────────────────────────
it('parses hand-typed money to exact minor units with no float drift', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');

    // 175.57 * 100 in float is 17556.9999…; the string parser must yield 17557 exactly.
    $this->actingAs($manager)->post('/app/rates', [
        'room_type_id' => $s['roomType']->id,
        'start_date' => '2026-09-02', 'end_date' => '2026-09-02',
        'set_rate' => '1', 'rate' => '175.57',
    ])->assertRedirect();

    // A whole number and a one-decimal amount also land on the right minor units.
    $this->actingAs($manager)->post('/app/rates', [
        'room_type_id' => $s['roomType']->id,
        'start_date' => '2026-09-03', 'end_date' => '2026-09-03',
        'set_rate' => '1', 'rate' => '200',
    ])->assertRedirect();

    $rows = RateCalendar::query()->where('rate_plan_id', $s['ratePlan']->id)
        ->whereIn('date', ['2026-09-02', '2026-09-03'])->pluck('price_minor', 'date');

    expect((int) $rows['2026-09-02'])->toBe(17557)
        ->and((int) $rows['2026-09-03'])->toBe(20000);
});

it('rejects an invalid rate and writes nothing', function () {
    $s = seedScenario(1);

    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->post('/app/rates', [
        'room_type_id' => $s['roomType']->id,
        'start_date' => '2026-09-02', 'end_date' => '2026-09-02',
        'set_rate' => '1', 'rate' => 'not-a-price',
    ])->assertRedirect()->assertSessionHas('rates_notice');

    expect(RateCalendar::query()->where('rate_plan_id', $s['ratePlan']->id)->where('date', '2026-09-02')->exists())
        ->toBeFalse();
});

// ── 5. PERMISSION (#30) — the write is gated on rates.manage; mutation-proven by the contrast ─────
it('refuses the rate write to a Reservation Agent, and lets a Hotel Manager through with the SAME payload', function () {
    $s = seedScenario(1);

    $payload = [
        'room_type_id' => $s['roomType']->id,
        'start_date' => '2026-09-04', 'end_date' => '2026-09-04',
        'set_rate' => '1', 'rate' => '140',
    ];

    // Reservation Agent holds rates.VIEW but not rates.MANAGE (separation of duties, NOT Front Desk either).
    $this->actingAs(operatorUser($s, ['name' => 'Rae Agent'], 'Reservation Agent'))
        ->post('/app/rates', $payload)
        ->assertForbidden();

    // MUTATION PROOF: the payload is otherwise valid — only the permission differs. Nothing was written.
    expect(RateCalendar::query()->where('rate_plan_id', $s['ratePlan']->id)->where('date', '2026-09-04')->exists())
        ->toBeFalse();

    // The SAME payload from a permitted role succeeds — so the 403 was the gate, not a bad request.
    $this->actingAs(operatorUser($s, ['name' => 'Mo Manager'], 'Hotel Manager'))
        ->post('/app/rates', $payload)
        ->assertRedirect();

    expect((int) RateCalendar::query()->where('rate_plan_id', $s['ratePlan']->id)->where('date', '2026-09-04')->value('price_minor'))
        ->toBe(14000);
});

it('shows the grid to a rates.view role but hides the editor control from it', function () {
    $s = seedScenario(1);
    seedRate($s, '2026-09-01', '2026-09-03', 10000);

    // Reservation Agent may VIEW rates → sees the grid…
    $this->actingAs(operatorUser($s, ['name' => 'Rae Agent'], 'Reservation Agent'))
        ->get('/app/rates?from=2026-09-01')
        ->assertOk()
        ->assertSee('€100.00')
        // …but may NOT manage them → the write form is absent (no dead-end 403 to walk into).
        ->assertDontSee('Set rates & availability')
        // ⚠️ BULK EDIT is the SECOND rates.manage control, and the design-parity pass MOVED it into the
        // control row. It is gated by the same permission, and this asserts the gate travelled with it —
        // a relocated control that quietly loses its gate is exactly the #30 "the UI invites a 403" defect.
        ->assertDontSee('Bulk edit');

    // A manager DOES see the editor — and the bulk control.
    $this->actingAs(operatorUser($s, ['name' => 'Mo Manager'], 'Hotel Manager'))
        ->get('/app/rates?from=2026-09-01')
        ->assertOk()
        ->assertSee('Set rates & availability')
        ->assertSee('Bulk edit');
});

// ── 6. THE PAGE ITSELF is gated on rates.view; the nav never dead-ends a role that lacks it ──────
it('forbids the rates page to a role without rates.view, and shows it no live nav link', function () {
    $s = seedScenario(1);

    // Housekeeping holds neither rates.view nor rates.manage.
    $housekeeper = operatorUser($s, ['name' => 'Hattie'], 'Housekeeping');
    $this->actingAs($housekeeper)->get('/app/rates')->assertForbidden();

    // …and their nav offers no link to it (a "Soon" placeholder instead), so the 403 is never reachable by click.
    $this->actingAs($housekeeper)->get('/app/dashboard')->assertOk()->assertDontSee(route('app.rates'));

    // A Revenue Manager (rates.view) DOES get a live nav link.
    $this->actingAs(operatorUser($s, ['name' => 'Ray Revenue'], 'Revenue Manager'))
        ->get('/app/dashboard')->assertOk()->assertSee(route('app.rates'));
});

// ── 7. TENANT (#26 + global scope) — a cross-org room type 404s for a PERMITTED actor ────────────
it('404s a cross-org room type for a fully-permitted actor, so tenancy is what is proven', function () {
    $foreign = seedScenario(1);                 // a foreign org's room type

    $s = seedScenario(1);                       // the home org (now the active context)
    $manager = operatorUser($s, ['name' => 'Mo Manager'], 'Hotel Manager');   // genuinely holds rates.manage

    // The 404 can only come from the tenant scope — the actor is permitted, so it is NOT a masked 403.
    $this->actingAs($manager)->post('/app/rates', [
        'room_type_id' => $foreign['roomType']->id,
        'start_date' => '2026-09-02', 'end_date' => '2026-09-02',
        'set_rate' => '1', 'rate' => '199',
    ])->assertNotFound();

    expect(RateCalendar::query()->withoutGlobalScopes()->where('rate_plan_id', $foreign['ratePlan']->id)->exists())
        ->toBeFalse();
});

// ── 8. HONESTY — none of the design's fabricated metrics, trends or channel statuses render ───────
it('renders no fabricated trend, smart-pricing or channel-sync status', function () {
    $s = seedScenario(1);
    seedRate($s, '2026-09-01', '2026-09-05', 10000);

    $response = $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->get('/app/rates?from=2026-09-01');

    $response->assertOk()
        ->assertDontSee('vs Aug')                 // fabricated trend — no baseline is stored
        ->assertDontSee('vs last year')
        ->assertDontSee('Smart pricing')          // no pricing engine exists
        ->assertDontSee('Syncing')                // channels.status is never written — a dead enum
        ->assertDontSee('Queued')
        ->assertDontSee('All channels');          // no rate_plan↔channel model
});

// ── ⚠️⚠️ F-21 — THE AGREED-RATE DISCLOSURE STATES ITS OWN SCOPE (founder ruling, 2026-09-19) ────────
/**
 * ⚠️⚠️ THE COUNT WAS CORRECT AND ITS LIMITS WERE INVISIBLE. 🔬 `AgreedRateDisclosure::forSeason()` counts a
 * night only when an **APPROVED `RateProposal`** covers it AND the current price **still equals** the agreed
 * figure. An owner's self-set rate (`OwnerRateService::apply()`) leaves no proposal, so it is excluded by the
 * first rule. ⚠️ Until FIX 46 all of that lived in a **Blade comment, which never renders** — so an operator
 * read "3 nights carry a negotiated rate" as "that is all that is at risk".
 *
 * ⚠️⚠️ IT IS ASSERTED ON THE RENDERED PAGE, NOT ON A VARIABLE OR A KEY. F-1, F-4 and F-5 were each "the value
 * was set and nothing rendered" — the whole finding is that the limit existed and was never shown.
 *
 * ⚠️⚠️ AND IT MUST RENDER WITH NO SEASON SELECTED AND NO AGREED NIGHTS, which is the case it matters most in:
 * inside the `agreed_nights > 0` template it would be hidden at ZERO, exactly when "nothing is at risk" is the
 * wrong conclusion to draw.
 */
it('⚠️⚠️ RULED 2026-09-19 (F-21) — the disclosure says what its count cannot see', function () {
    $s = seedScenario(1);

    $html = $this->actingAs(operatorUser($s, [], 'Property Owner'))
        ->get('/app/rates?from=2026-09-01')
        ->assertOk()
        ->getContent();

    // ⚠️ THE SCOPE SENTENCE — the rule, stated positively, so it cannot be an incomplete list of exclusions.
    expect($html)->toContain('That count sees only rates agreed through a proposal');
    expect($html)->toContain('only while the price still matches what was agreed');
    expect($html)->toContain('A rate an owner set themselves, or one that has since moved, is not counted here');

    // ⚠️ AND IT DOES NOT REPLACE OR CONTRADICT THE LINE THAT WAS ALREADY THERE — both must stand.
    expect($html)->toContain('Applying replaces those prices, and the owner is not told');
});
