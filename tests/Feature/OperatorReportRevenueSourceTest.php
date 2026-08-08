<?php

declare(strict_types=1);

use App\Support\Reports\OperatorReportData;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * REGRESSION — the wireframe-parity sweep's HIGH-4.
 *
 * `OperatorReportData::topUnits()` summed `reservation_units.price_minor` while `revenue()` summed
 * `folio_items` of type `room`, so ONE screen printed two different "room revenue" figures for the same
 * period — measured live at €11,560 against €7,080, a 63% overstatement — and the PDF/CSV inherited it
 * because they build from the same assembler.
 *
 * The folio is authoritative for money (#17), so Top units now reads the folio and allocates it across the
 * reservation's active lines by their price weights. Σ(top units) equals the Revenue panel's room figure by
 * construction.
 *
 * The blind spot: nothing asserted the two panels agreed. Each was individually "real", which is exactly how
 * two sources for one concept survive review.
 */
function reportRange(): array
{
    return ['start' => CarbonImmutable::parse('2026-09-01'), 'end' => CarbonImmutable::parse('2026-10-01')];
}

it('makes Top units sum to the Revenue panel’s room figure', function () {
    $s = seedScenario(1);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);

    $data = OperatorReportData::build([(string) $s['property']->id], reportRange(), 'EUR');

    $topSum = array_sum(array_column($data['top_units'], 'revenue_minor'));

    expect($data['room_minor'])->toBe(20000)
        ->and($topSum)->toBe($data['room_minor']);   // ONE source — the panels cannot drift
});

it('splits a multi-unit booking’s folio across its units without inventing or losing a cent', function () {
    $s = seedScenario(2);

    // One reservation over two units, priced unevenly.
    $reservation = app(App\Services\AssignmentService::class)->createBooking(
        [
            'property_id' => $s['property']->id, 'currency' => 'EUR',
            'check_in' => '2026-09-01', 'check_out' => '2026-09-03',
            'lead_guest_id' => $s['guest']->id, 'source' => 'direct',
        ],
        [
            ['room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id, 'unit_id' => $s['units'][0]->id,
                'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'price_minor' => 12000, 'currency' => 'EUR'],
            ['room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id, 'unit_id' => $s['units'][1]->id,
                'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'price_minor' => 8000, 'currency' => 'EUR'],
        ],
    );
    postRoom($reservation, 20000);   // ONE folio for the whole booking

    $data = OperatorReportData::build([(string) $s['property']->id], reportRange(), 'EUR');
    $topSum = array_sum(array_column($data['top_units'], 'revenue_minor'));

    // Allocated 12000/8000 across the two units, and the parts still total the folio exactly (#17).
    expect($topSum)->toBe(20000)
        ->and($data['room_minor'])->toBe(20000)
        ->and(collect($data['top_units'])->pluck('revenue_minor')->sort()->values()->all())->toBe([8000, 12000]);
});

it('reports no unit revenue when the booking has no folio, rather than a phantom figure', function () {
    $s = seedScenario(1);

    // A booking created WITHOUT a folio — the shape DemoSeeder::seedAnalyticsHistory produces, and the reason
    // the two sources diverged. The stay-night price exists, but no money has been posted.
    book($s, ['unit_id' => $s['units'][0]->id]);

    $data = OperatorReportData::build([(string) $s['property']->id], reportRange(), 'EUR');
    $topSum = array_sum(array_column($data['top_units'], 'revenue_minor'));

    expect($data['room_minor'])->toBe(0)
        ->and($topSum)->toBe(0);   // agrees with the Revenue panel instead of reporting the unbilled price
});
