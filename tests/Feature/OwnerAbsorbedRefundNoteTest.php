<?php

declare(strict_types=1);

use App\Livewire\Owner\OwnerBookingDetail;
use App\Models\Folio;
use App\Models\OwnerLedgerEntry;
use App\Models\Payment;
use App\Services\FolioService;
use App\Services\OwnerAttributionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * ⚠️ A NOTE ON A MONEY PAGE — which is exactly where a total quietly gets recomputed. These tests exist to
 * pin that it is DISPLAY ONLY: every owner figure byte-identical, #17 holding to the cent WHILE the note
 * renders, and no ledger row posted by it.
 */
function absorbedNoteScenario(int $refundMinor, ?string $borneBy): array
{
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0], ['commission_pct' => 18]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());

    $folios = app(FolioService::class);
    $folio = Folio::query()->where('reservation_id', $reservation->getKey())->firstOrFail();
    $payment = $folios->recordPayment($folio, Money::of(20000, 'EUR'));
    $folios->refundPayment($payment, Money::of($refundMinor, 'EUR'), 'goodwill', null, $borneBy);

    return [$s, $owner, $reservation, $payment, $folios];
}

it('DISPLAY ONLY — an absorbed refund moves no owner figure and posts no ledger row', function () {
    [$s, $owner, $reservation] = absorbedNoteScenario(5000, Payment::BORNE_BY_OPERATOR);

    $rows = OwnerLedgerEntry::query()->where('reservation_id', $reservation->id)->get();

    // ⚠️ No adjustment posted at all — the note is not a ledger entry.
    expect($rows->where('entry_type', 'adjustment'))->toHaveCount(0);

    // ⚠️ #17 — gross − deductions == net, to the cent, with the absorbed refund in existence.
    $gross = $rows->where('entry_type', 'room_revenue')->sum(fn ($r) => $r->signedMinor());
    $deductions = $rows->where('entry_type', '!=', 'room_revenue')->sum(fn ($r) => $r->signedMinor());
    $net = $rows->sum(fn ($r) => $r->signedMinor());

    expect($gross + $deductions)->toBe($net);
});

it('the owner page renders the note, with the REAL amount and no guest data', function () {
    [$s, $owner, $reservation] = absorbedNoteScenario(5000, Payment::BORNE_BY_OPERATOR);

    Livewire::actingAs(ownerRoleUser($s))
        ->test(OwnerBookingDetail::class, ['booking' => $reservation->id])
        ->assertSee('Refunds we covered')
        ->assertSee('50.00')                                     // the REAL refund amount
        ->assertSee('not to you', false);
});

it('ABSENT → SILENT — no note and no zero placeholder when nothing was absorbed', function () {
    [$s, $owner, $reservation] = absorbedNoteScenario(5000, null);   // owner-borne

    Livewire::actingAs(ownerRoleUser($s))
        ->test(OwnerBookingDetail::class, ['booking' => $reservation->id])
        ->assertDontSee('Refunds we covered');
});

it('an OWNER-borne refund still renders as an Adjustment deduction, not as the note', function () {
    [$s, $owner, $reservation] = absorbedNoteScenario(5000, null);

    expect(OwnerLedgerEntry::query()
        ->where('reservation_id', $reservation->id)
        ->where('entry_type', 'adjustment')
        ->count())->toBe(1);

    Livewire::actingAs(ownerRoleUser($s))
        ->test(OwnerBookingDetail::class, ['booking' => $reservation->id])
        ->assertSee('Adjustment');
});

it('SEVERAL refunds with a MIX of borne-by — only the absorbed ones appear', function () {
    [$s, $owner, $reservation, $payment, $folios] = absorbedNoteScenario(3000, Payment::BORNE_BY_OPERATOR);

    $folios->refundPayment($payment, Money::of(2000, 'EUR'), 'second', null, null);                       // owner
    $folios->refundPayment($payment, Money::of(1000, 'EUR'), 'third', null, Payment::BORNE_BY_OPERATOR);  // absorbed

    $response = Livewire::actingAs(ownerRoleUser($s))
        ->test(OwnerBookingDetail::class, ['booking' => $reservation->id]);

    $response->assertSee('30.00')->assertSee('10.00');   // both absorbed refunds listed

    // ⚠️ The owner-borne one is a real adjustment, and must NOT be duplicated into the note.
    expect(OwnerLedgerEntry::query()
        ->where('reservation_id', $reservation->id)
        ->where('entry_type', 'adjustment')
        ->count())->toBe(1);
});
