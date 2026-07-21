<?php

use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Support\Owner\OwnerLedgerAttribution;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// PHASE 6b · SG3 — OWNER REPORTS + INSIGHTS. Owner-scoped over the SG1 metric layer; earnings reconcile to
// the owner ledger (the same figures the statements show); a real-or-omitted observation; owner isolation
// mutation-proven (owner A never sees owner B's unit analytics).

/** A ledger credit/debit on an owner's unit. */
function ledgerEntry(string $ownerId, string $unitId, string $type, string $direction, int $minor): OwnerLedgerEntry
{
    return OwnerLedgerEntry::factory()->create([
        'owner_id' => $ownerId,
        'unit_id' => $unitId,
        'entry_type' => $type,
        'direction' => $direction,
        'amount_minor' => $minor,
        'currency' => 'EUR',
    ]);
}

// ── 1. 🔒 OWNER ISOLATION — owner A sees their unit's analytics; owner B never sees A's ─────────────
it('scopes owner Reports + Insights to the owner\'s own units — A cannot see B', function () {
    $s = seedScenario(2);
    $s['units'][0]->update(['code' => 'OWNED-A']);
    $s['units'][1]->update(['code' => 'OWNED-B']);
    $userA = ownerRoleUser($s, 0);   // owner A ← units[0]
    $userB = ownerRoleUser($s, 1);   // owner B ← units[1]

    book($s, ['unit_id' => $s['units'][0]->id, 'price_minor' => 20000]);   // a stay on A's unit only

    // Owner A: their unit is listed, with the real stay.
    $this->actingAs($userA)->get('/owner/reports')->assertOk()
        ->assertSee('OWNED-A')->assertDontSee('OWNED-B');

    // Owner B: their own (empty) unit only — never A's unit or its data.
    $this->actingAs($userB)->get('/owner/reports')->assertOk()
        ->assertSee('OWNED-B')->assertDontSee('OWNED-A');

    // Insights is owner-scoped too.
    $this->actingAs($userA)->get('/owner/insights')->assertOk();
    $this->actingAs($userB)->get('/owner/insights')->assertOk();
});

// ── 2. 🪤 EARNINGS RECONCILE TO THE OWNER LEDGER (the same figure the statements show) ──────────────
it('shows owner net earnings that reconcile to the owner ledger', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    // Room revenue credit − commission debit → net €255.00.
    ledgerEntry($ownerId, $s['units'][0]->id, 'room_revenue', 'credit', 30000);
    ledgerEntry($ownerId, $s['units'][0]->id, 'commission', 'debit', 4500);

    // The ledger net, computed by the ONE shared attribution rule — what statements show.
    $entries = OwnerLedgerEntry::query()->where('owner_id', $ownerId)->get();
    $ledgerNet = OwnerLedgerAttribution::netPerCurrency($entries);
    expect($ledgerNet)->toBe(['EUR' => 25500]);

    // Owner Reports shows exactly that figure — reconciles by construction.
    $this->actingAs($user)->get('/owner/reports')->assertOk()->assertSee('€255.00');
});

// ── 3. ✅ OWNER INSIGHTS — real metrics, and NO fabricated narrative ───────────────────────────────
it('shows owner insights metrics with no invented narrative', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    book($s, ['unit_id' => $s['units'][0]->id, 'price_minor' => 20000]);

    $res = $this->actingAs($user)->get('/owner/insights?range=this-year');

    $res->assertOk()->assertSee('Occupancy')->assertSee('ADR')->assertSee('RevPAR');

    // 🪤 The prototype's invented observations are never rendered (real-or-omitted).
    $res->assertDontSee('Saturdays sell out first')
        ->assertDontSee('pts behind')
        ->assertDontSee('2024')->assertDontSee('on record');

    assertNoRawBlade($res);
});

// ── 4. 🔒 A NON-LINKED USER FAILS CLOSED — sees NOTHING, never another owner's analytics ────────────
it('fails closed for a non-owner reaching owner analytics — empty, never someone else\'s data', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s, 0);   // a real owner with a unit + a booking
    book($s, ['unit_id' => $s['units'][0]->id, 'price_minor' => 20000]);
    $s['units'][0]->update(['code' => 'SOMEONES-UNIT']);

    // A back-office user with NO Owner link: the owner scope activates EMPTY (fail-closed) → they see the
    // empty owner portal, NEVER the real owner's unit or earnings.
    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->get('/owner/reports')
        ->assertOk()
        ->assertSee('No units linked to your account yet')
        ->assertDontSee('SOMEONES-UNIT');
});
