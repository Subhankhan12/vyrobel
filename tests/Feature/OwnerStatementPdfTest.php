<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerStatementLine;
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

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));
    app(OwnerPayoutService::class)->createFromStatement($statement);

    return compact('user', 'owner', 'unit', 'statement');
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

    expect($html)->toContain($a['owner']->name)
        ->and($html)->toContain(MoneyFormatter::display((int) $a['statement']->net_payable_minor, 'EUR'))
        ->and(strtolower($html))->not->toContain('card-processing')
        ->and(strtolower($html))->not->toContain('processing fee')
        // PDF-SAFE: no frosted-glass / flex-dependent layout in the document.
        ->and($html)->not->toContain('backdrop-filter')
        ->and($html)->not->toContain('display:flex')
        ->and($html)->not->toContain('display: flex');
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
