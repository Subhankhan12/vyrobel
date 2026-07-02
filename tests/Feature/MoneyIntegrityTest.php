<?php

use App\Models\RateCalendar;
use App\Models\Tax;
use App\Services\FolioService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Obligation 7 — money round-trips as integer minor units end-to-end (rate → folio → payment),
// no float drift; folio.total = SUM(folio_items.amount_minor) with tax as its own line.

it('keeps money as integer minor units from rate through folio to payment', function () {
    $s = seedScenario(1);

    $rate = RateCalendar::create(['rate_plan_id' => $s['ratePlan']->id, 'date' => '2026-09-01', 'price' => moneyEur(12345)]);
    $rate = $rate->fresh();
    expect($rate->price->amountMinor())->toBe(12345)
        ->and(is_int($rate->price->amountMinor()))->toBeTrue()
        ->and((int) $rate->getAttributes()['price_minor'])->toBe(12345);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);

    $folios->postCharge($folio, 'room', 'Room', moneyEur(30000));
    $vat = Tax::create(['property_id' => $s['property']->id, 'name' => 'VAT', 'kind' => 'vat', 'mode' => 'percentage', 'rate' => 20.0]);
    $folios->applyTax($folio, $vat, moneyEur(30000)); // 6000, its own line
    $folio->refresh();

    expect($folio->total_minor)->toBe(36000)
        ->and(is_int($folio->total_minor))->toBeTrue()
        ->and($folio->total_minor)->toBe((int) $folio->items()->sum('amount_minor')) // total = ledger sum
        ->and((int) $folio->items()->where('type', 'tax')->sum('amount_minor'))->toBe(6000) // tax is a distinct line
        ->and($folio->items()->count())->toBe(2);

    $folios->recordPayment($folio, moneyEur(36000));
    $folio->refresh();

    expect($folio->paid_minor)->toBe(36000)
        ->and($folio->balance_minor)->toBe(0)
        ->and($folio->balance->amountMinor())->toBe(0)   // Money cast round-trips
        ->and($reservation->refresh()->payment_status)->toBe('paid');
});
