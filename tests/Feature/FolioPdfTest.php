<?php

use App\Models\Membership;
use App\Models\User;
use App\Services\FolioService;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use App\Support\Pdf\FolioDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Demo-readiness · Part B · Step B2 — operator FOLIO PDF. Real append-only ledger (charges +, payments −,
// refunds their own line); reconciles to the cent (matching the on-screen folio); TENANT-scoped download.

function folioMember(array $s, string $email = 'folio-op@vyrobel.test'): User
{
    $user = User::factory()->create(['email' => $email, 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return $user;
}

/** Book a reservation with a folio: room €300 + city tax €30 (= €330 charges), + a €200 payment. */
function pdfResWithFolio(array $s): array
{
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($res);
    $folios->postCharge($folio, 'room', 'Room stay', Money::of(30000, 'EUR'));
    $folios->postCharge($folio, 'tax', 'City tax', Money::of(3000, 'EUR'));
    $folios->recordPayment($folio, Money::of(20000, 'EUR'));

    return ['res' => $res, 'folio' => $folio->refresh()];
}

// ── The operator downloads a folio PDF (application/pdf, filename, %PDF bytes) ────────────────────
it('streams a real folio PDF to the operator', function () {
    $s = seedScenario(1);
    $f = pdfResWithFolio($s);

    $response = $this->actingAs(folioMember($s))->get("/app/reservations/{$f['res']->id}/folio.pdf");

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('attachment')
        ->and($response->headers->get('content-disposition'))->toContain('vyrobel-folio-'.$f['res']->reference)
        ->and(str_starts_with((string) $response->getContent(), '%PDF'))->toBeTrue();
});

// ── Reconciles to the cent — charges/paid/balance match the folio ledger ─────────────────────────
it('reconciles to the cent against the real folio ledger', function () {
    $s = seedScenario(1);
    $f = pdfResWithFolio($s);

    $data = FolioDocument::data($f['res']);

    expect($data['charges'])->toBe(MoneyFormatter::display(33000, 'EUR'))   // 300 + 30
        ->and($data['paid'])->toBe(MoneyFormatter::display(20000, 'EUR'))    // 200 payment
        ->and($data['balance'])->toBe(MoneyFormatter::display(13000, 'EUR')) // 330 − 200
        // The document balance equals the folio's own FolioService-maintained cached figure.
        ->and($data['balance_minor'])->toBe((int) $f['folio']->balance_minor);
});

// ── Payments negative + refunds their OWN offsetting line (append-only truth) ────────────────────
it('shows the payment negative and a refund as its own offsetting line', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($res);
    $folios->postCharge($folio, 'room', 'Room stay', Money::of(30000, 'EUR'));
    $payment = $folios->recordPayment($folio, Money::of(30000, 'EUR'));
    $folios->refundPayment($payment, Money::of(10000, 'EUR'));   // partial refund → its own line

    $data = FolioDocument::data($res->refresh());

    // A settled payment renders negative; the refund is its OWN positive offsetting line (original intact).
    $amounts = array_map(fn ($l) => $l['amount'], $data['lines']);
    expect($amounts)->toContain('−'.MoneyFormatter::display(30000, 'EUR'))   // payment −€300.00
        ->and($amounts)->toContain('+'.MoneyFormatter::display(10000, 'EUR'))  // refund +€100.00 (own line)
        // Reconciles: charges 300 − paid (300 − 100 refunded) = 100 balance.
        ->and($data['balance'])->toBe(MoneyFormatter::display(10000, 'EUR'))
        ->and($data['balance_minor'])->toBe((int) $folio->refresh()->balance_minor);
});

// ── No invented items + PDF-safe rendering ───────────────────────────────────────────────────────
it('renders only real ledger lines and is PDF-safe', function () {
    $s = seedScenario(1);
    $f = pdfResWithFolio($s);

    $html = view('pdfs.folio', FolioDocument::data($f['res']))->render();

    expect($html)->toContain($f['res']->reference)
        ->and($html)->toContain('Room stay')
        ->and($html)->toContain(MoneyFormatter::display(13000, 'EUR'))   // balance
        ->and(strtolower($html))->not->toContain('card processing')
        ->and(strtolower($html))->not->toContain('processing fee')
        ->and($html)->not->toContain('backdrop-filter')
        ->and($html)->not->toContain('display:flex')
        ->and($html)->not->toContain('display: flex');
});

// ── Tenant isolation: an operator cannot export another org's folio (cross-org 404) ─────────────
it('404s a cross-org folio PDF', function () {
    $other = seedScenario(1);                 // org B
    $foreign = pdfResWithFolio($other);
    $s = seedScenario(1);                      // org A (context switches)
    $op = folioMember($s);

    $this->actingAs($op)->get("/app/reservations/{$foreign['res']->id}/folio.pdf")->assertNotFound();

    // The operator's own org folio downloads fine.
    $own = pdfResWithFolio($s);
    $this->actingAs($op)->get("/app/reservations/{$own['res']->id}/folio.pdf")->assertOk();
});
