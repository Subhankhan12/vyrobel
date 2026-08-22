<?php

use App\Models\Channel;
use App\Models\Fee;
use App\Models\Folio;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\Reservation;
use App\Models\Tax;
use App\Models\Unit;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Services\OwnerAttributionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Phase 3 · Module 1 Step 2 — the revenue attribution engine (the crux). Every figure on an owner
// statement must trace to a specific rule, and owner + operator shares reconcile to the folio room
// revenue to the cent. (ownUnit/postRoom/ownerNet/lineFor live in tests/Support/owner_helpers.php.)

// ── 1. Room + commission correct; owner_share + operator_share == room revenue exactly ──────────
it('attributes room revenue and commission with exact owner/operator complementarity', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0], ['commission_pct' => 15.00]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);

    app(OwnerAttributionService::class)->attributeReservation($reservation);

    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    $room = lineFor($owner->id, 'room_revenue');
    $commission = lineFor($owner->id, 'commission');

    expect($room->direction)->toBe('credit')
        ->and($room->amount_minor)->toBe(20000)
        ->and($commission->direction)->toBe('debit')
        ->and($commission->amount_minor)->toBe(3000); // 15% of 20000

    // owner_share (room − commission) + operator_share (commission) == room revenue, to the cent.
    $ownerShare = $room->amount_minor - $commission->amount_minor;
    $operatorShare = $commission->amount_minor;
    expect($ownerShare + $operatorShare)->toBe(20000);
});

// ── 2. OTA cost: reservation override → channel pct → 0-flagged-unverified ──────────────────────
it('reads the per-reservation OTA commission override', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $reservation->update(['ota_commission_minor' => 2500]);
    postRoom($reservation, 20000);

    app(OwnerAttributionService::class)->attributeReservation($reservation);

    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    $ota = lineFor($owner->id, 'ota_cost');
    expect($ota->amount_minor)->toBe(2500)
        ->and($ota->direction)->toBe('debit')
        ->and($ota->unverified)->toBeFalse();
});

it('falls back to the channel commission percentage when no reservation override', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $channel = Channel::factory()->create(['property_id' => $s['property']->id, 'commission_pct' => 18.00]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $reservation->update(['channel_id' => $channel->id, 'source' => 'ota']);
    postRoom($reservation, 20000);

    app(OwnerAttributionService::class)->attributeReservation($reservation);

    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    $ota = lineFor($owner->id, 'ota_cost');
    expect($ota->amount_minor)->toBe(3600) // 18% of 20000
        ->and($ota->unverified)->toBeFalse();
});

it('attributes OTA cost = 0 flagged unverified when neither source is present', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]); // direct booking, no channel, no override
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);

    app(OwnerAttributionService::class)->attributeReservation($reservation);

    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    $ota = lineFor($owner->id, 'ota_cost');
    expect($ota->amount_minor)->toBe(0)
        ->and($ota->unverified)->toBeTrue()
        ->and($ota->note)->toBe('ota_unverified');
});

// ── 3. Cleaning fee + add-on rules/overrides ────────────────────────────────────────────────────
it('attributes cleaning fee to the owner and keeps add-ons with the operator by default', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of(20000, 'EUR'));

    $cleaning = Fee::create(['property_id' => $s['property']->id, 'name' => 'Cleaning', 'kind' => 'cleaning', 'mode' => 'flat', 'amount_minor' => 5000, 'currency' => 'EUR']);
    app(FolioService::class)->applyFee($folio, $cleaning);
    app(FolioService::class)->postCharge($folio, 'add_on', 'Breakfast', Money::of(3000, 'EUR'));

    app(OwnerAttributionService::class)->attributeReservation($reservation);

    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    expect(lineFor($owner->id, 'cleaning')?->amount_minor)->toBe(5000)
        ->and(lineFor($owner->id, 'cleaning')?->direction)->toBe('credit')
        ->and(lineFor($owner->id, 'addon'))->toBeNull(); // operator keeps add-ons by default
});

it('honours agreement overrides: add-on to owner, cleaning to operator', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0], [
        'attribution_rules' => ['addon_bearer' => 'owner', 'cleaning_fee_bearer' => 'operator'],
    ]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of(20000, 'EUR'));
    $cleaning = Fee::create(['property_id' => $s['property']->id, 'name' => 'Cleaning', 'kind' => 'cleaning', 'mode' => 'flat', 'amount_minor' => 5000, 'currency' => 'EUR']);
    app(FolioService::class)->applyFee($folio, $cleaning);
    app(FolioService::class)->postCharge($folio, 'add_on', 'Breakfast', Money::of(3000, 'EUR'));

    app(OwnerAttributionService::class)->attributeReservation($reservation);

    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    expect(lineFor($owner->id, 'addon')?->amount_minor)->toBe(3000)
        ->and(lineFor($owner->id, 'addon')?->direction)->toBe('credit')
        ->and(lineFor($owner->id, 'cleaning'))->toBeNull(); // operator bears cleaning under the override
});

// ── 4. Tax excluded from both sides; damage/deposit excluded ────────────────────────────────────
it('excludes pass-through tax and deposits from owner attribution', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0], ['commission_pct' => 10.00]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of(20000, 'EUR'));
    $cityTax = Tax::create(['property_id' => $s['property']->id, 'name' => 'City Tax', 'kind' => 'city_tax', 'mode' => 'flat', 'amount_minor' => 400, 'currency' => 'EUR']);
    app(FolioService::class)->applyTax($folio, $cityTax, Money::of(20000, 'EUR'));
    app(FolioService::class)->authorizeDeposit($folio, Money::of(15000, 'EUR'), 'pm_card_visa');

    app(OwnerAttributionService::class)->attributeReservation($reservation);

    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    // No tax line, no deposit line; net = room − commission − 0 ota = 20000 − 2000.
    expect(OwnerLedgerEntry::where('owner_id', $owner->id)->whereIn('entry_type', ['adjustment'])->count())->toBe(0)
        ->and(OwnerLedgerEntry::where('owner_id', $owner->id)->get()->every(fn ($e) => $e->entry_type !== 'tax'))->toBeTrue()
        ->and(ownerNet($owner->id))->toBe(20000 - 2000); // tax + deposit untouched
});

// ── 5. Multi-unit reservation splits to the correct owners ──────────────────────────────────────
it('splits a multi-unit reservation to the correct owners by their line share', function () {
    $s = seedScenario(2);
    $ownerA = ownUnit($s['units'][0]);
    $ownerB = ownUnit($s['units'][1]);

    $reservation = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        [
            ['room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'unit_id' => $s['units'][0]->id, 'price_minor' => 12000, 'currency' => 'EUR'],
            ['room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'unit_id' => $s['units'][1]->id, 'price_minor' => 8000, 'currency' => 'EUR'],
        ],
    );
    postRoom($reservation, 20000); // folio room total across both units

    app(OwnerAttributionService::class)->attributeReservation($reservation);

    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    // Allocated by price weights 12000:8000 → 12000 / 8000.
    expect(lineFor($ownerA->id, 'room_revenue')->amount_minor)->toBe(12000)
        ->and(lineFor($ownerB->id, 'room_revenue')->amount_minor)->toBe(8000);
    // Reconciles to the folio room revenue exactly.
    expect(lineFor($ownerA->id, 'room_revenue')->amount_minor + lineFor($ownerB->id, 'room_revenue')->amount_minor)->toBe(20000);
});

// ── 6. Mid-stay unit move attributes per-night to whoever owned the occupied unit ───────────────
it('attributes a mid-stay unit move per-night to the occupying owner', function () {
    $s = seedScenario(2);
    $ownerA = ownUnit($s['units'][0]);
    $ownerB = ownUnit($s['units'][1]);

    // 4-night stay on unit A.
    $reservation = book($s, ['unit_id' => $s['units'][0]->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-05']);
    $reservation->update(['check_out' => '2026-09-05']);
    postRoom($reservation, 40000); // 4 nights @ 10000

    // Move to unit B at the mid-point: split at night 3, reassign the second segment to unit B.
    $line = $reservation->reservationUnits()->where('status', 'active')->first();
    [$first, $second] = app(AssignmentService::class)->split($line, '2026-09-03');
    app(AssignmentService::class)->assignUnit($second, $s['units'][1]->id);

    app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());

    // split() zeroes the second segment's price → night-weighting: 2 nights each → 20000 / 20000.
    expect(lineFor($ownerA->id, 'room_revenue')->amount_minor)->toBe(20000)
        ->and(lineFor($ownerB->id, 'room_revenue')->amount_minor)->toBe(20000)
        ->and(lineFor($ownerA->id, 'room_revenue')->amount_minor + lineFor($ownerB->id, 'room_revenue')->amount_minor)->toBe(40000);
});

// ── 7. WHOLE-TRANSACTION reversal ───────────────────────────────────────────────────────────────
it('reverses every component of a booking together, leaving originals untouched and netting to zero', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);

    $attr = app(OwnerAttributionService::class);
    $attr->attributeReservation($reservation);

    $originals = OwnerLedgerEntry::where('reservation_id', $reservation->id)->whereNull('reverses_id')->get();
    $originalCount = $originals->count();
    $originalSum = (int) $originals->sum('amount_minor');

    $attr->reverseReservation($reservation);

    // Every original has a mirror reversal (flipped direction, reverses_id set).
    $reversals = OwnerLedgerEntry::where('reservation_id', $reservation->id)->whereNotNull('reverses_id')->get();
    expect($reversals->count())->toBe($originalCount);
    foreach ($originals as $o) {
        $mirror = $reversals->firstWhere('reverses_id', $o->id);
        expect($mirror)->not->toBeNull()
            ->and($mirror->direction)->toBe($o->direction === 'credit' ? 'debit' : 'credit')
            ->and($mirror->amount_minor)->toBe($o->amount_minor);
    }

    // Originals untouched; the booking nets to zero.
    expect((int) OwnerLedgerEntry::where('reservation_id', $reservation->id)->whereNull('reverses_id')->sum('amount_minor'))->toBe($originalSum)
        ->and(ownerNet($owner->id))->toBe(0);

    // Idempotent: reversing again creates nothing new.
    $before = OwnerLedgerEntry::where('reservation_id', $reservation->id)->count();
    $attr->reverseReservation($reservation);
    expect(OwnerLedgerEntry::where('reservation_id', $reservation->id)->count())->toBe($before);
});

it('re-attributes a modified booking by reversing the old version wholesale and posting a new one', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);

    $attr = app(OwnerAttributionService::class);
    $attr->attributeReservation($reservation);
    $attr->reattributeReservation($reservation);

    // v1 fully reversed, v2 fresh → net is the v2 figures only (not doubled).
    expect(ownerNet($owner->id))->toBe(20000 - 3000) // room − 15% commission, single version's worth
        ->and(OwnerLedgerEntry::where('reservation_id', $reservation->id)->where('attribution_version', 2)->whereNull('reverses_id')->count())->toBeGreaterThan(0)
        ->and(OwnerLedgerEntry::where('reservation_id', $reservation->id)->where('attribution_version', 1)->whereNotNull('reverses_id')->count())->toBeGreaterThan(0);
});

// ── 8. Null governing agreement on a covered date → nothing attributed + flagged ────────────────
it('attributes nothing and flags explicitly when no governing agreement covers the date', function () {
    $s = seedScenario(1);
    $owner = Owner::factory()->create();
    $s['units'][0]->update(['owner_id' => $owner->id]);
    // Agreement only becomes effective AFTER the stay → resolveFor() returns null on the booking date.
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'unit_id' => null, 'effective_from' => '2027-01-01']);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);

    app(OwnerAttributionService::class)->attributeReservation($reservation);

    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    expect(lineFor($owner->id, 'room_revenue'))->toBeNull()
        ->and(lineFor($owner->id, 'commission'))->toBeNull()
        ->and(lineFor($owner->id, 'adjustment')?->unverified)->toBeTrue()
        ->and(lineFor($owner->id, 'adjustment')?->note)->toBe('no_agreement')
        ->and(ownerNet($owner->id))->toBe(0);
});

// ── 9. Idempotency + read-only over operational data ────────────────────────────────────────────
it('is idempotent and never mutates folios, reservations, or availability', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);

    $folioTotalBefore = (int) Folio::where('reservation_id', $reservation->id)->sum('total_minor');
    $resTotalBefore = $reservation->fresh()->total_minor; // fresh — postRoom recompute updated the row
    $soldBefore = soldOn($s['roomType']->id, '2026-09-01');

    $attr = app(OwnerAttributionService::class);
    $attr->attributeReservation($reservation);
    $countAfterFirst = OwnerLedgerEntry::where('reservation_id', $reservation->id)->count();
    $attr->attributeReservation($reservation); // re-run

    expect(OwnerLedgerEntry::where('reservation_id', $reservation->id)->count())->toBe($countAfterFirst)
        ->and((int) Folio::where('reservation_id', $reservation->id)->sum('total_minor'))->toBe($folioTotalBefore)
        ->and($reservation->fresh()->total_minor)->toBe($resTotalBefore)
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe($soldBefore);
});

// ── 10. Fixed management fee proration ──────────────────────────────────────────────────────────
it('pro-rates the fixed management fee to the period', function () {
    $s = seedScenario(1);
    $owner = Owner::factory()->create(['payout_currency' => 'EUR']);
    $s['units'][0]->update(['owner_id' => $owner->id]);
    $agreement = ManagementAgreement::factory()->withManagementFee(30000, 'EUR', 'monthly')->create([
        'owner_id' => $owner->id, 'effective_from' => '2026-01-01',
    ]);

    $attr = app(OwnerAttributionService::class);
    $full = $attr->attributeManagementFee($agreement, '2026-09-01', '2026-09-30'); // 30 days ≈ full month
    expect($full->amount_minor)->toBe(30000)
        ->and($full->direction)->toBe('debit');

    // Idempotent on the period key.
    $again = $attr->attributeManagementFee($agreement, '2026-09-01', '2026-09-30');
    expect($again->id)->toBe($full->id);

    // A shorter period pro-rates down.
    $half = $attr->attributeManagementFee($agreement, '2026-10-01', '2026-10-15'); // 15 days
    expect($half->amount_minor)->toBe(15000);
});
