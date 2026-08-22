<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerStatementLine;
use App\Models\PayoutMethod;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Support\Money\MoneyFormatter;
use App\Support\Pdf\OwnerStatementDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Demo-readiness · Part B · Step B1 — branded owner-statement PDF. Real owner_ledger figures; reconciles
// to the on-screen statement to the cent; NO invented lines; OWNER-scoped download (cross-owner 404).

/** A linked owner with a finalized statement (mirrors the owner-portal test setup). */
function pdfStatementOwner(array $s, int $unitIndex, int $roomMinor): array
{
    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);

    $unit = $s['units'][$unitIndex];
    $unit->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);

    $reservation = book($s, ['unit_id' => $unit->id]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));
    app(OwnerPayoutService::class)->createFromStatement($statement);

    return compact('user', 'owner', 'unit', 'statement');
}

/** A linked owner who owns TWO units, both attributed into ONE finalized statement — for the by-unit table. */
function twoUnitPdfStatement(array $s): array
{
    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 18.00, 'effective_from' => '2026-01-01']);

    foreach ([[0, 20000], [1, 15000]] as [$idx, $roomMinor]) {
        $unit = $s['units'][$idx];
        $unit->update(['owner_id' => $owner->id]);
        $reservation = book($s, ['unit_id' => $unit->id]);
        postRoom($reservation, $roomMinor);
        app(OwnerAttributionService::class)->attributeReservation($reservation);
        collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)
    }

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    return compact('user', 'owner', 'statement');
}

// ── The owner downloads their statement PDF (application/pdf, filename, real %PDF bytes) ──────────
it('streams a real PDF to the owner', function () {
    $s = seedScenario(2);
    $a = pdfStatementOwner($s, 0, 20000);

    $response = $this->actingAs($a['user'])->get("/owner/statements/{$a['statement']->id}/pdf");

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('attachment')
        ->and($response->headers->get('content-disposition'))->toContain('vyrobel-statement-')
        ->and(str_starts_with((string) $response->getContent(), '%PDF'))->toBeTrue();
});

// ── The PDF data RECONCILES to the on-screen statement, to the cent ──────────────────────────────
it('reconciles to the cent — the component lines sum to the net payable', function () {
    $s = seedScenario(2);
    $a = pdfStatementOwner($s, 0, 20000);
    $statement = $a['statement'];

    $data = OwnerStatementDocument::data($statement);

    // The document's net string is byte-identical to the on-screen figure (shared MoneyFormatter).
    expect($data['net_display'])->toBe(MoneyFormatter::display((int) $statement->net_payable_minor, (string) $statement->currency));

    // The real component lines (everything but the net line) sum EXACTLY to the net payable.
    $componentSum = (int) OwnerStatementLine::query()
        ->where('owner_statement_id', $statement->id)
        ->where('line_type', '!=', 'net')
        ->sum('amount_minor');
    expect($componentSum)->toBe((int) $statement->net_payable_minor)
        ->and(count($data['lines']))->toBe(OwnerStatementLine::query()->where('owner_statement_id', $statement->id)->count());
});

// ── NO invented lines: the fake "card-processing fee" never appears ──────────────────────────────
it('renders only real ledger lines — no invented card-processing fee', function () {
    $s = seedScenario(2);
    $a = pdfStatementOwner($s, 0, 20000);

    $html = view('pdfs.owner-statement', OwnerStatementDocument::data($a['statement']))->render();

    // ⚠️ e() — THE ESCAPED name, because that is what Blade actually renders. This was THE FLAKY TEST
    // (register item 6, root-caused 2026-07-17 after it blinked once in six full runs): `OwnerFactory` uses
    // `faker->name()`, **1.68% of which contain an apostrophe** ("Abelardo O'Conner"), and Blade escapes it
    // to `O&#039;Conner`. So this assertion failed ~1 run in 6, on a page that was perfectly correct —
    // testing the needle rather than the behaviour. The PAGE was never wrong; the comparison was.
    expect($html)->toContain(e($a['owner']->name))
        ->and($html)->toContain(MoneyFormatter::display((int) $a['statement']->net_payable_minor, 'EUR'))
        ->and(strtolower($html))->not->toContain('card-processing')
        ->and(strtolower($html))->not->toContain('processing fee')
        // PDF-SAFE: no frosted-glass / flex-dependent layout in the document.
        ->and($html)->not->toContain('backdrop-filter')
        ->and($html)->not->toContain('display:flex')
        ->and($html)->not->toContain('display: flex');
});

// ── GATE 2: REAL occupancy/nights/ADR + a by-unit table that RECONCILES to the statement gross ────
it('shows real occupancy/nights/ADR and a by-unit table reconciling to the statement gross', function () {
    $s = seedScenario(2);
    $a = twoUnitPdfStatement($s);       // one owner, two units, both in one finalized statement
    $data = OwnerStatementDocument::data($a['statement']);

    // #17 — Σ per-unit gross == the statement gross; the Total-gross row echoes it to the character.
    $unitGross = array_sum(array_map(fn (array $u): int => $u['gross_minor'], $data['units']));
    expect($unitGross)->toBe((int) $a['statement']->gross_minor)
        ->and($data['unit_total']['gross_display'])->toBe(MoneyFormatter::display((int) $a['statement']->gross_minor, 'EUR'));

    // Real stats (present + non-negative); ADR is gross ÷ nights, so it is 0 only when nights is 0.
    expect($data['nights'])->toBeGreaterThan(0)
        ->and($data['occupancy_pct'])->toBeGreaterThanOrEqual(0)
        ->and(count($data['units']))->toBe(2);

    // The rendered document shows the by-unit heading + the real total (no guest data anywhere, #28).
    $html = view('pdfs.owner-statement', $data)->render();
    expect($html)->toContain('Performance by unit')
        ->and($html)->toContain('Total gross room revenue');
});

// ── GATE 2: the masked payout IBAN appears ONLY for a payout-capable exporter (CAP_PAYOUT), #28 ────
it('shows the masked payout IBAN on the PDF only for a payout-capable exporter', function () {
    $s = seedScenario(2);
    $a = pdfStatementOwner($s, 0, 20000);

    PayoutMethod::create([
        'organization_id' => $s['org']->id,
        'owner_id' => $a['owner']->id,
        'account_holder' => 'Owner',
        'iban' => 'GB29NWBK60161331926819',
        'iban_last4' => '6819',
        'iban_country' => 'GB',
        'currency' => 'EUR',
        'is_default' => true,
    ]);

    // Payout-capable (co-owner) → the masked IBAN renders; a non-payout export (e.g. accountant) omits it.
    $withPayout = view('pdfs.owner-statement', OwnerStatementDocument::data($a['statement'], true))->render();
    $withoutPayout = view('pdfs.owner-statement', OwnerStatementDocument::data($a['statement'], false))->render();

    expect($withPayout)->toContain('···· 6819')
        ->and($withPayout)->not->toContain('GB29NWBK60161331926819')   // never the full number
        ->and($withoutPayout)->not->toContain('···· 6819');
});

// ── GATE 3: the owner's REAL registered address renders as the "Prepared for" block, only when set ─
it('renders the owner registered address on the statement letterhead only when set', function () {
    $s = seedScenario(2);
    $a = pdfStatementOwner($s, 0, 20000);

    // With no address on file, no recipient block + no fabricated line (#39).
    $bare = view('pdfs.owner-statement', OwnerStatementDocument::data($a['statement']))->render();
    expect($bare)->not->toContain('Prepared for');

    // Set a real address → it renders as printable lines under "Prepared for".
    $a['owner']->update(['address' => [
        'street' => '12 Rua da Liberdade', 'city' => 'Lisbon', 'postcode' => '1250-096', 'country' => 'Portugal',
    ]]);

    $data = OwnerStatementDocument::data($a['statement']->fresh());
    expect($data['owner_address_lines'])->toBe(['12 Rua da Liberdade', '1250-096 Lisbon', 'Portugal']);

    $html = view('pdfs.owner-statement', $data)->render();
    expect($html)->toContain('Prepared for')
        ->and($html)->toContain('12 Rua da Liberdade')
        ->and($html)->toContain('1250-096 Lisbon')
        ->and($html)->toContain('Portugal');
});

// ── Owner isolation: an owner cannot download another owner's statement (cross-owner 404) ────────
it('404s a cross-owner statement PDF', function () {
    $s = seedScenario(2);
    $a = pdfStatementOwner($s, 0, 20000);
    $b = pdfStatementOwner($s, 1, 30000);

    // Owner B asking for owner A's statement PDF → 404 (owner-scoped, no leak).
    $this->actingAs($b['user'])->get("/owner/statements/{$a['statement']->id}/pdf")->assertNotFound();
    // Owner A can download their own.
    $this->actingAs($a['user'])->get("/owner/statements/{$a['statement']->id}/pdf")->assertOk();
});

// ── Fail closed: a non-linked user (no owner) can download nothing ────────────────────────────────
it('fails closed for a non-linked user', function () {
    $s = seedScenario(2);
    $a = pdfStatementOwner($s, 0, 20000);

    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);

    $this->actingAs($stranger)->get("/owner/statements/{$a['statement']->id}/pdf")->assertNotFound();
});

// ── The document's ink stays post-AA — a statement is read on WHITE PAPER ─────────────────────────
// The pre-AA greys survived a whole pass on this template before Owner-2 measured the generated PDF's own
// content stream and found #8A8079 (3.86 on white) carrying the NEGATIVE MONEY — the commission and
// deduction figures themselves — and #A69A92 (2.74) the footer. Nothing guarded that fix, so it could
// silently return. Blade comments are stripped at compile time, so this asserts the RENDERED document.
// (The .band greys are deliberately absent from this check: they sit on #2A211C and measure 8.21 / 7.05,
// where darkening would be backwards.)
it('keeps the statement document free of the pre-AA greys', function () {
    $s = seedScenario(2);
    $a = pdfStatementOwner($s, 0, 20000);

    $html = view('pdfs.owner-statement', OwnerStatementDocument::data($a['statement']))->render();

    expect($html)->not->toContain('#8A8079')
        ->and($html)->not->toContain('#A69A92');
});
