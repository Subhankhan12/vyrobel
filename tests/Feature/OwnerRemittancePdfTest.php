<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerStatementLine;
use App\Models\Payout;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Support\Money\MoneyFormatter;
use App\Support\Pdf\OwnerRemittanceDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Wave 1 · Sub-gate 2 — the owner REMITTANCE ADVICE PDF (one payout). Real Payout + owner_statement_lines
// figures; reconciles to the payout/statement to the cent; the design's fiction (IBAN / "···· 4821" /
// "Scheduled" / SEPA / RA-2025- / OS-2025- / "est." dates) is never invented; OWNER-scoped download
// (cross-owner 404, non-linked user fails closed).

/** A linked owner with a finalized statement + its payout (paid through the manual rail when $pay). */
function remittanceOwner(array $s, int $unitIndex, int $roomMinor, bool $pay = false): array
{
    // Deterministic letterhead + beneficiary. The honesty assertions below scan the rendered document for
    // substrings that a random faker name (or newDemoOrg's 'Org <random>') could otherwise collide with.
    $s['org']->update(['name' => 'Coastal Stays Operator']);

    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'name' => 'Amara Okafor', 'payout_currency' => 'EUR']);

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

/** Render the remittance document to HTML — dompdf compresses its object streams, so (exactly as
 *  OwnerStatementPdfTest does) the figures are asserted against the document source the PDF is built from. */
function remittanceHtml(Payout $payout): string
{
    return view('pdfs.owner-remittance', OwnerRemittanceDocument::data($payout))->render();
}

// ── The owner downloads their remittance advice (application/pdf, filename, real %PDF bytes) ─────
it('streams a real PDF to the owner', function () {
    $s = seedScenario(2);
    $a = remittanceOwner($s, 0, 20000, pay: true);

    $response = $this->actingAs($a['user'])->get("/owner/payouts/{$a['payout']->id}/remittance");

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('attachment')
        ->and($response->headers->get('content-disposition'))->toContain('vyrobel-remittance-')
        ->and(str_starts_with((string) $response->getContent(), '%PDF'))->toBeTrue();
});

// ── RECONCILIATION (the headline): payout.amount === statement.net_payable, components sum to net ─
it('reconciles to the cent — the payout is the statement net, and the lines sum to it', function () {
    $s = seedScenario(2);
    $a = remittanceOwner($s, 0, 20000, pay: true);
    $payout = $a['payout'];
    $statement = $a['statement']->refresh();

    // 1. The identity OwnerPayoutService::createFromStatement guarantees — exact minor integers.
    expect((int) $payout->amount_minor)->toBe((int) $statement->net_payable_minor)
        ->and((string) $payout->currency)->toBe((string) $statement->currency);

    // 2. The statement's component lines sum EXACTLY to its net LINE (both in source_currency — never a
    //    cross-currency sum), and that net line is the statement's net_source_minor.
    $componentSum = (int) OwnerStatementLine::query()
        ->where('owner_statement_id', $statement->id)
        ->where('line_type', '!=', 'net')
        ->sum('amount_minor');
    $netLine = OwnerStatementLine::query()
        ->where('owner_statement_id', $statement->id)
        ->where('line_type', 'net')
        ->firstOrFail();

    expect($componentSum)->toBe((int) $netLine->amount_minor)
        ->and($componentSum)->toBe((int) $statement->net_source_minor)
        ->and((string) $netLine->currency)->toBe((string) $statement->source_currency);

    // 3. No FX in this fixture → the source net IS the payable, so the whole chain closes on one figure.
    expect((int) $statement->net_source_minor)->toBe((int) $statement->net_payable_minor)
        ->and((int) $payout->amount_minor)->toBe(17000);   // €200.00 gross − 15% commission

    // 4. That exact figure — formatted by the SHARED MoneyFormatter, so it is byte-identical to the
    //    on-screen payout row — appears in the document the PDF is rendered from.
    $data = OwnerRemittanceDocument::data($payout);
    expect($data['amount_display'])->toBe(MoneyFormatter::display(17000, 'EUR'))
        ->and(remittanceHtml($payout))->toContain(MoneyFormatter::display(17000, 'EUR'))
        // and every real line renders (components + the net line).
        ->and(count($data['lines']))->toBe(OwnerStatementLine::query()->where('owner_statement_id', $statement->id)->count());
});

// ── HONESTY: the design's fabrications have no backend and never appear ──────────────────────────
it('invents no IBAN, settlement estimate, Scheduled status, SEPA claim or RA-/OS- reference', function () {
    $s = seedScenario(2);
    $a = remittanceOwner($s, 0, 20000, pay: true);

    $html = remittanceHtml($a['payout']);

    expect($html)->toContain('Amara Okafor')                      // the REAL beneficiary
        ->and($html)->toContain('Bank transfer')                  // rail=manual → no "SEPA" claim
        ->and($html)->toContain('Gross room revenue')             // the REAL statement lines
        // ⚠️ every fabricated element the design implies:
        ->and($html)->not->toContain('IBAN')
        ->and($html)->not->toContain('4821')
        ->and($html)->not->toContain('Revolut')
        ->and($html)->not->toContain('Scheduled')                 // NOT a real payout status
        ->and($html)->not->toContain('SEPA')
        ->and($html)->not->toContain('RA-2025')
        ->and($html)->not->toContain('OS-2025')
        ->and($html)->not->toContain('est.')                      // no estimated settlement date
        ->and($html)->not->toContain('Seaside Villas')            // no hard-coded operator fiction
        ->and($html)->not->toContain('VAT')
        ->and($html)->not->toContain('Av. da Marina');
});

// ── Guest PII: an owner surface never shows a guest name (locked precedent) ──────────────────────
it('shows no guest name on the owner remittance', function () {
    $s = seedScenario(2);
    $s['guest']->update(['first_name' => 'Zoltan', 'last_name' => 'Quibberdink']);
    $a = remittanceOwner($s, 0, 20000, pay: true);

    expect(remittanceHtml($a['payout']))->not->toContain('Zoltan')->not->toContain('Quibberdink');
});

// ── A real settlement date ONLY once paid — a pending payout states no date at all ───────────────
it('renders Paid on for a paid payout and no payment date at all for a pending one', function () {
    $s = seedScenario(2);

    $paid = remittanceOwner($s, 0, 20000, pay: true);
    $paidHtml = remittanceHtml($paid['payout']);
    expect($paidHtml)->toContain('Paid on')
        ->and($paidHtml)->toContain($paid['payout']->paid_at->format('j M Y'))
        ->and($paidHtml)->toContain('Net paid to owner')
        ->and($paidHtml)->toContain('Paid');

    // A PENDING payout has no paid_at — so the document carries no payment date and never claims payment.
    $pending = remittanceOwner($s, 1, 30000, pay: false);
    expect($pending['payout']->status)->toBe('pending')
        ->and($pending['payout']->paid_at)->toBeNull();

    $pendingHtml = remittanceHtml($pending['payout']);
    expect($pendingHtml)->not->toContain('Paid on')
        ->and($pendingHtml)->toContain('Pending')
        ->and($pendingHtml)->toContain('Net payable to owner')   // never "paid" before it is paid
        ->and($pendingHtml)->not->toContain('Net paid to owner');
});

// ── The Reference row appears ONLY when the operator genuinely attached a bank ref ───────────────
it('omits the reference row unless external_ref is really set', function () {
    $s = seedScenario(2);
    $a = remittanceOwner($s, 0, 20000, pay: true);

    // The manual rail generates NO external reference — so the row is absent, not faked.
    expect($a['payout']->external_ref)->toBeNull()
        ->and(remittanceHtml($a['payout']))->not->toContain('Reference');

    // Once the operator attaches their real bank reference, it renders verbatim.
    $a['payout']->update(['external_ref' => 'BANKREF-77Q']);
    $html = remittanceHtml($a['payout']->refresh());
    expect($html)->toContain('Reference')->toContain('BANKREF-77Q');
});

// ── PDF-SAFE: dompdf-compatible document (bundled Unicode font, no frosted glass / flex) ─────────
it('renders a PDF-safe document', function () {
    $s = seedScenario(2);
    $a = remittanceOwner($s, 0, 20000, pay: true);

    $html = remittanceHtml($a['payout']);

    expect($html)->toContain('DejaVu Sans')          // renders € and the U+2212 minus
        ->and($html)->not->toContain('backdrop-filter')
        ->and($html)->not->toContain('display:flex')
        ->and($html)->not->toContain('display: flex')
        ->and($html)->not->toContain('display:grid');
});

// ── OWNER ISOLATION: an owner cannot download another owner's remittance (cross-owner 404) ───────
it('404s a cross-owner remittance PDF', function () {
    $s = seedScenario(2);
    $a = remittanceOwner($s, 0, 20000, pay: true);
    $b = remittanceOwner($s, 1, 30000, pay: true);

    // Owner B asking for owner A's remittance → 404 (owner-scoped, no leak).
    $this->actingAs($b['user'])->get("/owner/payouts/{$a['payout']->id}/remittance")->assertNotFound();
    // Owner A asking for owner B's → 404 in the other direction too.
    $this->actingAs($a['user'])->get("/owner/payouts/{$b['payout']->id}/remittance")->assertNotFound();
    // Each can download their own.
    $this->actingAs($a['user'])->get("/owner/payouts/{$a['payout']->id}/remittance")->assertOk();
    $this->actingAs($b['user'])->get("/owner/payouts/{$b['payout']->id}/remittance")->assertOk();
});

// ── Fail closed: a non-linked authenticated member (Membership, no Owner) downloads nothing ──────
it('fails closed for a non-linked user', function () {
    $s = seedScenario(2);
    $a = remittanceOwner($s, 0, 20000, pay: true);

    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    $this->actingAs($stranger)->get("/owner/payouts/{$a['payout']->id}/remittance")->assertNotFound();
});

// ── The payouts screen links the REAL remittance (the "Receipt · Soon" stub is gone) ─────────────
it('links the real remittance from the payouts screen', function () {
    $s = seedScenario(2);
    $a = remittanceOwner($s, 0, 20000, pay: true);

    $response = $this->actingAs($a['user'])->get('/owner/payouts');

    $response->assertOk()
        ->assertSee('Remittance')
        ->assertSee(route('owner.payouts.remittance', $a['payout']->id), false)
        ->assertDontSee('Receipt · Soon');

    assertNoRawBlade($response);

    // The link target is the owner's OWN payout — and it really streams.
    expect((string) Payout::query()->whereKey($a['payout']->id)->value('owner_id'))->toBe((string) $a['owner']->id);
});

// ── Exact-match (#81) — the VAT letterhead + masked IBAN + RA reference render when the data is REAL ──
it('renders the operator VAT letterhead, the masked IBAN and the RA reference — reconciling', function () {
    $s = seedScenario(1);
    // Real operator identity on the letterhead (shown only when set).
    $s['org']->update(['billing_company' => 'Coastal Stays Lda', 'billing_vat_id' => 'PT512034776', 'country' => 'PT']);
    $r = remittanceOwner($s, 0, 60000, pay: true);   // €600 room → statement + paid payout

    // The masked IBAN is passed in CAP_PAYOUT-gated by the controller — here we prove the DOCUMENT renders it.
    $html = view('pdfs.owner-remittance', OwnerRemittanceDocument::data($r['payout'], '···· 4821'))->render();

    expect($html)->toContain('Coastal Stays Lda')      // real billing company
        ->toContain('VAT PT512034776')                 // real VAT (letterhead)
        ->toContain('IBAN ···· 4821')                   // the masked IBAN (never the full number)
        ->toContain('RA-2026-09')                       // the period-derived remittance ref
        ->toContain('Payment details');                 // the payment-details section

    // #28 — with no CAP_PAYOUT (null iban), the IBAN row is OMITTED entirely.
    $noIban = view('pdfs.owner-remittance', OwnerRemittanceDocument::data($r['payout'], null))->render();
    expect($noIban)->not->toContain('IBAN');

    // #17 — the breakdown still reconciles: the net line equals the payout amount.
    $netMinor = (int) OwnerStatementLine::query()->where('owner_statement_id', $r['statement']->id)->where('line_type', 'net')->value('amount_minor');
    expect($html)->toContain(MoneyFormatter::display($netMinor, 'EUR'))
        ->and((int) $r['payout']->amount_minor)->toBe($netMinor);
});
