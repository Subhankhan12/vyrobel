<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Support\Money\MoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Wave 1 · Sub-gate 1 (Owner Balance) — a NEW owner-scoped page over the CERTIFIED owner-accounting
// backend. Locks its PRESENTATION guarantees: the real `owner_ledger` renders; the DERIVED net
// position + running balance reconcile TO THE CENT against the source rows; the REAL flags
// (prior-period / unverified / statement link) render; owner isolation holds (another owner's ledger
// invisible, a non-linked user fails closed); and the design's fictional clearing state machine
// (on-hold / tax-ID hold / IBAN / withdrawal ETA) is NEVER invented.

/** A linked owner with an attributed booking, a finalized statement, and a payout. */
function balanceOwner(array $s, int $unitIndex, int $roomMinor, bool $pay = false): array
{
    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);

    $unit = $s['units'][$unitIndex];
    $unit->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create([
        'owner_id' => $owner->id,
        'commission_pct' => 15.00,
        'effective_from' => '2026-01-01',
        'payout_schedule' => 'monthly',
    ]);

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

// ── 1. Real ledger + a net position that reconciles to the cent + NO invented clearing state ─────
it('renders the real ledger, reconciles the net position to the cent, and invents no clearing state', function () {
    $s = seedScenario(1);
    $a = balanceOwner($s, 0, 20000, pay: true);

    // The DERIVED figure's SOURCE OF TRUTH: Σ signedMinor() over the owner's real ledger rows —
    // €200.00 room revenue − €30.00 commission (15%) − €0.00 unverified OTA = €170.00.
    $netMinor = ownerNet($a['owner']->id);
    expect($netMinor)->toBe(17000);

    $this->actingAs($a['user'])->get('/owner/balance')
        ->assertOk()
        ->assertSee('Balance activity')
        ->assertSee('Net position')
        // The rendered Net position IS that ledger sum — reconciled to the cent, not a coincidence.
        ->assertSee(MoneyFormatter::display($netMinor, 'EUR'))     // €170.00
        // REAL rows, described by the certified attribution engine itself
        ->assertSee('Room revenue · 2 night(s)')
        ->assertSee('Management commission 15%')
        ->assertSee('+€200.00')                                    // signed credit
        ->assertSee('−€30.00')                                     // signed debit (U+2212, per MoneyFormatter)
        ->assertSee('Unverified')                                  // REAL flag on the direct booking's OTA line
        ->assertSee('/owner/statements/'.$a['statement']->id)      // REAL statement_id link
        ->assertSee('Monthly')                                     // REAL agreement payout_schedule
        ->assertSee('Paid to date')
        // the badges are DATA-DRIVEN, not decorative: nothing was sealed when these rows were posted,
        // so prior_period_adjustment is false here (test 3 proves the badge appears when it is true).
        ->assertDontSee('Prior-period adjustment')
        // ⚠️ the design's fictional CLEARING state machine must not be invented
        ->assertDontSee('Add tax details')
        ->assertDontSee('Hold clearing')
        ->assertDontSee('How money clears')
        ->assertDontSee('On hold')
        // ⚠️ no fake payout account, no fake ETA, no owner-initiated withdrawal
        ->assertDontSee('IBAN')
        ->assertDontSee('4821')
        ->assertDontSee('arrives in')
        ->assertDontSee('Withdraw early');
});

// ── 2. Owner isolation — A sees only A's ledger; a non-linked user fails closed ──────────────────
it('scopes the ledger to the owner, hides other owners, and fails closed for a non-linked user', function () {
    $s = seedScenario(2);
    $a = balanceOwner($s, 0, 20000, pay: true);   // net €170.00
    $b = balanceOwner($s, 1, 50000, pay: true);   // net €425.00 (distinctive)

    expect(ownerNet($a['owner']->id))->toBe(17000)
        ->and(ownerNet($b['owner']->id))->toBe(42500);

    $this->actingAs($a['user'])->get('/owner/balance')
        ->assertOk()
        ->assertSee('€170.00')
        ->assertDontSee('€425.00')      // owner B's net position
        ->assertDontSee('+€500.00');    // owner B's room-revenue row

    // A non-linked authenticated member resolves to an EMPTY owner scope → nothing, never all owners.
    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    $this->actingAs($stranger)->get('/owner/balance')
        ->assertOk()
        ->assertSee('No balance activity')
        ->assertSee('€0.00')
        ->assertDontSee('€170.00')
        ->assertDontSee('€425.00')
        // the agreement read is NOT OwnerScope-backed (no BelongsToOwner) — it is gated explicitly,
        // so it must fail closed here too rather than leak another owner's terms.
        ->assertDontSee('Monthly');
});

// ── 3. The REAL per-row flags: prior-period badge + an honestly unstatemented row ────────────────
it('badges a prior-period adjustment and says plainly when a row is not yet on a statement', function () {
    $s = seedScenario(1);
    $a = balanceOwner($s, 0, 20000, pay: true);   // 3 rows, all claimed by the finalized statement

    // A REAL later row dated AFTER the sealed September period, so it is genuinely unclaimed
    // (statement_id null — `claimable()` only takes service_date <= period_end).
    OwnerLedgerEntry::factory()->create([
        'owner_id' => $a['owner']->id,
        'unit_id' => $a['unit']->id,
        'entry_type' => 'adjustment',
        'direction' => 'debit',
        'amount_minor' => 2500,
        'currency' => 'EUR',
        'description' => 'Late supplier credit',
        'service_date' => '2026-10-05',
        'prior_period_adjustment' => true,
    ]);

    $netMinor = ownerNet($a['owner']->id);
    expect($netMinor)->toBe(14500);   // 17000 − 2500 — the running balance must land exactly here

    $this->actingAs($a['user'])->get('/owner/balance')
        ->assertOk()
        ->assertSee('Late supplier credit')
        ->assertSee('Prior-period adjustment')
        ->assertSee('Not yet on a statement')
        ->assertSee('−€25.00')
        ->assertSee(MoneyFormatter::display($netMinor, 'EUR'));   // €145.00 — net + newest running balance
});

// ── 5. No unrendered Blade leaks to the owner ────────────────────────────────────────────────────
// A directive whose `@` follows a word character is not compiled and ships raw source to the page as
// ordinary text — an assertSee/assertDontSee suite passes straight over it. Real bug found in the
// browser on the Expenses screen (Wave 1 · Sub-gate 1); guarded here for the whole owner portal.
it('renders no raw Blade directives', function () {
    $s = seedScenario(1);
    balanceOwner($s, 0, 20000, pay: true);

    assertNoRawBlade($this->actingAs(User::where('name', 'Amara Okafor')->firstOrFail())->get('/owner/balance')->assertOk());
});
