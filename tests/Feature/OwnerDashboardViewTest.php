<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerMonthlyTarget;
use App\Models\OwnerStatement;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

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

// ── 1z. GATE 4 — the earnings chart's goal line is the REAL monthly target, only when set ─────────
it('draws the earnings goal line from the real monthly target only when the owner has set one', function () {
    $s = seedScenario(1);
    $a = dashboardOwner($s, 0, 20000);

    // No target set → no goal line, no fabricated goal (#39).
    $this->actingAs($a['user'])->get('/owner')->assertOk()->assertDontSee('Goal €');

    // Set a REAL monthly target (the same model the Bookings ring uses) → the goal line renders.
    OwnerMonthlyTarget::updateOrCreate(
        ['owner_id' => $a['owner']->id, 'currency' => 'EUR'],
        ['organization_id' => $s['org']->id, 'target_minor' => 500000],   // €5,000
    );

    $this->actingAs($a['user'])->get('/owner')->assertOk()->assertSee('Goal €5,000.00');
});

// ── 1. Real data renders + reconciles; upcoming stays name-only (#28); no fabrication ────────────
it('renders real dashboard data, shows upcoming stays name-only, and fabricates nothing', function () {
    // Freeze "today" so the seeded 2026-09-01 booking is a future arrival WITHIN 30 days (an upcoming stay).
    Carbon::setTestNow('2026-08-20 12:00:00');

    $s = seedScenario(1);
    // A distinctive guest name + real contact PII — the name may show (name-only, #28); the contact must NOT.
    $s['guest']->update(['first_name' => 'Zephyrina', 'last_name' => 'Quibblewick', 'email' => 'zeph@quibble.test', 'phone' => '+3197000111222']);
    $a = dashboardOwner($s, 0, 20000);   // room €200.00, 15% commission → net €170.00
    $a['unit']->update(['code' => 'ALPHAUNIT']);   // distinctive code (a 2-char code can collide with a Livewire id)

    $response = $this->actingAs($a['user'])->get('/owner')->assertOk();

    $response
        ->assertSee('ALPHAUNIT')                               // real unit (owner-scoped)
        ->assertSee('€170.00')                                 // this-month net (hero + KPI + recent row)
        ->assertSee('Total units')
        ->assertSee('Earned YTD')
        ->assertSee('data-countup="17000"', false)             // count-up animates the REAL value
        // Upcoming stays — REAL, guest NAME only (invariant #28)
        ->assertSee('Zephyrina Quibblewick')                   // the guest NAME is shown (name-only)
        ->assertDontSee('zeph@quibble.test')                   // ⚠️ guest email NEVER on an owner surface
        ->assertDontSee('+3197000111222')                      // ⚠️ guest phone NEVER
        // Attention — a REAL item (this owner has no payout method on file)
        ->assertSee('Needs your attention')
        ->assertSee('Confirm your payout bank account')
        // No fabrication (#39): occupancy is no longer a stub; no invented earnings goal or estimated payout date
        ->assertDontSee('Not yet available')
        ->assertDontSee('Occupancy soon');

    // ⚠️ This owner has set NO monthly target, so NO goal line may be drawn (#39 — never an invented goal).
    // Asserted over the page CONTENT: the raw-body form of this guard also matched the `.vy-ov-goal` CSS
    // class in the layout's inline stylesheet, which is a style rule, not something the owner is shown.
    // Scoped to the claim, not weakened — see assertDontSeeInContent(). The positive case (a REAL target
    // renders "Goal €5,000.00") is pinned by the test directly above.
    assertDontSeeInContent($response, 'goal');
    assertDontSeeInContent($response, 'Goal');

    assertNoRawBlade($response);   // the word@directive Blade trap never ships raw source

    // Reconciliation: the Earned-YTD figure == Σ the owner's statement nets == €170.00.
    expect((int) OwnerStatement::query()->where('owner_id', $a['owner']->id)->sum('net_payable_minor'))->toBe(17000);

    Carbon::setTestNow();
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
