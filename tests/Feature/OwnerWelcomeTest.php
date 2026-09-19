<?php

use App\Models\Membership;
use App\Models\PayoutMethod;
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
    // ⚠️ R-GATE (founder, 2026-09-19): this asserted the portal RENDERS EMPTY for a non-owner — the
    //    behaviour F-23 recorded as the defect. The refusal is now the intended answer: a non-owner is
    //    turned away at the realm door instead of being shown an owner's portal that is not theirs.
    $this->actingAs($stranger)->get('/owner/welcome')->assertForbidden();
});

// ── The payout card answers "is an account on file?", and nothing else ────────────────────────────
// Owner-29: the flag read `$units->isNotEmpty() && PayoutMethod::exists()`, so an owner who had already
// saved a payout method but had no units yet was shown the EMPTY branch — "add a bank account on the
// secure Payout Method page". That is a false INSTRUCTION, not merely a wrong badge: it tells someone
// to do a thing they have already done. Units have no bearing on whether an account is on file.
it('does not tell an owner with a payout account on file to add one', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $owner = $s['units'][0]->fresh()->owner;

    // A real payout method on file — and the owner's units detached, the shape that exposed it.
    PayoutMethod::create([
        'organization_id' => $s['org']->id,
        'owner_id' => $owner->id,
        'account_holder' => 'Owner',
        'iban' => 'GB29NWBK60161331926819',
        'iban_last4' => '6819',
        'iban_country' => 'GB',
        'currency' => 'EUR',
        'is_default' => true,
    ]);
    $s['units'][0]->update(['owner_id' => null]);

    $this->actingAs($user)->get('/owner/welcome')->assertOk()
        ->assertSee('A payout account is on file')
        ->assertDontSee('add a bank account on the secure Payout Method page');
});
