<?php

use App\Exceptions\Owner\PayoutHeldException;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Services\OwnerTaxService;
use App\Support\Pdf\OwnerAnnualTaxDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// OWNER TAX CENTER + ANNUAL TAX PDF (Phase 2 · Sub-gate 4).
//   • the tax ID is SELF-ONLY and ENCRYPTED at rest (assert ciphertext);
//   • the payout HOLD is GENUINE — a pending payout to a tax-incomplete owner is refused at the money path
//     (OwnerPayoutService::pay), and RELEASES once a tax ID is saved (proven, not faked);
//   • the annual summary + PDF aggregate the owner's REAL committed statements and reconcile to the cent;
//   • self-only / owner-scoped — a non-linked user 404s; one owner's save never touches another's.

/**
 * A linked owner-portal user with a REAL finalized statement + a pending payout, built through the certified
 * services (attributed booking → ledger → statement → payout). `withTaxId=false` leaves the payout HELD.
 *
 * @return array{owner: Owner, user: User, statement: OwnerStatement, payout: Payout}
 */
if (! function_exists('taxOwnerFull')) {
    function taxOwnerFull(array $s, int $unitIndex, int $roomMinor, string $name, bool $withTaxId): array
    {
        $owner = Owner::factory()->create(['name' => $name, 'payout_currency' => 'EUR', 'status' => 'active']);
        if (! $withTaxId) {
            $owner->update(['tax_id' => null]);   // the HELD state — no tax ID on file
        }

        $unit = $s['units'][$unitIndex];
        $unit->update(['owner_id' => $owner->id]);
        ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01', 'effective_to' => null]);

        $reservation = book($s, ['unit_id' => $unit->id]);
        postRoom($reservation, $roomMinor);
        app(OwnerAttributionService::class)->attributeReservation($reservation);
        collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

        $svc = app(OwnerStatementService::class);
        $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));
        $payout = app(OwnerPayoutService::class)->createFromStatement($statement);

        $user = User::factory()->create(['name' => $name]);
        Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
        $owner->update(['user_id' => $user->id]);

        return compact('owner', 'user', 'statement', 'payout');
    }
}

// ── 1. The tax ID saves, ENCRYPTED at rest, self-only ─────────────────────────────────────────────
it('saves the tax id encrypted at rest', function () {
    $s = seedScenario(1);
    $f = taxOwnerFull($s, 0, 20000, 'Amara Okafor', false);

    $this->actingAs($f['user'])->post('/owner/tax', [
        'type' => 'individual', 'legal_name' => 'Amara Okafor', 'tax_country' => 'Portugal', 'tax_id' => '501234567',
    ])->assertRedirect(route('owner.tax'))->assertSessionHas('tax_notice');

    // Decrypts back to what was typed…
    expect((string) $f['owner']->fresh()->tax_id)->toBe('501234567');
    // …but the RAW column is ciphertext, never the plaintext.
    $raw = (string) DB::table('owners')->where('id', $f['owner']->id)->value('tax_id');
    expect($raw)->not->toBe('501234567')->and($raw)->not->toContain('501234567');
});

// ── 2. THE GENUINE HOLD — a payout is refused until tax details exist, then RELEASES ──────────────
it('holds a payout until a tax id is on file, then releases it', function () {
    $s = seedScenario(1);
    $f = taxOwnerFull($s, 0, 20000, 'Amara Okafor', false);   // no tax id → held

    // The money path itself refuses to disburse — a genuine hold, not a label.
    expect(fn () => app(OwnerPayoutService::class)->pay($f['payout']))->toThrow(PayoutHeldException::class);
    expect($f['payout']->fresh()->status)->toBe('pending');   // untouched, still owed

    // The owner adds a tax ID…
    $this->actingAs($f['user'])->post('/owner/tax', ['type' => 'individual', 'tax_id' => '501234567'])->assertRedirect();

    // …and the SAME payout can now be paid — released, for real.
    $paid = app(OwnerPayoutService::class)->pay($f['payout']->fresh());
    expect($paid->status)->toBe('paid');
});

// ── 3. The Tax Center shows the REAL held amount ─────────────────────────────────────────────────
it('shows the real held-payout amount on the screen', function () {
    $s = seedScenario(1);
    $f = taxOwnerFull($s, 0, 20000, 'Amara Okafor', false);   // net €170.00 held

    $this->actingAs($f['user'])->get('/owner/tax')
        ->assertOk()
        ->assertSee('release held payouts')
        ->assertSee('€170.00');   // the real pending-payout amount, held
});

// ── 4. The annual summary reconciles + the PDF is self-only ───────────────────────────────────────
it('renders a reconciling annual summary and a self-only PDF', function () {
    $s = seedScenario(1);
    $f = taxOwnerFull($s, 0, 20000, 'Amara Okafor', true);

    // Reconciles to the cent: gross + deductions = net, and net = the statement's net.
    $summary = app(OwnerTaxService::class)->annualSummary($f['owner'], 2026);
    expect($summary['gross_minor'])->toBe(20000)
        ->and($summary['deductions_minor'])->toBe(-3000)          // 15% commission
        ->and($summary['gross_minor'] + $summary['deductions_minor'])->toBe($summary['net_minor'])
        ->and($summary['net_minor'])->toBe(17000);

    // The owner downloads their OWN summary PDF…
    $pdf = $this->actingAs($f['user'])->get('/owner/tax/2026/summary.pdf');
    $pdf->assertOk();
    expect($pdf->headers->get('content-type'))->toContain('application/pdf');

    // …a year with no statements 404s; a non-linked user fails closed.
    $this->actingAs($f['user'])->get('/owner/tax/2019/summary.pdf')->assertNotFound();

    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);
    $this->actingAs($stranger)->get('/owner/tax/2026/summary.pdf')->assertNotFound();   // no linked owner
    $this->actingAs($stranger)->get('/owner/tax')->assertOk()->assertSee('No owner account is linked');
});

// ── 5. SELF-ONLY — one owner's save never touches another's ───────────────────────────────────────
it('never lets one owner’s save affect another owner', function () {
    $s = seedScenario(2);
    $a = taxOwnerFull($s, 0, 20000, 'Amara Okafor', false);
    $b = taxOwnerFull($s, 1, 20000, 'Bruno Vale', false);

    $this->actingAs($a['user'])->post('/owner/tax', ['type' => 'individual', 'tax_id' => 'AAA111']);

    // A is complete; B is still untouched (no owner id in the request → the write can only ever be A's own).
    expect($a['owner']->fresh()->hasTaxDetails())->toBeTrue()
        ->and($b['owner']->fresh()->hasTaxDetails())->toBeFalse()
        ->and($b['owner']->fresh()->tax_id)->toBeNull();
});

// ── 6. The VAT-registered FLAG round-trips through the SHARED store; de-registering drops the number ──
it('stores the VAT-registered flag through the shared tax store and clears the number when off', function () {
    $s = seedScenario(1);
    $f = taxOwnerFull($s, 0, 20000, 'Amara Okafor', false);

    // Register for VAT with a number (the same owner.tax.save path the whole app uses).
    $this->actingAs($f['user'])->post('/owner/tax', [
        'type' => 'company', 'tax_id' => '501234567', 'vat_registered' => '1', 'vat_number' => 'PT501234567',
    ])->assertRedirect(route('owner.tax'));
    $owner = $f['owner']->fresh();
    expect($owner->vat_registered)->toBeTrue()
        ->and((string) $owner->vat_number)->toBe('PT501234567');
    // The VAT number is encrypted at rest, like the tax ID (#28).
    $rawVat = (string) DB::table('owners')->where('id', $owner->id)->value('vat_number');
    expect($rawVat)->not->toContain('PT501234567');

    // De-register (the toggle off submits vat_registered=0): the flag goes false AND the number is dropped —
    // the flag is the source of truth, so no stale VAT number lingers on the letterhead.
    $this->actingAs($f['user'])->post('/owner/tax', [
        'type' => 'company', 'tax_id' => '501234567', 'vat_registered' => '0', 'vat_number' => 'PT501234567',
    ])->assertRedirect();
    $owner = $f['owner']->fresh();
    expect($owner->vat_registered)->toBeFalse()
        ->and($owner->vat_number)->toBeNull();
});

// ── 7. WITHHOLDING is HONEST — the wireframe's residency-driven rates are NOT modelled → never fabricated ──
it('renders an honest withholding state and never a fabricated rate', function () {
    $s = seedScenario(1);
    $f = taxOwnerFull($s, 0, 20000, 'Amara Okafor', true);   // complete → the green state, no hold

    $res = $this->actingAs($f['user'])->get('/owner/tax')->assertOk()
        // the green "complete" state is REAL (tax details on file)
        ->assertSee('Tax details complete')
        // the withholding section is HONEST — no rate configured for this owner → a genuine "None"
        ->assertSee('Withholding tax')
        ->assertSee('None')
        ->assertSee('No withholding is configured')
        ->assertSee('full net earnings')
        // ⚠️ NEVER the wireframe's fabricated residency-driven withholding (#39)
        ->assertDontSee('treaty rate')
        ->assertDontSee('withheld on payouts')
        ->assertDontSee('non-resident rate');

    assertNoRawBlade($res);
});

/** The document's own view data, from an annualSummary()-shaped result — the shape OwnerAnnualTaxDocument takes. */
function annualTaxHtml(array $categories, array $months = []): string
{
    $owner = new Owner(['name' => 'Amara Okafor', 'legal_name' => 'Amara Okafor', 'tax_id' => '', 'tax_country' => '']);
    $summary = [
        'currency' => 'EUR', 'year' => 2026, 'period_start' => '1 Jan 2026', 'period_end' => '31 Dec 2026',
        'statement_count' => 1, 'gross_minor' => 100000, 'deductions_minor' => -25000, 'net_minor' => 75000,
        'categories' => $categories,
        'months' => $months !== [] ? $months : [['label' => 'January', 'gross_minor' => 100000, 'commission_minor' => -18000, 'fees_minor' => -7000, 'net_minor' => 75000]],
    ];

    return view('pdfs.owner-annual-tax', OwnerAnnualTaxDocument::data($owner, $summary))->render();
}

// ── WITHHOLDING reaches the tax summary — real when posted, silent when genuinely zero ────────────
// Owner-22 found BOTH docblocks on this document still claiming "no withholding (we don't compute it)".
// That went stale when the tax engine (#73) shipped: annualSummary() emits a real `withholding_minor`
// bucket and a "Withholding tax" category, so it flows into the table like any other deduction. The
// stale comment pointed the dangerous way — a reader who believed it might add a SECOND withholding
// line and double-count a real payout deduction on a tax document. Both directions are asserted so the
// claim can never drift again, and so a genuinely-zero year can never print an invented €0.00 row.
it('states withholding tax when it is real and stays silent when it is zero', function () {
    $withReal = annualTaxHtml([
        ['label' => 'Management commission', 'minor' => -18000],
        ['label' => 'Withholding tax', 'minor' => -7000],
    ]);
    expect($withReal)->toContain('Withholding tax')
        ->and($withReal)->toContain('70.00');

    $withZero = annualTaxHtml([
        ['label' => 'Management commission', 'minor' => -18000],
        ['label' => 'Withholding tax', 'minor' => 0],
    ]);
    expect($withZero)->not->toContain('Withholding tax');
});

// ── The document's ink stays post-AA — a tax summary is read on WHITE PAPER, often by an accountant ─
// Measured on the GENERATED pdf, as Owner-20/21 did: the same pre-AA pair, and the WORST spread of the
// three — #8A8079 across 27 text runs at 3.86, including `.neg`, which is the DEDUCTION FIGURES
// themselves, and #A69A92 at 2.74 on the footer, which is where the "not tax advice" framing lives.
// The legal framing was the least legible text on the document.
it('keeps the annual tax document free of the pre-AA ink', function () {
    $html = annualTaxHtml([['label' => 'Management commission', 'minor' => -18000]]);

    expect($html)->not->toContain('#8A8079')
        ->and($html)->not->toContain('#A69A92');
});
