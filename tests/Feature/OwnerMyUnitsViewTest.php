<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\RateCalendar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Design pass · Screen 4 (Owner My Units) — a NEW owner-scoped page. Locks its PRESENTATION guarantees:
// units render owner-scoped; OCCUPANCY is genuinely the owner's (excludes a non-owner unit in the same
// room type — the room-type-wide availabilityRange trap is NOT used); owner isolation holds (fail-closed);
// no guest PII.

/** Link a user to a fresh owner and give them the unit at $index (with a distinctive code). */
function linkUnitOwner(array $s, int $index, string $code): array
{
    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    $unit = $s['units'][$index];
    $unit->update(['owner_id' => $owner->id, 'code' => $code]);

    return ['user' => $user, 'owner' => $owner, 'unit' => $unit];
}

// ── 1. Occupancy is OWNER-SCOPED — a non-owner unit's booking in the same room type is excluded ──
it('computes owner-scoped occupancy that excludes a non-owner unit in the same room type', function () {
    $s = seedScenario(2);   // U1, U2 in the SAME room type
    // Guest name is NAME-ONLY on the P2-3 day-detail (like Unit Detail / Block Calendar); CONTACT is the tripwire.
    $s['guest']->update(['first_name' => 'Zephyrina', 'last_name' => 'Quibblewick', 'email' => 'zephyrina@example.test', 'phone' => '+15550009999']);

    $a = linkUnitOwner($s, 0, 'OWNEDUNIT');          // owner A owns unit 0
    $foreign = $s['units'][1];
    $foreign->update(['code' => 'FOREIGNUNIT']);      // unit 1 — same room type, NOT owner A's

    book($s, ['unit_id' => $a['unit']->id]);          // owner's unit — 2 nights (2026-09-01..03)
    book($s, ['unit_id' => $foreign->id]);            // non-owner unit — 2 nights, same room type

    // ⚠️ The month is now selected EXPLICITLY. My Units used to default to the month of the owner's latest
    // booking while captioning it "This month" (wireframe-parity WAVE 2 · #11); it now defaults to the
    // CURRENT month, so a test whose data lives in Sep 2026 must ask for Sep 2026 — as a user would.
    $this->actingAs($a['user'])->get('/owner/units?month=2026-09')
        ->assertOk()
        ->assertSee('OWNEDUNIT')                      // owner's unit shown
        ->assertDontSee('FOREIGNUNIT')                // non-owner unit hidden (isolation)
        ->assertSee('2 nights')                       // owner occupancy = the owner's unit ONLY (2 nights)
        ->assertDontSee('4 nights')                   // NOT U1+U2 → the room-type-wide count is excluded
        ->assertSee('Quibblewick')                    // P2-3 — the day-detail shows the guest NAME-only (#28 OK)
        ->assertDontSee('zephyrina@example.test')     // …but NEVER contact PII (email)…
        ->assertDontSee('+15550009999');              // …nor phone (#28 — name-only, contact stays on the CRM)
});

// ── 2. Owner isolation — A sees only A's units; a non-linked user fails closed ───────────────────
it('scopes units to the owner, hides other owners, and fails closed for a non-linked user', function () {
    $s = seedScenario(2);
    $a = linkUnitOwner($s, 0, 'AUNIT');
    linkUnitOwner($s, 1, 'BUNIT');   // a second owner with the other unit

    $this->actingAs($a['user'])->get('/owner/units')
        ->assertOk()
        ->assertSee('AUNIT')
        ->assertDontSee('BUNIT');

    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    $this->actingAs($stranger)->get('/owner/units')
        ->assertOk()
        ->assertSee('No units')       // fail closed — empty, never all owners
        ->assertDontSee('AUNIT')
        ->assertDontSee('BUNIT');
});

// ── Batch B · GATE 2 — REAL per-unit analytics (revenue · nightly rate · best performer · earned) ──
it('renders REAL per-unit revenue, nightly rate, best performer and a real attention feed', function () {
    $s = seedScenario(2);
    $a = linkUnitOwner($s, 0, 'UNITALPHA');
    $s['units'][1]->update(['owner_id' => $a['owner']->id, 'code' => 'UNITBETA']);   // owner owns both
    book($s, ['unit_id' => $a['unit']->id]);   // only ALPHA is booked → it is the best performer

    $this->actingAs($a['user'])->get('/owner/units?month=2026-09')   // see #11 above — month is explicit now
        ->assertOk()
        ->assertSee('Earned this month')            // the real ledger-net KPI (Batch B)
        ->assertSee('Nightly rate')                 // the per-unit nightly-rate row is wired (was "Soon")
        ->assertSee('Best performer')
        ->assertSee('UNITALPHA')                    // the booked unit leads
        ->assertSee('★ Best')                       // the best badge sits on the leading card
        ->assertSee('this month')                   // the best-performer sub-line (real nights · revenue this month) — a real ranking, not a fabricated winner
        // the attention feed shows a REAL item (fresh owner → no payout method), never a fabricated proposal
        ->assertSee('Confirm your payout bank account')
        ->assertDontSee('rate change proposed');    // the prototype's invented rate proposals are NOT built (#39)
});

// ── #39 — a unit with NO revenue shows an honest empty best-performer, never a fabricated winner ──
it('shows an honest empty best performer when nothing has earned (#39)', function () {
    $s = seedScenario(1);
    $a = linkUnitOwner($s, 0, 'IDLEUNIT');   // owned, but never booked

    $this->actingAs($a['user'])->get('/owner/units')
        ->assertOk()
        ->assertSee('IDLEUNIT')
        ->assertSee('No bookings yet this month')   // honest empty best-performer state
        ->assertSee('€0.00');                       // earned-this-month is a clean real zero, not fabricated
});

// ── GATE 2 — the real card/calendar actions are wired (never faked) ──────────────────────────────
it('renders the real edit-pricing stepper for an exclusively-owned room type + a unit-scoped message relay', function () {
    $s = seedScenario(1);   // one unit, one room type → the owner owns it EXCLUSIVELY → editable
    $s['guest']->update(['first_name' => 'Zephyrina', 'last_name' => 'Quibblewick']);   // PII tripwire
    $a = linkUnitOwner($s, 0, 'SOLOUNIT');
    RateCalendar::create(['rate_plan_id' => $s['ratePlan']->id, 'date' => now()->toDateString(), 'price_minor' => 15000, 'currency' => 'EUR']);

    $this->actingAs($a['user'])->get('/owner/units')
        ->assertOk()
        ->assertSee('Set nightly rate')                     // the REAL rate stepper (exclusive room type)
        ->assertSee(route('owner.rates.apply'), false)      // posts to the certified rate service
        ->assertSee('Message your management team about')   // the D2 relay (owner↔operator, never owner→guest)
        ->assertSee(route('owner.messages.start'), false)
        ->assertDontSee('Quibblewick');                     // #28 — no guest name on My Units
});

it('exports the month occupancy as an owner-scoped CSV (counts only, #28)', function () {
    $s = seedScenario(1);
    $a = linkUnitOwner($s, 0, 'SOLOUNIT');

    $response = $this->actingAs($a['user'])->get('/owner/units/export');
    $response->assertOk();
    expect($response->streamedContent())->toContain('Units booked')->toContain('Month total');
});
