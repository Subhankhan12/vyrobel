<?php

declare(strict_types=1);

use App\Models\Folio;
use App\Models\OwnerLedgerEntry;
use App\Models\Unit;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Services\FolioService;
use App\Services\OwnerAttributionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

// ⚠️⚠️ VY-02 (audit of 2026-11-24, reproduced) — A REFUND TAKES THE OWNER'S SHARE OF THE ROOM, NEVER OF THE TAX.
//
// The owner is credited from the ROOM revenue only. The old formula took their share from the WHOLE refund — city tax, operator fees and
// add-ons included — so a full refund of "room 200.00 + city tax 20.00" debited 187.00 from an owner who had been credited 170.00, and left
// them 17.00 in debt for a stay the guest never paid for. The same formula charged ONE owner for the WHOLE refund when only one of two
// rooms was theirs, and under-charged the second of two partial refunds.
//
// The rule now, in one sentence: the owner gives back the same fraction of what they were credited as the guest got back of what the
// folio charged — and never more than they were credited.

uses(DatabaseTransactions::class);

/** A paid stay on an owned unit: room 200.00 at 15% commission (owner credited 170.00), plus whatever else the folio carries. */
function vy02Stay(int $unitCount = 1, ?callable $extraCharges = null): array
{
    $s = seedScenario($unitCount);
    $owner = ownUnit($s['units'][0], ['commission_pct' => 15]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id, 'price_minor' => 20000]);
    postRoom($reservation, 20000);

    $folios = app(FolioService::class);
    $folio = Folio::query()->where('reservation_id', $reservation->getKey())->firstOrFail();
    if ($extraCharges !== null) {
        $extraCharges($folios, $folio);
    }

    app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());

    return compact('s', 'owner', 'reservation', 'folio', 'folios');
}

function vy02RefundDebits(string $ownerId): int
{
    return (int) OwnerLedgerEntry::query()->where('owner_id', $ownerId)->where('entry_type', 'adjustment')->where('note', 'refund')->sum('amount_minor');
}

it('⚠️⚠️ VY-02: a full refund of a stay that carried a tax leaves the owner at zero — not in debt', function () {
    $c = vy02Stay(1, fn (FolioService $f, Folio $folio) => $f->postCharge($folio, 'tax', 'City tax', Money::of(2000, 'EUR')));
    expect(ownerNet($c['owner']->id))->toBe(17000);

    $payment = $c['folios']->recordPayment($c['folio']->refresh(), Money::of(22000, 'EUR'));
    $c['folios']->refundPayment($payment, Money::of(22000, 'EUR'), 'Guest cancelled');

    // 220.00 refunded of 220.00 charged → the owner gives back all 170.00 they were credited, and not a cent more.
    expect(vy02RefundDebits($c['owner']->id))->toBe(17000)
        ->and(ownerNet($c['owner']->id))->toBe(0);
});

it('VY-02: a part refund of a taxed stay takes the same fraction of the owner credit as of the folio', function () {
    $c = vy02Stay(1, fn (FolioService $f, Folio $folio) => $f->postCharge($folio, 'tax', 'City tax', Money::of(2000, 'EUR')));

    $payment = $c['folios']->recordPayment($c['folio']->refresh(), Money::of(22000, 'EUR'));
    $c['folios']->refundPayment($payment, Money::of(11000, 'EUR'), 'Half back');

    // half the folio back → half the owner's 170.00
    expect(vy02RefundDebits($c['owner']->id))->toBe(8500)
        ->and(ownerNet($c['owner']->id))->toBe(8500);
});

it('VY-02: two part refunds that add up to the whole take exactly the whole credit — the second is not shrunk by the first', function () {
    $c = vy02Stay(1, fn (FolioService $f, Folio $folio) => $f->postCharge($folio, 'tax', 'City tax', Money::of(2000, 'EUR')));

    $payment = $c['folios']->recordPayment($c['folio']->refresh(), Money::of(22000, 'EUR'));
    $c['folios']->refundPayment($payment, Money::of(11000, 'EUR'), 'First half');
    $c['folios']->refundPayment($payment, Money::of(11000, 'EUR'), 'Second half');

    expect(vy02RefundDebits($c['owner']->id))->toBe(17000)
        ->and(ownerNet($c['owner']->id))->toBe(0);
});

it('⚠️ VY-02: the debit is CAPPED at what the owner was credited, whatever the refund', function () {
    $c = vy02Stay();

    // The guest overpaid by hand (a deposit on top) and gets all of it back: 300.00 refunded against a 200.00 folio.
    $payment = $c['folios']->recordPayment($c['folio']->refresh(), Money::of(30000, 'EUR'));
    $c['folios']->refundPayment($payment, Money::of(30000, 'EUR'), 'Everything back');

    expect(vy02RefundDebits($c['owner']->id))->toBe(17000)
        ->and(ownerNet($c['owner']->id))->toBe(0);
});

it('VY-02: an owner whose room is one of two is charged for their room only — not for the whole refund', function () {
    $s = seedScenario(2);
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-09-01', '2026-09-10');
    $owner = ownUnit($s['units'][0], ['commission_pct' => 15]);

    // one booking, two rooms: the first is the owner's, the second is the operator's own
    $reservation = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        array_map(fn (Unit $u): array => [
            'room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id, 'unit_id' => $u->id,
            'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'price_minor' => 10000, 'currency' => 'EUR',
        ], $s['units']),
    );
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());
    expect(ownerNet($owner->id))->toBe(8500);   // 100.00 of the 200.00 is theirs, less 15%

    $folios = app(FolioService::class);
    $folio = Folio::query()->where('reservation_id', $reservation->getKey())->firstOrFail();
    $payment = $folios->recordPayment($folio, Money::of(20000, 'EUR'));
    $folios->refundPayment($payment, Money::of(10000, 'EUR'), 'Half back');

    // half the folio back → half of THEIR 85.00, not 85% of the whole 100.00 refund
    expect(vy02RefundDebits($owner->id))->toBe(4250)
        ->and(ownerNet($owner->id))->toBe(4250);
});

it('VY-02 (unchanged): a room-only stay still gives back the owner net share of the refund, to the cent', function () {
    $c = vy02Stay();

    $payment = $c['folios']->recordPayment($c['folio']->refresh(), Money::of(20000, 'EUR'));
    $refund = $c['folios']->refundPayment($payment, Money::of(5000, 'EUR'), 'Overcharge');

    // 50.00 of 200.00 → 25% of 170.00 = 42.50, exactly as before this fix (5000 × 17000 ÷ 20000)
    expect(vy02RefundDebits($c['owner']->id))->toBe(4250);

    // …and running the same refund again changes nothing (one row per refund and owner)
    app(OwnerAttributionService::class)->attributeRefund($c['reservation']->refresh(), Money::of(5000, 'EUR'), (string) $refund->getKey());
    expect(vy02RefundDebits($c['owner']->id))->toBe(4250);
});
