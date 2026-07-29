<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerPayoutPreference;
use App\Models\Payout;
use App\Models\PayoutRun;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use App\Services\PayoutRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// PAYOUT ENGINE — Phase 4 surfacing. The Balance + Payouts owner pages reflect REAL engine state
// (held / in transit / paid), clearly TEST-MODE-labelled — never implying real money moves.

/** A portal owner (primary → CAP_PAYOUT) with a finalized Sep statement; optionally no tax id (→ held). */
function surfaceOwner(array $s, int $unitIndex, bool $noTax = false): array
{
    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR'] + ($noTax ? ['tax_id' => null] : []));
    $s['units'][$unitIndex]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);

    $reservation = book($s, ['unit_id' => $s['units'][$unitIndex]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    $svc = app(OwnerStatementService::class);
    $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    return ['user' => $user, 'owner' => $owner];
}

// ── The Balance page shows the engine's HELD state, TEST-MODE-labelled, reconciling ───────────────
it('renders the engine hold on Balance, test-mode-labelled, with the clearing identity intact', function () {
    $s = seedScenario(1);
    ['user' => $user, 'owner' => $owner] = surfaceOwner($s, 0, noTax: true);

    // The engine holds the payout (owner has no tax details) — a REAL persisted state, no money moved.
    app(PayoutRunService::class)->run(PayoutRun::TRIGGER_MANUAL);
    expect(Payout::query()->where('owner_id', $owner->id)->where('status', 'held')->count())->toBe(1);

    $this->actingAs($user)->get('/owner/balance')
        ->assertOk()
        ->assertSee('Test mode')          // ⚠️ TEST-MODE banner — never implies real money moves
        ->assertSee('On hold');           // the held payout surfaces in the clearing view

    // The clearing identity still reconciles with the persisted held state (#17).
    $available = (int) OwnerLedgerEntry::query()->where('owner_id', $owner->id)->whereNull('statement_id')
        ->get()->sum(fn (OwnerLedgerEntry $e) => $e->signedMinor());
    $held = (int) Payout::query()->where('owner_id', $owner->id)->where('status', 'held')->sum('amount_minor');
    $paid = (int) Payout::query()->where('owner_id', $owner->id)->where('status', 'paid')->sum('amount_minor');
    $net = (int) OwnerLedgerEntry::query()->where('owner_id', $owner->id)->get()->sum(fn (OwnerLedgerEntry $e) => $e->signedMinor());
    expect($available + $held + 0 + $paid)->toBe($net);
});

// ── The Payouts page shows the REAL next scheduled date + the TEST-MODE banner ────────────────────
it('renders the real next scheduled payout date on Payouts, test-mode-labelled', function () {
    $s = seedScenario(1);
    ['user' => $user, 'owner' => $owner] = surfaceOwner($s, 0);
    OwnerPayoutPreference::create([
        'organization_id' => $owner->organization_id, 'owner_id' => $owner->id,
        'frequency' => 'monthly', 'payout_day' => 3, 'minimum_minor' => 0, 'paused' => false,
    ]);

    $this->actingAs($user)->get('/owner/payouts')
        ->assertOk()
        ->assertSee('Test mode')                 // ⚠️ TEST-MODE banner
        ->assertSee('Next scheduled payout')     // the placeholder is now a real, dated schedule
        ->assertDontSee('once scheduling is wired');   // the old honest-deferred copy is gone
});
