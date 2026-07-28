<?php

use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OWNER WELCOME / onboarding (Wave B · GATE 6) — the honest thin flow. REAL data (units + the real commission
// rate + a payout hand-off), NO IBAN capture, NO fake micro-deposit, nothing fabricated (#39).

it('renders the welcome flow with the owner\'s real units and REAL commission rate — no IBAN, no fake bank step', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);   // links an owner with a 15% agreement + a unit
    $s['units'][0]->update(['code' => 'CASA-ONE']);

    $this->actingAs($user)->get('/owner/welcome')->assertOk()
        ->assertSee('Welcome')
        ->assertSee('Your units are live')
        ->assertSee('CASA-ONE')                       // the owner's REAL unit
        ->assertSee('15% of booking revenue')         // the REAL agreement rate (ownerRoleUser sets 15%), never a hardcoded default
        ->assertSee('Add your payout account')
        ->assertSee(route('owner.payout-method'))     // hand-off to the certified page…
        // ⚠️ NO IBAN capture / micro-deposit on the welcome flow (honest thin onboarding)
        ->assertDontSee('IBAN')
        ->assertDontSee('micro-deposit')
        ->assertDontSee('two small deposits')
        ->assertSee('Go to your dashboard');
});

it('fails closed for a non-linked user', function () {
    $s = seedScenario(1);
    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    // No linked owner → the honest empty state (no units), never someone else's data.
    $this->actingAs($stranger)->get('/owner/welcome')->assertOk()
        ->assertSee('No units are linked to your account yet');
});
