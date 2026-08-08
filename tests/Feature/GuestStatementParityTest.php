<?php

use App\Models\Folio;
use App\Models\Reservation;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Support\Folio\FolioTotals;
use App\Support\Money\Money;
use App\Support\Pdf\FolioDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * THE STATEMENT OF ACCOUNT IS A STATEMENT, NOT THE RECEIPT RELABELLED (audit 2026-08-07 · §5 parity).
 *
 * Both documents read the same real append-only ledger — correct, and the point. But the statement
 * rendered the receipt's layout under a different title, missing the prototype's 4-tile KPI band, and
 * in particular the CREDITED tile: a guest who had been refunded could not see that fact stated
 * anywhere on their statement of account.
 *
 * ⚠️ THE CREDITED FIGURE IS REAL, AND SHARED. FolioTotals already computed Σ refunds on its way to
 * `paid_minor` and threw it away; it now returns it. Deriving refunds a SECOND time for one tile is
 * exactly how two surfaces come to state different money (#17) — so the tile reads the same single
 * pass over the same rows that every other folio figure reads.
 *
 * ⚠️ `paid_minor` IS UNCHANGED and stays NET. The receipt and the on-screen folio show it; moving it
 * to gross to make a tile band tidier would have silently changed a shipped figure.
 */

function statementScenario(): Reservation
{
    $s = seedScenario(1);

    $reservation = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-05',
            'check_out' => '2026-09-08', 'lead_guest_id' => $s['guest']->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'rate_plan_id' => $s['ratePlan']->id,
            'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'price_minor' => 30000, 'currency' => 'EUR']],
    );

    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room stay', Money::of(30000, 'EUR'));

    return $reservation;
}

function reloadFolio(Reservation $reservation): Folio
{
    return Folio::query()->where('reservation_id', $reservation->getKey())->with(['items', 'payments'])->firstOrFail();
}

it('states a REAL credited figure once a refund exists', function () {
    $reservation = statementScenario();
    $folio = reloadFolio($reservation);

    $payment = app(FolioService::class)->recordPayment($folio, Money::of(30000, 'EUR'), 'cash');

    // Before any refund, the tile is an honest zero — it is never hidden to avoid showing one.
    expect(FolioDocument::data($reservation->fresh())['credited_minor'])->toBe(0);

    app(FolioService::class)->refundPayment($payment, Money::of(5000, 'EUR'), 'guest goodwill');

    $data = FolioDocument::data($reservation->fresh());

    expect($data['credited_minor'])->toBe(5000)
        ->and($data['credited'])->toContain('50.00');

    // The tile and the band render — the gap was the band's absence, not the figure's.
    $html = view('pdfs.guest-statement', $data)->render();
    expect($html)->toContain('Credited')->toContain('Account activity')->toContain('Balance due');
});

it('keeps every KPI reconciling to the ONE certified source (#17)', function () {
    $reservation = statementScenario();
    $folio = reloadFolio($reservation);

    $payment = app(FolioService::class)->recordPayment($folio, Money::of(30000, 'EUR'), 'cash');
    app(FolioService::class)->refundPayment($payment, Money::of(5000, 'EUR'), 'partial');

    $totals = FolioTotals::for(reloadFolio($reservation));

    // The two halves this class used to discard now balance its own net figure, exactly.
    expect($totals['paid_minor'])->toBe($totals['received_minor'] - $totals['refunded_minor']);

    // And the long-standing identity is untouched by the addition.
    expect($totals['balance_minor'])->toBe($totals['charges_minor'] - $totals['paid_minor']);

    // 30000 charged · 30000 received · 5000 credited back ⇒ 25000 net paid ⇒ 5000 still due.
    expect($totals)->toMatchArray([
        'charges_minor' => 30000,
        'received_minor' => 30000,
        'refunded_minor' => 5000,
        'paid_minor' => 25000,
        'balance_minor' => 5000,
    ]);
});

it('does not move the figure the folio receipt already showed', function () {
    $reservation = statementScenario();
    $folio = reloadFolio($reservation);

    $payment = app(FolioService::class)->recordPayment($folio, Money::of(30000, 'EUR'), 'cash');
    app(FolioService::class)->refundPayment($payment, Money::of(5000, 'EUR'), 'partial');

    $data = FolioDocument::data($reservation->fresh());

    // ⚠️ `paid` stays NET across BOTH documents — the receipt and the statement share this data, so a
    // change here would have silently rewritten what an already-shipped document says.
    expect($data['paid'])->toContain('250.00');

    // The two documents therefore agree, tile for line.
    $receipt = view('pdfs.folio', $data)->render();
    $statement = view('pdfs.guest-statement', $data)->render();
    foreach ([$receipt, $statement] as $html) {
        expect($html)->toContain($data['paid'])->toContain($data['balance']);
    }
});
