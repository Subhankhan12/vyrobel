<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\Payout;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Design pass · Screen 3 (Owner Payouts) — a NEW owner-scoped page. Locks its PRESENTATION guarantees:
// live payout data renders; paid-this-year reconciles to Σ real paid payouts; the lifecycle shows the
// REAL states only (no invented "Scheduled"/"On hold"); no fake IBAN/date; owner isolation holds
// (another owner's payouts invisible, non-linked user fails closed).

/** A linked owner with a finalized statement + payout (optionally paid through the manual rail). */
function payoutOwner(array $s, int $unitIndex, int $roomMinor, bool $pay = false): array
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

    $payoutSvc = app(OwnerPayoutService::class);
    $payout = $payoutSvc->createFromStatement($statement);
    if ($pay && $payout !== null) {
        $payout = $payoutSvc->pay($payout);   // manual rail (default)
    }

    return compact('user', 'owner', 'unit', 'statement', 'payout');
}

// ── 1. Real payout data + paid-year reconciliation + NO invented statuses + no fake IBAN ─────────
it('renders real payout data, reconciles paid-this-year, and invents no Scheduled/On-hold statuses', function () {
    $s = seedScenario(1);
    $a = payoutOwner($s, 0, 20000, pay: true);   // net €170.00, payout PAID via the manual rail

    $this->actingAs($a['user'])->get('/owner/payouts')
        ->assertOk()
        ->assertSee('Payout history')
        ->assertSee('Paid this year')
        ->assertSee('€170.00')                       // the real payout amount
        ->assertSee('Bank transfer')                 // rail=manual → "Bank transfer"
        ->assertSee('Finalized')                     // REAL lifecycle labels only
        ->assertSee('Paid')
        ->assertSee('data-countup="17000"', false)   // count-up on the REAL amount
        // ⚠️ the design's non-existent lifecycle states must NOT be invented
        ->assertDontSee('Scheduled')
        ->assertDontSee('On hold')
        ->assertDontSee('On-hold')
        ->assertDontSee('Verification pending')
        // no fake payout-account details
        ->assertDontSee('IBAN')
        ->assertDontSee('4821');

    // paid-this-year reconciles to Σ the owner's REAL paid payouts.
    expect((int) Payout::query()->where('owner_id', $a['owner']->id)->where('status', 'paid')->sum('amount_minor'))->toBe(17000);
});

// ── 2. Owner isolation — A sees only A's payouts; a non-linked user fails closed ─────────────────
it('scopes payouts to the owner, hides other owners, and fails closed for a non-linked user', function () {
    $s = seedScenario(2);
    $a = payoutOwner($s, 0, 20000, pay: true);   // €170.00
    $b = payoutOwner($s, 1, 50000, pay: true);   // €425.00 (distinctive)

    $this->actingAs($a['user'])->get('/owner/payouts')
        ->assertOk()
        ->assertSee('€170.00')
        ->assertDontSee('€425.00');

    // A non-linked authenticated user resolves to an empty owner scope → nothing, never all owners.
    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    $this->actingAs($stranger)->get('/owner/payouts')
        ->assertOk()
        ->assertSee('No payouts')
        ->assertDontSee('€170.00')
        ->assertDontSee('€425.00');
});
