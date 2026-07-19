<?php

use App\Services\FolioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// Phase 0.2 — the "dead enum values" register item resolved per enum (the product must not lie to itself):
//   • reservations.payment_status='refunded' is now WRITTEN (a fully-refunded stay must not read 'unpaid');
//     'charged_back' was REMOVED (no chargeback/dispute flow exists).
//   • folios.status 'closed'/'void' were REMOVED (no folio close/void lifecycle; a half-built lock would lie).
// The schema now declares exactly what the code produces — proven both ways below.

/** A stay charged + paid in full through the certified folio path; returns the service, folio and payment. */
function paidStay(int $paidMinor = 36900): array
{
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);
    $folios->postCharge($folio, 'room', 'Room', moneyEur($paidMinor));
    $payment = $folios->recordPayment($folio, moneyEur($paidMinor));

    return ['folios' => $folios, 'folio' => $folio, 'payment' => $payment, 'reservation' => $reservation];
}

// ── The WRITE — a fully-refunded stay is now 'refunded', not 'unpaid' (the mutation) ──────────────
it('writes payment_status=refunded for a fully-refunded stay (was silently unpaid before Phase 0.2)', function () {
    ['folios' => $folios, 'folio' => $folio, 'payment' => $payment, 'reservation' => $reservation] = paidStay(36900);

    expect($reservation->refresh()->payment_status)->toBe('paid'); // paid in full first

    $folios->refundPayment($payment, moneyEur(36900)); // full refund

    // The money came IN and went (net) fully back OUT — truthfully 'refunded', NOT 'unpaid' (never paid).
    // Remove the new match arm in FolioService::paymentStatus() and this reverts to 'unpaid'.
    expect($reservation->refresh()->payment_status)->toBe('refunded');
});

it('keeps payment_status=partial for a partial refund (net money still in)', function () {
    ['folios' => $folios, 'payment' => $payment, 'reservation' => $reservation] = paidStay(36900);

    $folios->refundPayment($payment, moneyEur(10000)); // refund €100 of €369 → €269 net still in

    expect($reservation->refresh()->payment_status)->toBe('partial');
});

// ── The REMOVAL — the schema no longer allows any value the code cannot write ──────────────────────
it('removed the dead enum values from the schema (nothing keys on an unwritable value)', function () {
    $paymentStatus = DB::selectOne("SHOW COLUMNS FROM reservations LIKE 'payment_status'")->Type;
    expect($paymentStatus)->toBe("enum('unpaid','partial','paid','refunded')")
        ->and($paymentStatus)->not->toContain('charged_back');

    $folioStatus = DB::selectOne("SHOW COLUMNS FROM folios LIKE 'status'")->Type;
    expect($folioStatus)->toBe("enum('open')")
        ->and($folioStatus)->not->toContain('closed')
        ->and($folioStatus)->not->toContain('void');
});
