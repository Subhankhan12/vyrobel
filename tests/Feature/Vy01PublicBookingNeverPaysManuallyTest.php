<?php

declare(strict_types=1);

use App\Models\Folio;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\BookingEngineService;
use App\Services\FolioService;
use App\Support\Folio\FolioTotals;
use Illuminate\Foundation\Testing\DatabaseTransactions;

// ⚠️⚠️ VY-01 (audit of 2026-11-24, reproduced; P0) — A VISITOR CAN NEVER RECORD A PAYMENT THROUGH THE MANUAL GATEWAY.
//
// A signed-out call to the public JSON `book` endpoint, with ANY text as the card token, answered 201 and left a payment row reading
// `succeeded` for the whole stay, the reservation `paid` and the folio balance 0. The manual gateway approves every charge — it models
// cash a member of staff took at the desk — and the public endpoint handed it a visitor's own word. A booking faked this way also
// passed the collection gate, so it would have become payable to an owner.
//
// The public path now records NO payment while the gateway is manual: the booking is made with its balance DUE (pay at the property),
// exactly like the web checkout's "pay on arrival". A manual payment is recorded only by signed-in staff (`payments.record`).
//
// ⚠️ EACH REFUSAL HAS ITS CONTROL: with the card gateway selected the same call still pays (the gateway decides, not the visitor),
// and staff recording cash at the desk still works.

uses(DatabaseTransactions::class);

function vy01Book(array $s, array $overrides = [])
{
    $quote = test()->postJson("/book/{$s['property']->slug}/quote", [
        'room_type_id' => $s['roomType']->id,
        'rate_plan_id' => $s['ratePlan']->id,
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-03',
        'adults' => 2,
        'email' => 'ottoline.vy01@guestmail.dev',
    ])->assertCreated()->json('data');

    return test()->postJson("/book/{$s['property']->slug}/book", array_merge([
        'quote_id' => $quote['quote_id'],
        'payment_token' => 'anything-a-visitor-types',
        'email' => 'ottoline.vy01@guestmail.dev',
        'first_name' => 'Ottoline',
    ], acceptedTerms($s['property']), $overrides));
}

function vy01Reservation(array $s): Reservation
{
    return Reservation::query()->where('property_id', $s['property']->id)->firstOrFail();
}

it('⚠️⚠️ VY-01 (as reproduced): an anonymous JSON booking under the manual gateway records NO payment — the stay is booked with its balance due', function () {
    $s = publicPropertyScenario(units: 1);
    config(['services.payments.gateway' => 'manual']);   // the default, and what the audit ran under

    $response = vy01Book($s)->assertCreated();

    $reservation = vy01Reservation($s);
    $folio = Folio::query()->where('reservation_id', $reservation->id)->firstOrFail();

    expect($response->json('data.status'))->toBe('confirmed')
        ->and($response->json('data.payment_status'))->toBeNull()
        // no payment row of any kind, and nothing reads as collected
        ->and(Payment::query()->where('folio_id', $folio->id)->count())->toBe(0)
        ->and($reservation->payment_status)->not->toBe('paid')
        ->and((int) $folio->paid_minor)->toBe(0)
        ->and(FolioTotals::for($folio->load(['items', 'payments']))['balance_minor'])->toBe(24000);
});

it('⚠️ VY-01: the guard is in the SERVICE — a direct call that asks for an inline payment under the manual gateway still records none', function () {
    $s = publicPropertyScenario(units: 1);
    config(['services.payments.gateway' => 'manual']);
    $engine = app(BookingEngineService::class);

    $quote = $engine->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-03', 2, 0, 'direct.vy01@guestmail.dev');
    $result = $engine->book($quote, 'offline', ['email' => 'direct.vy01@guestmail.dev'], takePayment: true);

    expect($result['status'])->toBe('confirmed')
        ->and($result['payment'])->toBeNull()
        ->and(Payment::query()->where('folio_id', $result['folio']->id)->count())->toBe(0)
        ->and($result['reservation']->payment_status)->not->toBe('paid');
});

it('VY-01 CONTROL: with the card gateway selected the same public call still pays — the gateway decides, not the visitor', function () {
    $s = publicPropertyScenario(units: 1);   // selects the card gateway (the in-memory fake)
    onboardOrgForCards($s);

    $response = vy01Book($s, ['payment_token' => 'pm_card_visa'])->assertCreated();

    $reservation = vy01Reservation($s);
    $payment = Payment::query()->where('folio_id', Folio::query()->where('reservation_id', $reservation->id)->value('id'))->firstOrFail();

    expect($response->json('data.payment_status'))->toBe('succeeded')
        ->and($payment->gateway)->toBe('stripe')
        ->and($reservation->payment_status)->toBe('paid');
});

it('VY-01 CONTROL: staff recording cash at the desk still works under the manual gateway', function () {
    $s = publicPropertyScenario(units: 1);
    config(['services.payments.gateway' => 'manual']);

    vy01Book($s)->assertCreated();
    $reservation = vy01Reservation($s);
    $folio = Folio::query()->where('reservation_id', $reservation->id)->firstOrFail();

    $payment = app(FolioService::class)->recordPayment($folio, moneyEur(24000), 'cash');

    expect($payment->gateway)->toBe('manual')
        ->and($payment->status)->toBe('succeeded')
        ->and($reservation->refresh()->payment_status)->toBe('paid');
});

it('VY-01: the public endpoint no longer needs a card token at all while there is no card gateway', function () {
    $s = publicPropertyScenario(units: 1);
    config(['services.payments.gateway' => 'manual']);

    $quote = test()->postJson("/book/{$s['property']->slug}/quote", [
        'room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id,
        'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'adults' => 2,
    ])->assertCreated()->json('data');

    test()->postJson("/book/{$s['property']->slug}/book", array_merge([
        'quote_id' => $quote['quote_id'], 'email' => 'notoken.vy01@guestmail.dev',
    ], acceptedTerms($s['property'])))->assertCreated()->assertJsonPath('data.payment_status', null);

    expect(Payment::query()->whereIn('folio_id', Folio::query()->where('reservation_id', vy01Reservation($s)->id)->pluck('id'))->count())->toBe(0);
});
