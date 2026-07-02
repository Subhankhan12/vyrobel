<?php

use App\Models\Payment;
use App\Services\FolioService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Obligation 8 — append-only ledger: a refund is a new offsetting entry (original untouched);
// a deposit hold is not counted as paid until captured.

it('records a refund as a NEW offsetting payment and never mutates the original', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);
    $folios->postCharge($folio, 'room', 'Room', moneyEur(30000));

    $payment = $folios->recordPayment($folio, moneyEur(30000));
    expect($folio->refresh()->balance_minor)->toBe(0);

    $originalMinor = $payment->amount_minor;
    $refund = $folios->refundPayment($payment, moneyEur(10000));

    $payment->refresh();
    expect($payment->amount_minor)->toBe($originalMinor)   // original untouched
        ->and($payment->type)->toBe('payment')
        ->and($payment->status)->toBe('succeeded')
        ->and($refund->getKey())->not->toBe($payment->getKey()) // brand-new row
        ->and($refund->type)->toBe('refund')
        ->and(Payment::query()->where('folio_id', $folio->id)->count())->toBe(2); // append-only

    $folio->refresh();
    expect($folio->paid_minor)->toBe(20000)   // 30000 − 10000 refund
        ->and($folio->balance_minor)->toBe(10000);
});

it('does not count a deposit hold as paid until it is captured', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);
    $folios->postCharge($folio, 'room', 'Room', moneyEur(30000));

    $auth = $folios->authorizeDeposit($folio, moneyEur(10000));
    expect($folio->refresh()->paid_minor)->toBe(0)   // hold is NOT paid
        ->and($auth->status)->toBe('authorized');

    $folios->captureDeposit($auth, moneyEur(10000));
    expect($folio->refresh()->paid_minor)->toBe(10000)  // capture counts as paid
        ->and($auth->refresh()->status)->toBe('captured');
});
