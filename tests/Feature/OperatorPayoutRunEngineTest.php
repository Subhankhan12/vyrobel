<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\Payout;
use App\Models\PayoutRun;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #93 GATE 3 — the operator Payout Run wires the CERTIFIED PayoutRunService (test-mode): a real persisted run
// that RECONCILES (run total = Σ owner nets, #17) and moves NO real money, plus the read-only guard.

function engineStaff(array $s): User
{
    $user = User::factory()->create(['name' => 'Payout Staff', 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return grantBackOfficeRole($user, $s['org'], 'Accountant');   // holds payouts.manage
}

/** A real owner with tax details + a finalized statement (payable), through the certified services. */
function engineOwner(array $s, int $unitIndex, int $roomMinor, string $name): array
{
    $owner = Owner::factory()->create(['name' => $name, 'payout_currency' => 'EUR', 'status' => 'active', 'tax_id' => 'IE'.$unitIndex.'X']);
    $s['units'][$unitIndex]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 18.0, 'payout_schedule' => 'monthly', 'effective_from' => '2026-01-01', 'effective_to' => null]);
    $reservation = book($s, ['unit_id' => $s['units'][$unitIndex]->id]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    $statement = app(OwnerStatementService::class)->finalize(app(OwnerStatementService::class)->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    return ['owner' => $owner, 'statement' => $statement];
}

// ── The run is REAL + persisted + reconciles (total = Σ owner nets), TEST-MODE (no real money) ────
it('runs the certified engine into a persisted, reconciling, test-mode run', function () {
    $s = seedScenario(2);
    $a = engineOwner($s, 0, 20000, 'Amara Okafor');
    $b = engineOwner($s, 1, 30000, 'Bram Visser');
    $staff = engineStaff($s);

    $this->actingAs($staff)->post(route('app.payouts.run-now'))->assertRedirect(route('app.payouts.run'));

    // A REAL PayoutRun row was persisted, in TEST mode.
    $run = PayoutRun::query()->latest('created_at')->firstOrFail();
    expect($run->mode)->toBe('test')
        ->and($run->status)->toBe('completed');

    // The run total reconciles: Σ per-owner nets == the two finalized statements' net_payable (to the cent, #17).
    $expected = (int) $a['statement']->net_payable_minor + (int) $b['statement']->net_payable_minor;
    $paidSum = (int) Payout::query()->whereIn('status', ['paid', 'processing'])->sum('amount_minor');
    expect($paidSum)->toBe($expected);
    // No fabricated live transfer — the manual rail records; the mode is test.
    expect((array) $run->totals)->toBe(['EUR' => $expected]);
});

// ── The run is idempotent-ish: each payout equals the owner's exact net_payable ───────────────────
it('creates a payout equal to each owner exact net', function () {
    $s = seedScenario(1);
    $a = engineOwner($s, 0, 20000, 'Amara Okafor');
    $staff = engineStaff($s);

    $this->actingAs($staff)->post(route('app.payouts.run-now'))->assertRedirect();

    $payout = Payout::query()->where('owner_id', $a['owner']->id)->firstOrFail();
    expect((int) $payout->amount_minor)->toBe((int) $a['statement']->net_payable_minor);
});

// ── Read-only refuses the run + the pay writes ────────────────────────────────────────────────────
it('refuses payout writes while the workspace is paused', function () {
    $s = seedScenario(1);
    engineOwner($s, 0, 20000, 'Amara Okafor');
    $staff = engineStaff($s);
    $s['org']->forceFill(['paused_until' => now()->addMonth()])->save();

    $this->actingAs($staff)->post(route('app.payouts.run-now'))->assertRedirect()->assertSessionHas('payout_error');
    expect(PayoutRun::query()->count())->toBe(0);   // nothing ran
});

// ── The run is gated on payouts.manage ────────────────────────────────────────────────────────────
it('gates the payout run on payouts.manage', function () {
    $s = seedScenario(1);
    engineOwner($s, 0, 20000, 'Amara Okafor');
    $frontDesk = operatorUser($s, [], 'Front Desk');   // no payouts.manage

    $this->actingAs($frontDesk)->post(route('app.payouts.run-now'))->assertForbidden();
});
