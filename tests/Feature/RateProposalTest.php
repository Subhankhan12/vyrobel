<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\RateCalendar;
use App\Models\RateGuardrail;
use App\Models\RatePlan;
use App\Models\RateProposal;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\User;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// RATE PROPOSALS (Wave C · GATE 1 · ⚠️ REAL MONEY) — the operator→owner rate-proposal state machine. The contract:
//   • operator PROPOSES on an EXCLUSIVELY-owned room type → pending (a shared room type is refused);
//   • ⚠️ owner APPROVES → the rate GENUINELY changes via the certified path (mutation-proven in rate_calendar);
//   • owner DECLINES → the rate is UNCHANGED (mutation-proven);
//   • a decided proposal can't be re-decided; owner A can't decide owner B's proposal;
//   • a pending proposal surfaces as a REAL Approve/Decline attention item on My Units + Overview.

/**
 * An owner with an EXCLUSIVELY-owned room type (one unit, one plan, an initial rate) + a linked portal user,
 * plus an operator (Hotel Manager → rates.manage). Returns the pieces the tests act on.
 */
function proposalScenario(array $s): array
{
    $property = $s['property'];
    $rt = RoomType::create(['property_id' => $property->id, 'name' => 'Azure Suite', 'kind' => 'room']);
    $unit = Unit::create(['property_id' => $property->id, 'room_type_id' => $rt->id, 'code' => 'U-'.Str::upper(Str::random(4))]);
    $plan = RatePlan::create(['property_id' => $property->id, 'room_type_id' => $rt->id, 'name' => 'Flex', 'currency' => 'EUR']);
    $rt->update(['default_rate_plan_id' => $plan->id]);

    $owner = ownUnit($unit);   // owner + agreement + unit->owner_id
    $ownerUser = User::factory()->create(['name' => 'Olive Owner']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $ownerUser->id, 'status' => 'active']);
    $owner->update(['user_id' => $ownerUser->id]);

    // An initial nightly rate (€180) on today's date so the snapshot + the "changed" assertion have a baseline.
    RateCalendar::create(['rate_plan_id' => $plan->id, 'date' => now()->toDateString(), 'price' => Money::of(18000, 'EUR')]);

    $operator = operatorUser($s, ['name' => 'Manny Manager'], 'Hotel Manager');   // holds rates.manage

    return compact('owner', 'ownerUser', 'operator', 'rt', 'plan', 'unit');
}

/** The stored nightly rate (minor) for a plan on a date, or null. */
function proposalRateMinor(string $planId, string $date): ?int
{
    $row = RateCalendar::query()->where('rate_plan_id', $planId)->whereDate('date', $date)->first();

    return $row === null ? null : (int) $row->price_minor;
}

// ── 1. Operator proposes → a pending proposal with the truthful current-rate snapshot ─────────────
it('lets the operator propose a rate change and snapshots the current rate', function () {
    $s = seedScenario(1);
    $c = proposalScenario($s);

    $this->actingAs($c['operator'])->post(route('app.rate-proposals.store'), [
        'owner_id' => $c['owner']->id, 'room_type_id' => $c['rt']->id,
        'rate' => '185', 'from_date' => now()->toDateString(), 'to_date' => now()->addDays(30)->toDateString(),
    ])->assertRedirect();

    $p = RateProposal::query()->firstOrFail();
    expect($p->state)->toBe('pending')
        ->and((int) $p->current_rate_minor)->toBe(18000)     // the real snapshot
        ->and((int) $p->proposed_rate_minor)->toBe(18500)
        ->and((string) $p->owner_id)->toBe((string) $c['owner']->id);
});

// ── 2. ⚠️ Owner APPROVES → the rate GENUINELY changes (mutation-proven) ────────────────────────────
it('applies the proposed rate when the owner approves — the rate really changes', function () {
    $s = seedScenario(1);
    $c = proposalScenario($s);
    $today = now()->toDateString();

    $this->actingAs($c['operator'])->post(route('app.rate-proposals.store'), [
        'owner_id' => $c['owner']->id, 'room_type_id' => $c['rt']->id,
        'rate' => '185', 'from_date' => $today, 'to_date' => $today,
    ])->assertRedirect();
    $p = RateProposal::query()->firstOrFail();

    // Before approval the rate is still the original €180.
    expect(proposalRateMinor((string) $c['plan']->id, $today))->toBe(18000);

    $this->actingAs($c['ownerUser'])->post(route('owner.rate-proposals.approve', $p->id))->assertRedirect();

    // ⚠️ After approval the certified path really moved the price to €185 — and the proposal is closed.
    expect(proposalRateMinor((string) $c['plan']->id, $today))->toBe(18500)
        ->and($p->fresh()->state)->toBe('approved')
        ->and($p->fresh()->decided_at)->not->toBeNull();
});

// ── 3. Owner DECLINES → the rate is UNCHANGED (mutation-proven) ────────────────────────────────────
it('leaves the rate unchanged when the owner declines', function () {
    $s = seedScenario(1);
    $c = proposalScenario($s);
    $today = now()->toDateString();

    $this->actingAs($c['operator'])->post(route('app.rate-proposals.store'), [
        'owner_id' => $c['owner']->id, 'room_type_id' => $c['rt']->id,
        'rate' => '185', 'from_date' => $today, 'to_date' => $today,
    ])->assertRedirect();
    $p = RateProposal::query()->firstOrFail();

    $this->actingAs($c['ownerUser'])->post(route('owner.rate-proposals.decline', $p->id))->assertRedirect();

    expect(proposalRateMinor((string) $c['plan']->id, $today))->toBe(18000)   // still €180 — untouched
        ->and($p->fresh()->state)->toBe('declined');
});

// ── 4. A decided proposal can't be re-decided; owner B can't decide owner A's ─────────────────────
it('guards the state machine and owner isolation', function () {
    $s = seedScenario(1);
    $a = proposalScenario($s);

    $this->actingAs($a['operator'])->post(route('app.rate-proposals.store'), [
        'owner_id' => $a['owner']->id, 'room_type_id' => $a['rt']->id,
        'rate' => '185', 'from_date' => now()->toDateString(), 'to_date' => now()->toDateString(),
    ])->assertRedirect();
    $p = RateProposal::query()->firstOrFail();

    // A second owner cannot touch A's proposal (owner-scoped → 404).
    $b = proposalScenario($s);
    $this->actingAs($b['ownerUser'])->post(route('owner.rate-proposals.approve', $p->id))->assertNotFound();
    expect($p->fresh()->state)->toBe('pending');

    // Decline it, then a second decision is a no-op (guarded).
    $this->actingAs($a['ownerUser'])->post(route('owner.rate-proposals.decline', $p->id))->assertRedirect();
    $this->actingAs($a['ownerUser'])->post(route('owner.rate-proposals.approve', $p->id))->assertRedirect();
    expect($p->fresh()->state)->toBe('declined');   // unchanged by the late approve
});

// ── 5. A shared room type is refused (never moves another party's price) ───────────────────────────
it('refuses a proposal on a room type the owner does not own exclusively', function () {
    $s = seedScenario(1);
    $c = proposalScenario($s);
    // Add an operator-owned unit (owner_id null) to the SAME room type → no longer exclusive.
    Unit::create(['property_id' => $s['property']->id, 'room_type_id' => $c['rt']->id, 'code' => 'U-OPER']);

    $this->actingAs($c['operator'])->post(route('app.rate-proposals.store'), [
        'owner_id' => $c['owner']->id, 'room_type_id' => $c['rt']->id,
        'rate' => '185', 'from_date' => now()->toDateString(), 'to_date' => now()->toDateString(),
    ])->assertRedirect()->assertSessionHas('rate_error');

    expect(RateProposal::query()->count())->toBe(0);
});

// ── 6. A pending proposal surfaces as a REAL Approve/Decline attention item ────────────────────────
it('surfaces a pending proposal as an attention item on My Units and Overview', function () {
    $s = seedScenario(1);
    $c = proposalScenario($s);

    $this->actingAs($c['operator'])->post(route('app.rate-proposals.store'), [
        'owner_id' => $c['owner']->id, 'room_type_id' => $c['rt']->id,
        'rate' => '185', 'from_date' => now()->toDateString(), 'to_date' => now()->toDateString(),
    ])->assertRedirect();
    $p = RateProposal::query()->firstOrFail();

    foreach (['/owner/units', '/owner'] as $url) {
        $this->actingAs($c['ownerUser'])->get($url)->assertOk()
            ->assertSee('Rate change to approve')
            ->assertSee('€180.00 → €185.00')                                  // the truthful before/after
            ->assertSee(route('owner.rate-proposals.approve', $p->id), false) // real Approve action
            ->assertSee(route('owner.rate-proposals.decline', $p->id), false);
    }
});

// ── COUNTER (Owner Rates exact-match #81) — owner counters → operator accepts → the OWNER'S rate applies ──
it('lets the owner counter a proposal and the operator accept it — the owner’s rate genuinely applies', function () {
    $s = seedScenario(1);
    $c = proposalScenario($s);
    $today = now()->toDateString();

    // Operator proposes €185.
    $this->actingAs($c['operator'])->post(route('app.rate-proposals.store'), [
        'owner_id' => $c['owner']->id, 'room_type_id' => $c['rt']->id,
        'rate' => '185', 'from_date' => $today, 'to_date' => now()->addDays(10)->toDateString(),
    ])->assertRedirect();
    $p = RateProposal::query()->firstOrFail();

    // OWNER COUNTERS with €195 — state=countered, nothing applied yet (rate still €180).
    $this->actingAs($c['ownerUser'])->post(route('owner.rate-proposals.counter', $p->id), ['counter_rate' => '195'])
        ->assertRedirect();
    $p->refresh();
    expect($p->state)->toBe('countered')
        ->and((int) $p->countered_rate_minor)->toBe(19500)
        ->and(proposalRateMinor((string) $c['plan']->id, $today))->toBe(18000);   // unchanged while countered

    // OPERATOR ACCEPTS the counter → the OWNER'S €195 is applied (the rate really changes).
    $this->actingAs($c['operator'])->post(route('app.rate-proposals.accept-counter', $p->id))->assertRedirect();
    $p->refresh();
    expect($p->state)->toBe('approved')
        ->and(proposalRateMinor((string) $c['plan']->id, $today))->toBe(19500);   // the owner's counter is live
});

it('refuses a counter to another owner’s proposal (owner-scoped 404)', function () {
    $s = seedScenario(1);
    $c = proposalScenario($s);
    $this->actingAs($c['operator'])->post(route('app.rate-proposals.store'), [
        'owner_id' => $c['owner']->id, 'room_type_id' => $c['rt']->id,
        'rate' => '185', 'from_date' => now()->toDateString(), 'to_date' => now()->addDays(5)->toDateString(),
    ])->assertRedirect();
    $p = RateProposal::query()->firstOrFail();

    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);
    $this->actingAs($stranger)->post(route('owner.rate-proposals.counter', $p->id), ['counter_rate' => '195'])->assertNotFound();
    expect($p->fresh()->state)->toBe('pending');
});

// ── GUARDRAILS (#81) — a SAVED guardrail GENUINELY constrains the manager's proposals (mutation-proven) ──
it('rejects an operator proposal below the owner’s floor / above the ceiling', function () {
    $s = seedScenario(1);
    $c = proposalScenario($s);
    RateGuardrail::create(['owner_id' => $c['owner']->id, 'pricing_mode' => 'fixed', 'auto_approve_pct' => 0, 'floor_minor' => 15000, 'ceiling_minor' => 30000, 'currency' => 'EUR']);

    // €140 is below the €150 floor → rejected, no proposal created.
    $this->actingAs($c['operator'])->post(route('app.rate-proposals.store'), [
        'owner_id' => $c['owner']->id, 'room_type_id' => $c['rt']->id,
        'rate' => '140', 'from_date' => now()->toDateString(), 'to_date' => now()->addDays(5)->toDateString(),
    ])->assertRedirect()->assertSessionHas('rate_error');
    expect(RateProposal::query()->count())->toBe(0);

    // €320 is above the €300 ceiling → also rejected.
    $this->actingAs($c['operator'])->post(route('app.rate-proposals.store'), [
        'owner_id' => $c['owner']->id, 'room_type_id' => $c['rt']->id,
        'rate' => '320', 'from_date' => now()->toDateString(), 'to_date' => now()->addDays(5)->toDateString(),
    ])->assertSessionHas('rate_error');
    expect(RateProposal::query()->count())->toBe(0);
});

it('auto-approves a small change within the dynamic tolerance, and leaves a larger one pending', function () {
    $s = seedScenario(1);
    $c = proposalScenario($s);
    $today = now()->toDateString();
    RateGuardrail::create(['owner_id' => $c['owner']->id, 'pricing_mode' => 'dynamic', 'auto_approve_pct' => 5, 'currency' => 'EUR']);

    // €185 vs €180 = +2.8% ≤ 5% → AUTO-APPROVED (applied immediately, no owner action).
    $this->actingAs($c['operator'])->post(route('app.rate-proposals.store'), [
        'owner_id' => $c['owner']->id, 'room_type_id' => $c['rt']->id,
        'rate' => '185', 'from_date' => $today, 'to_date' => now()->addDays(10)->toDateString(),
    ])->assertRedirect();
    $p = RateProposal::query()->firstOrFail();
    expect($p->state)->toBe('approved')
        ->and(proposalRateMinor((string) $c['plan']->id, $today))->toBe(18500);   // genuinely live

    // €230 vs €180 = +27.8% > 5% → PENDING (the owner decides); the rate is unchanged.
    $this->actingAs($c['operator'])->post(route('app.rate-proposals.store'), [
        'owner_id' => $c['owner']->id, 'room_type_id' => $c['rt']->id,
        'rate' => '230', 'from_date' => $today, 'to_date' => now()->addDays(10)->toDateString(),
    ])->assertRedirect();
    $pending = RateProposal::query()->where('proposed_rate_minor', 23000)->firstOrFail();
    expect($pending->state)->toBe('pending')
        ->and(proposalRateMinor((string) $c['plan']->id, $today))->toBe(18500);   // still the auto-approved 185, not 230
});

it('never auto-approves in fixed mode — even a tiny change waits for the owner', function () {
    $s = seedScenario(1);
    $c = proposalScenario($s);
    RateGuardrail::create(['owner_id' => $c['owner']->id, 'pricing_mode' => 'fixed', 'auto_approve_pct' => 10, 'currency' => 'EUR']);

    $this->actingAs($c['operator'])->post(route('app.rate-proposals.store'), [
        'owner_id' => $c['owner']->id, 'room_type_id' => $c['rt']->id,
        'rate' => '182', 'from_date' => now()->toDateString(), 'to_date' => now()->addDays(5)->toDateString(),
    ])->assertRedirect();
    expect(RateProposal::query()->firstOrFail()->state)->toBe('pending');
});

it('saves the owner’s guardrails through the real controller (CAP_ACTIONS, owner-scoped)', function () {
    $s = seedScenario(1);
    $c = proposalScenario($s);

    $this->actingAs($c['ownerUser'])->post(route('owner.rates.guardrails'), [
        'pricing_mode' => 'dynamic', 'auto_approve_pct' => '8', 'floor' => '150', 'ceiling' => '600',
    ])->assertRedirect()->assertSessionHas('owner_notice');

    $g = RateGuardrail::query()->where('owner_id', $c['owner']->id)->firstOrFail();
    expect($g->pricing_mode)->toBe('dynamic')
        ->and((int) $g->auto_approve_pct)->toBe(8)
        ->and((int) $g->floor_minor)->toBe(15000)
        ->and((int) $g->ceiling_minor)->toBe(60000);
});
