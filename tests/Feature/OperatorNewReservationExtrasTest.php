<?php

use App\Models\AddOn;
use App\Models\FolioItem;
use App\Models\Guest;
use App\Models\GuestCreditEntry;
use App\Models\Reservation;
use App\Models\Tax;
use App\Services\GuestWalletService;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #90 GATE 2 — the FULL New-Reservation wizard extends the certified createBooking path with REAL folio lines:
// extras (AddOn) + transfer + property taxes + returning-guest wallet credit. The folio must RECONCILE (#17):
// room + extras + transfer − credit + tax == the folio total, derived from the append-only ledger.

function extrasScenario(int $nightlyMinor = 10000): array
{
    $s = seedScenario(1);
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-10', Money::of($nightlyMinor, 'EUR'));
    $propertyId = $s['roomType']->property_id;

    $s['breakfast'] = AddOn::create(['property_id' => $propertyId, 'name' => 'Breakfast', 'price' => Money::of(1800, 'EUR'), 'taxable' => true, 'is_active' => true]);
    $s['cot'] = AddOn::create(['property_id' => $propertyId, 'name' => 'Baby cot', 'price' => Money::of(1500, 'EUR'), 'taxable' => false, 'is_active' => true]);
    $s['transfer'] = AddOn::create(['property_id' => $propertyId, 'name' => 'Airport transfer', 'price' => Money::of(6500, 'EUR'), 'taxable' => true, 'is_active' => true]);
    $s['vat'] = Tax::create(['property_id' => $propertyId, 'name' => 'VAT', 'kind' => 'vat', 'mode' => 'percentage', 'rate' => 10.0]);

    return $s;
}

function payload(array $s, array $overrides = []): array
{
    return array_merge([
        'check_in' => '2026-09-02', 'check_out' => '2026-09-05',   // 3 nights
        'room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id,
        'guest_first_name' => 'Grace', 'guest_last_name' => 'Hopper',
        'adults' => '2', 'children' => '0', 'source' => 'direct',
    ], $overrides);
}

// ── The folio RECONCILES with extras + transfer + tax (#17) ───────────────────────────────────────
it('posts real extras and taxes as folio lines that reconcile to the total', function () {
    $s = extrasScenario(10000);   // 3 nights × €100 = €300 room
    $manager = operatorUser($s);

    $this->actingAs($manager)->post('/app/reservations/create', payload($s, [
        'extras' => [$s['breakfast']->id, $s['cot']->id, $s['transfer']->id],
        'transfer_trip' => 'return',   // 2 legs
    ]))->assertRedirect();

    $reservation = Reservation::query()->latest('created_at')->firstOrFail();

    // Expected real lines: room 30000 + breakfast 1800×(2 guests×3 nights=6)=10800 + cot 1500×3=4500
    //                    + transfer 6500×2=13000. Taxable base = 30000+10800+13000 = 53800; VAT 10% = 5380.
    $room = 30000;
    $breakfast = 1800 * 6;
    $cot = 1500 * 3;
    $transfer = 6500 * 2;
    $vat = (int) round(($room + $breakfast + $transfer) * 0.10);
    $expected = $room + $breakfast + $cot + $transfer + $vat;

    // The folio total is the SUM of the append-only ledger — reconciles by construction.
    $ledgerSum = (int) FolioItem::query()->whereHas('folio', fn ($q) => $q->where('reservation_id', $reservation->id))->sum('amount_minor');
    expect((int) $reservation->total_minor)->toBe($expected)
        ->and($ledgerSum)->toBe($expected);
});

// ── Returning-guest credit is REAL (the guest's wallet balance), reducing the total ───────────────
it('applies the returning guest real wallet credit', function () {
    $s = extrasScenario(10000);
    $manager = operatorUser($s);

    // A returning guest with a real €50 wallet balance.
    $guest = Guest::create(['first_name' => 'Return', 'last_name' => 'Guest']);
    app(GuestWalletService::class)->credit($guest, GuestCreditEntry::TYPE_LOYALTY, 'Test credit', Money::of(5000, 'EUR'));

    $this->actingAs($manager)->post('/app/reservations/create', payload($s, [
        'guest_first_name' => '', 'guest_last_name' => '',   // use the existing guest
        'existing_guest_id' => $guest->id,
        'apply_credit' => '1',
    ]))->assertRedirect();

    $reservation = Reservation::query()->latest('created_at')->firstOrFail();
    // Room 30000 + VAT 10% (3000) = 33000, minus €50 credit = 30000 net; the credit line is real + negative.
    $ledgerSum = (int) FolioItem::query()->whereHas('folio', fn ($q) => $q->where('reservation_id', $reservation->id))->sum('amount_minor');
    expect((int) $reservation->total_minor)->toBe($ledgerSum)
        ->and(FolioItem::query()->whereHas('folio', fn ($q) => $q->where('reservation_id', $reservation->id))->where('amount_minor', '<', 0)->exists())->toBeTrue()
        ->and(app(GuestWalletService::class)->spendableMinor($guest->fresh()))->toBe(0);   // the wallet was really spent
});

// ── VIP + marketing consent are captured on a new guest (#86) ─────────────────────────────────────
it('captures VIP and marketing consent on a new guest', function () {
    $s = extrasScenario();
    $manager = operatorUser($s);

    $this->actingAs($manager)->post('/app/reservations/create', payload($s, [
        'guest_first_name' => 'Vip', 'guest_last_name' => 'Person',
        'guest_vip' => '1', 'guest_marketing_consent' => '1',
    ]))->assertRedirect();

    $guest = Guest::query()->where('first_name', 'Vip')->where('last_name', 'Person')->firstOrFail();
    expect((bool) $guest->is_vip)->toBeTrue()->and((bool) $guest->marketing_consent)->toBeTrue();
});

// ── A fabricated extra id (foreign / inactive) is silently skipped — no fake line ─────────────────
it('skips an extra that is not a real active add-on of the property', function () {
    $s = extrasScenario(10000);
    $manager = operatorUser($s);
    $inactive = AddOn::create(['property_id' => $s['roomType']->property_id, 'name' => 'Spa', 'price' => Money::of(9900, 'EUR'), 'taxable' => true, 'is_active' => false]);

    $this->actingAs($manager)->post('/app/reservations/create', payload($s, ['extras' => [$inactive->id, 'not-a-real-id']]))->assertRedirect();

    $reservation = Reservation::query()->latest('created_at')->firstOrFail();
    expect(FolioItem::query()->whereHas('folio', fn ($q) => $q->where('reservation_id', $reservation->id))->where('description', 'like', '%Spa%')->exists())->toBeFalse();
});
