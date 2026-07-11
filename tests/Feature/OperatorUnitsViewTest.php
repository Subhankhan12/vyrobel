<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Design pass · final operator stub (Units → UnitsList, /app/units). Locks its PRESENTATION guarantees:
// real units render with real fields + status keyed to the real enums; the owner LINK shows where set
// (owner NAME only — no encrypted owner PII); TENANT isolation holds (org B's units never shown to A);
// nothing fabricated.

function unitsMember(array $scenario, string $name = 'Marisol Vandenberg'): User
{
    $user = User::factory()->create(['name' => $name]);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return $user;
}

// ── 1. Real units render with real fields + owner link; no PII, no fabrication ───────────────────
it('renders real units with real status enums and the owner link', function () {
    $s = seedScenario(1);
    $s['units'][0]->update(['code' => 'UNITALPHA']);
    $owner = Owner::factory()->create(['name' => 'Marisol Estates', 'email' => 'owner-secret@example.test']);
    $s['units'][0]->update(['owner_id' => $owner->id]);

    $this->actingAs(unitsMember($s))->get('/app/units')
        ->assertOk()
        ->assertSee('Units')
        ->assertSee('UNITALPHA')          // real unit code
        ->assertSee('Std')                // real room type
        ->assertSee('Room')               // real kind (unit_kind = room)
        ->assertSee('Clean')              // real housekeeping_status enum
        ->assertSee('In service')         // real condition_status enum
        ->assertSee('Marisol Estates')    // owner LINK (business party, operator surface)
        ->assertDontSee('owner-secret@example.test')   // encrypted owner PII never rendered
        ->assertDontSee('Occupied')       // no invented unit status
        ->assertDontSee('Maintenance');
});

// ── 2. Tenant isolation — org B's units never shown to org A ─────────────────────────────────────
it('isolates tenants — org B units are never shown to org A', function () {
    $sA = seedScenario(1);
    $sA['units'][0]->update(['code' => 'AUNIT']);
    $userA = unitsMember($sA, 'Amara Okafor');

    $sB = seedScenario(1);
    $sB['units'][0]->update(['code' => 'BUNIT']);
    $userB = unitsMember($sB, 'Bao Nguyen');

    $this->actingAs($userA)->get('/app/units')->assertOk()->assertSee('AUNIT')->assertDontSee('BUNIT');
    $this->actingAs($userB)->get('/app/units')->assertOk()->assertSee('BUNIT')->assertDontSee('AUNIT');
});
