<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\Payout;
use App\Models\PayoutMethod;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Support\Money\MoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

// Owner Balance (exact-match pass · invariant #59) — the CERTIFIED `owner_ledger` presented as the decoded
// wireframe's CLEARING view. Locks the PRESENTATION + MONEY-SAFETY guarantees:
//   • the reconciling identity  Available + On hold + In transit + Paid == Net position  (per currency, to
//     the cent, #17) — Available = unstamped-ledger net, On hold/In transit = the split of pending payouts;
//   • the tax-hold "Why is X held?" explainer shows ONLY on a REAL hold (pending payout + no tax ID);
//   • "In transit" is a REAL figure — €0 while held — never a staged/fake movement;
//   • "Withdraw early" is honestly DISABLED (there is NO owner-initiated money-moving route) — never faked;
//   • the masked payout IBAN is CAP_PAYOUT-only and NEVER the full number (#28);
//   • owner isolation holds (another owner's ledger invisible; a non-linked user fails closed);
//   • Export is a REAL owner-scoped, CAP_EXPORTS-gated CSV.
// NOTHING here moves money or fabricates a balance (#39).

/** A linked owner (primary → CAP_PAYOUT) with a Sep statement + payout, optionally PAID, optionally no tax ID. */
function balanceOwner(array $s, int $unitIndex, int $roomMinor, bool $pay = false, bool $noTax = false): array
{
    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(
        ['user_id' => $user->id, 'payout_currency' => 'EUR'] + ($noTax ? ['tax_id' => null] : [])
    );

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
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    $payoutSvc = app(OwnerPayoutService::class);
    $payout = $payoutSvc->createFromStatement($statement);
    if ($pay && $payout !== null) {
        $payout = $payoutSvc->pay($payout);   // manual rail (default) — requires tax details on file
    }

    return compact('user', 'owner', 'unit', 'statement', 'payout');
}

/** A REAL unstamped (statement-less) credit row — this, netted, is exactly what "Available" reports. */
function unstampedCredit(Owner $owner, string $unitId, int $minor): void
{
    OwnerLedgerEntry::factory()->create([
        'owner_id' => $owner->id, 'unit_id' => $unitId,
        'entry_type' => 'adjustment', 'direction' => 'credit', 'amount_minor' => $minor,
        'currency' => 'EUR', 'description' => 'Late credit', 'service_date' => '2026-10-05',
    ]);
}

/** The reconciling clearing identity, computed straight from the source rows (the invariant the page honours). */
function assertClearingReconciles(string $ownerId): void
{
    $rows = OwnerLedgerEntry::where('owner_id', $ownerId)->get();
    $net = $rows->reduce(fn (int $c, OwnerLedgerEntry $e): int => $c + $e->signedMinor(), 0);
    $available = $rows->whereNull('statement_id')->reduce(fn (int $c, OwnerLedgerEntry $e): int => $c + $e->signedMinor(), 0);
    $pending = (int) Payout::where('owner_id', $ownerId)->where('status', 'pending')->sum('amount_minor');
    $paid = (int) Payout::where('owner_id', $ownerId)->where('status', 'paid')->sum('amount_minor');

    // On hold + In transit is the split of $pending, so the whole identity collapses to this:
    expect($available + $pending + $paid)->toBe($net);
}

// ── 1. Reconciling clearing model over the REAL ledger + an honest, money-safe withdrawal ────────────
it('renders the reconciling clearing model over the real ledger and moves no money', function () {
    $s = seedScenario(1);
    $a = balanceOwner($s, 0, 20000, pay: true);          // Sep €170.00 PAID
    unstampedCredit($a['owner'], $a['unit']->id, 2500);  // €25.00 unstamped → Available

    assertClearingReconciles($a['owner']->id);           // 2500 + 0(pending) + 17000(paid) == 19500 (net)

    $response = $this->actingAs($a['user'])->get('/owner/balance')
        ->assertOk()
        ->assertSee('Balance activity')
        ->assertSee('Available')
        ->assertSee('In transit')
        ->assertSee(MoneyFormatter::display(2500, 'EUR'))       // €25.00 — Available == unstamped-ledger net
        // REAL rows, described by the certified attribution engine itself
        ->assertSee('Room revenue · 2 night(s)')
        ->assertSee('Management commission 15%')
        ->assertSee('+€200.00')                                // signed credit
        ->assertSee('−€30.00')                                 // signed debit (U+2212, per MoneyFormatter)
        ->assertSee('Monthly')                                 // REAL agreement payout_schedule
        ->assertSee('How money clears')
        // ⚠️ MONEY-SAFETY: the withdraw control is honestly gated, disabled, and NOT a fake movement
        ->assertSee('Instant self-serve withdrawal activates')
        ->assertSee('aria-disabled="true"', false)
        // no fabricated payout account (no PayoutMethod on file for this owner)
        ->assertDontSee('IBAN');

    // No owner-initiated money-moving route exists — the withdrawal cannot fire even in principle.
    expect(Route::has('owner.balance.withdraw'))->toBeFalse();

    assertNoRawBlade($response);
});

// ── 2. Owner isolation — A sees only A's activity; a non-linked user fails closed ────────────────────
it('scopes the balance to the owner, hides other owners, and fails closed for a non-linked user', function () {
    $s = seedScenario(2);
    $a = balanceOwner($s, 0, 20000, pay: true);   // €170.00, room revenue +€200.00
    balanceOwner($s, 1, 50000, pay: true);        // €425.00, room revenue +€500.00 (distinctive)

    $this->actingAs($a['user'])->get('/owner/balance')
        ->assertOk()
        ->assertSee('+€200.00')
        ->assertDontSee('+€500.00');   // owner B's room-revenue row

    // A non-linked authenticated member resolves to an EMPTY owner scope → nothing, never all owners.
    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    // ⚠️ R-GATE (founder, 2026-09-19): this asserted the portal RENDERS EMPTY for a non-owner — the
    //    behaviour F-23 recorded as the defect. The refusal is now the intended answer: a non-owner is
    //    turned away at the realm door instead of being shown an owner's portal that is not theirs.
    $this->actingAs($stranger)->get('/owner/balance')->assertForbidden();
});

// ── 3. REAL per-row flags: prior-period badge, an honestly unstatemented row, exact running balance ──
it('badges a prior-period adjustment, marks an unstatemented row, and lands the running balance exactly', function () {
    $s = seedScenario(1);
    $a = balanceOwner($s, 0, 20000, pay: true);   // 3 rows, all claimed by the finalized September statement

    // A REAL later row dated AFTER the sealed September period → genuinely unclaimed (statement_id null).
    OwnerLedgerEntry::factory()->create([
        'owner_id' => $a['owner']->id, 'unit_id' => $a['unit']->id,
        'entry_type' => 'adjustment', 'direction' => 'debit', 'amount_minor' => 2500,
        'currency' => 'EUR', 'description' => 'Late supplier credit',
        'service_date' => '2026-10-05', 'prior_period_adjustment' => true,
    ]);

    $netMinor = ownerNet($a['owner']->id);
    expect($netMinor)->toBe(14500);   // 17000 − 2500 — the newest running balance must land exactly here

    $this->actingAs($a['user'])->get('/owner/balance')
        ->assertOk()
        ->assertSee('Late supplier credit')
        ->assertSee('Prior-period adjustment')
        ->assertSee('not yet on a statement')                    // the inline activity subline label
        ->assertSee('−€25.00')
        ->assertSee(MoneyFormatter::display($netMinor, 'EUR'));   // €145.00 — newest running balance
});

// ── 4. The tax-hold explainer is REAL-only; "In transit" is a real figure (€0 held, the payout released) ─
it('shows the tax-hold explainer only on a real hold, and In transit only once released', function () {
    // HELD — pending payout + NO tax details → On hold == the payout, In transit == €0, the "Why held?" card.
    $s = seedScenario(1);
    $held = balanceOwner($s, 0, 20000, pay: false, noTax: true);

    $response = $this->actingAs($held['user'])->get('/owner/balance')
        ->assertOk()
        ->assertSee('Why is €170.00 held?')
        ->assertSee('Add tax details')
        ->assertSee('On hold')
        // ⚠️ nothing is "in transit" while the payout is held — a real €0, never a staged movement.
        ->assertSeeInOrder(['In transit', '€0.00']);

    assertNoRawBlade($response);

    // RELEASED — pending payout + tax details on file → In transit == the payout, NO hold explainer.
    $s2 = seedScenario(1);
    $ok = balanceOwner($s2, 0, 20000, pay: false);   // factory tax id → released

    $this->actingAs($ok['user'])->get('/owner/balance')
        ->assertOk()
        ->assertDontSee('Why is')          // no hold explainer
        ->assertDontSee('Add tax details')
        ->assertSee('In transit');

    // the released pending payout IS the real In-transit figure (not fabricated, not always-zero).
    expect((int) Payout::where('owner_id', $ok['owner']->id)->where('status', 'pending')->sum('amount_minor'))->toBe(17000);
});

// ── 5. The masked payout IBAN shows ONLY to a payout-capable role (CAP_PAYOUT), never the full number ─
it('shows the masked payout IBAN only to a payout-capable role', function () {
    $s = seedScenario(2);
    $a = balanceOwner($s, 0, 20000, pay: false);   // pending → a payout rail is in play

    PayoutMethod::create([
        'organization_id' => $s['org']->id, 'owner_id' => $a['owner']->id,
        'label' => 'Revolut Business', 'account_holder' => 'Amara Okafor',
        'iban' => 'LT121000011101001000', 'iban_last4' => '1000', 'iban_country' => 'LT',
        'currency' => 'EUR', 'is_default' => true,
    ]);

    // The PRIMARY owner (co-owner, CAP_PAYOUT) sees the masked IBAN — never the full number.
    $this->actingAs($a['user'])->get('/owner/balance')
        ->assertOk()
        ->assertSee('···· 1000')
        ->assertDontSee('LT121000011101001000');

    // An accountant may VIEW the page but has no CAP_PAYOUT → no account number (#28).
    $this->actingAs(ownerSubUser($s, $a['owner']->id, 'accountant'))->get('/owner/balance')
        ->assertOk()
        ->assertDontSee('···· 1000')
        ->assertDontSee('LT121000011101001000');
});

// ── 6. Balance-activity CSV — owner-scoped, EXPORTS-gated, never another owner's rows ────────────────
it('exports the balance-activity CSV owner-scoped and EXPORTS-gated', function () {
    $s = seedScenario(2);
    $a = balanceOwner($s, 0, 20000, pay: true);   // A: room revenue +€200.00
    balanceOwner($s, 1, 50000, pay: true);        // B: room revenue +€500.00 (distinctive)

    $csv = $this->actingAs($a['user'])->get(route('owner.balance.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=utf-8')
        ->streamedContent();

    expect($csv)->toContain('Running balance')     // header
        ->toContain('+€200.00')                    // A's real row (signed)
        ->not->toContain('+€500.00');              // never B's

    // EXPORTS capability: accountant may download; view-only may NOT.
    $this->actingAs(ownerSubUser($s, $a['owner']->id, 'accountant'))->get(route('owner.balance.export'))->assertOk();
    $this->actingAs(ownerSubUser($s, $a['owner']->id, 'view_only'))->get(route('owner.balance.export'))->assertForbidden();
});
