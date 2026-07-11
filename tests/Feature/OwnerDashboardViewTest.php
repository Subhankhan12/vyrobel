<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Design pass · Screen 2 (Owner Dashboard). Locks the PRESENTATION guarantees of the styled dashboard:
// live owner-scoped data, figures reconcile with the underlying statements, NO guest PII, no-backend
// affordances are disabled (not faked), owner isolation holds. (Fail-closed empty states — "No units" /
// "No statements" for a non-linked user — are already asserted by OwnerPortalIsolationTest and preserved.)

/** A linked owner with a finalized statement; returns user/owner/unit/statement. */
function dashboardOwner(array $s, int $unitIndex, int $roomMinor): array
{
    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);

    $unit = $s['units'][$unitIndex];
    $unit->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);

    $reservation = book($s, ['unit_id' => $unit->id]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation);

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    return compact('user', 'owner', 'unit', 'statement');
}

// ── 1. Real data renders + reconciles; NO guest PII; no-backend items disabled, not faked ────────
it('renders real dashboard data with no guest PII and disabled no-backend affordances', function () {
    $s = seedScenario(1);
    // Give the booking guest a distinctive name so a PII leak on "upcoming stays" would be visible.
    $s['guest']->update(['first_name' => 'Zephyrina', 'last_name' => 'Quibblewick']);
    $a = dashboardOwner($s, 0, 20000);   // room €200.00, 15% commission → net €170.00
    $a['unit']->update(['code' => 'ALPHAUNIT']);   // distinctive code (a 2-char code can collide with a Livewire id)

    $this->actingAs($a['user'])->get('/owner')
        ->assertOk()
        ->assertSee('Overview')
        ->assertSee('ALPHAUNIT')                               // real unit (owner-scoped)
        ->assertSee('€170.00')                                 // this-month net (hero + KPI + recent row)
        ->assertSee('Total units')
        ->assertSee('Earned YTD')
        ->assertSee('data-countup="17000"', false)             // count-up animates the REAL value
        ->assertSee('Not yet available')                       // occupancy deferred, not fabricated
        ->assertSee('Guest personal details stay private')     // upcoming-stays honest stub
        ->assertDontSee('Quibblewick')                         // ⚠️ guest PII never exposed
        ->assertSee('Soon');                                   // no-backend affordance disabled, not faked

    // Reconciliation: the Earned-YTD figure == Σ the owner's statement nets == €170.00.
    expect((int) OwnerStatement::query()->where('owner_id', $a['owner']->id)->sum('net_payable_minor'))->toBe(17000);
});

// ── 2. Owner isolation — A's dashboard shows only A's figures, never B's ─────────────────────────
it('scopes the dashboard to the authenticated owner and never shows another owner’s figures', function () {
    $s = seedScenario(2);
    $a = dashboardOwner($s, 0, 20000);   // net €170.00
    $b = dashboardOwner($s, 1, 50000);   // net €425.00 (distinctive)
    // Distinctive codes: a 2-char seed code ("U2") can collide with a substring of Livewire's random
    // component id, false-failing assertDontSee. The €-amount assertions are the real isolation check.
    $a['unit']->update(['code' => 'ALPHAUNIT']);
    $b['unit']->update(['code' => 'BRAVOUNIT']);

    $this->actingAs($a['user'])->get('/owner')
        ->assertOk()
        ->assertSee('ALPHAUNIT')
        ->assertSee('€170.00')
        ->assertDontSee('BRAVOUNIT')
        ->assertDontSee('€425.00');
});
