<?php

use App\Models\Fee;
use App\Models\Folio;
use App\Models\FolioItem;
use App\Models\Tax;
use App\Services\GroupBookingService;
use App\Support\Folio\FolioTotals;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #104 GATE 1b — a GROUP block now charges the property's taxes + non-optional fees, through the SAME shared
// PropertyCharges helper the single-reservation flow uses. Before, a block posted room charges ONLY, so it
// under-billed vs both the wireframe and the direct flow — a real #17 inconsistency across surfaces.

/** Configure a property with an exclusive VAT, a per-person-night city tax, and a flat cleaning fee. */
function configureCharges(string $propertyId): void
{
    Tax::create(['property_id' => $propertyId, 'name' => 'VAT', 'kind' => 'vat', 'mode' => 'percentage', 'rate' => 10.0, 'inclusive' => false]);
    Tax::create(['property_id' => $propertyId, 'name' => 'City tax', 'kind' => 'city_tax', 'mode' => 'per_person_night', 'amount_minor' => 200, 'currency' => 'EUR']);
    Fee::create(['property_id' => $propertyId, 'name' => 'Cleaning fee', 'kind' => 'cleaning', 'mode' => 'flat', 'amount_minor' => 4500, 'currency' => 'EUR', 'optional' => false]);
}

/** @return array{property_id:string,name:string,occasion:?string,billing_mode:string,status:string,check_in:string,check_out:string,currency:string,adults:int,children:int,organiser_guest_id:string,members:list<array{room_type_id:string,unit_id:string,rate_plan_id:?string,price_minor:int}>} */
function blockInput(array $s, string $billing): array
{
    return [
        'property_id' => $s['property']->id,
        'name' => 'Test Block',
        'occasion' => null,
        'billing_mode' => $billing,
        'status' => 'confirmed',
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-03',   // 2 nights
        'currency' => 'EUR',
        'adults' => 2,
        'children' => 0,
        'organiser_guest_id' => $s['guest']->id,
        'members' => [
            ['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'rate_plan_id' => $s['ratePlan']->id, 'price_minor' => 30000],
            ['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][1]->id, 'rate_plan_id' => $s['ratePlan']->id, 'price_minor' => 30000],
        ],
    ];
}

// room €300 + VAT 10% (€30) + city tax (2 persons × 2 nights × €2 = €8) + cleaning €45 = €383.00 per member.
const EXPECTED_MEMBER_MINOR = 30000 + 3000 + 800 + 4500;

it('charges each member folio the room + taxes + fees, reconciling to the cent (individual)', function () {
    $s = seedScenario(2);
    configureCharges($s['property']->id);

    $group = app(GroupBookingService::class)->create(blockInput($s, 'individual'));
    $members = $group->reservations()->get();

    expect($members)->toHaveCount(2);
    foreach ($members as $member) {
        expect((int) $member->refresh()->total_minor)->toBe(EXPECTED_MEMBER_MINOR);   // #17 reconciles

        // The folio genuinely carries room + tax + fee lines (not room alone).
        $folioId = Folio::query()->where('reservation_id', $member->id)->value('id');
        $types = FolioItem::query()
            ->where('folio_id', $folioId)
            ->where('amount_minor', '>', 0)
            ->pluck('type')->unique()->values()->all();
        expect($types)->toContain('room')->toContain('tax')->toContain('fee');
    }
});

it('bills a master block onto ONE folio carrying every member’s room + taxes + fees (master)', function () {
    $s = seedScenario(2);
    configureCharges($s['property']->id);

    $group = app(GroupBookingService::class)->create(blockInput($s, 'master'));
    $memberIds = $group->reservations()->pluck('id')->all();

    // Exactly one folio for the block; it carries BOTH members' full charges (2 × €383).
    $folios = Folio::query()->whereIn('reservation_id', $memberIds)->get();
    expect($folios)->toHaveCount(1);

    $charges = FolioTotals::for($folios->first())['charges_minor'];
    expect($charges)->toBe(EXPECTED_MEMBER_MINOR * 2);   // #17 reconciles across the whole block
});

it('adds nothing when the property has no taxes or fees (room only — unchanged)', function () {
    $s = seedScenario(2);   // no taxes/fees configured

    $group = app(GroupBookingService::class)->create(blockInput($s, 'individual'));

    foreach ($group->reservations()->get() as $member) {
        expect((int) $member->refresh()->total_minor)->toBe(30000);   // room only, exactly as before
    }
});
