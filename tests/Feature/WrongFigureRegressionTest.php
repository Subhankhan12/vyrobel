<?php

declare(strict_types=1);

use App\Livewire\Operator\OperatorDashboard;
use App\Livewire\Owner\OwnerReports;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AvailabilityService;
use App\Services\FolioService;
use App\Support\Money\Money;
use App\Support\Property\PropertyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * REGRESSION — wireframe-parity sweep WAVE 2, the two items that printed a WRONG FIGURE rather than
 * wrong words (#17 and #21). Both surfaced under a heading that asserted something the number wasn't.
 */

/**
 * #17 — "Revenue today" was the ONLY dashboard KPI that ignored the property scope.
 *
 * Every sibling (occupancy, arrivals, departures, housekeeping, work orders, ADR) is built from
 * `$propertyIds`; `paymentsOn()` took only a date and summed payments across the WHOLE ORG. So with one
 * property selected the page showed that property's occupancy beside the entire org's takings. Latent
 * while only one property trades on a given day — wrong the moment two do (#38).
 */
it('scopes "Revenue today" to the selected property, not the whole organization', function () {
    $a = seedScenario(1);

    // A SECOND property in the SAME org (seedScenario always makes a new org, so build it here) with its
    // own room type, unit, rate plan and availability — a real second property, not a stub.
    $p2 = Property::create(['name' => 'H2', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $rt2 = RoomType::create(['property_id' => $p2->id, 'name' => 'Std2', 'kind' => 'room']);
    $u2 = Unit::create(['property_id' => $p2->id, 'room_type_id' => $rt2->id, 'code' => 'B1']);
    $rp2 = RatePlan::create(['property_id' => $p2->id, 'room_type_id' => $rt2->id, 'name' => 'Flex', 'currency' => 'EUR']);
    app(AvailabilityService::class)->recompute($rt2->id, '2026-09-01', '2026-09-10');
    $b = ['property' => $p2, 'roomType' => $rt2, 'ratePlan' => $rp2, 'guest' => $a['guest'], 'units' => [$u2]];

    $today = now()->toDateString();

    $folios = app(FolioService::class);
    foreach ([[$a, 10000], [$b, 25000]] as [$scenario, $minor]) {
        $reservation = book($scenario, ['unit_id' => $scenario['units'][0]->id]);
        $folio = $folios->openFolio($reservation);
        $folios->postCharge($folio, 'room', 'Room', new Money($minor, 'EUR'));
        $payment = $folios->recordPayment($folio, new Money($minor, 'EUR'));
        $payment->forceFill(['processed_at' => $today])->save();
    }

    // Select property A in the real PropertyContext (that is how the dashboard scopes — there is no
    // mount parameter; the concern captures the context at mount).
    app(PropertyContext::class)->activate(
        [(string) $a['property']->id, (string) $p2->id],
        (string) $a['property']->id,
    );

    // Viewing property A only: €100.00 — NOT €350.00.
    $kpis = Livewire::actingAs(operatorUser($a))
        ->test(OperatorDashboard::class)
        ->viewData('kpis');

    expect($kpis['payments'])->toContain('100.00')
        ->and($kpis['payments'])->not->toContain('350.00');   // the org-wide total the bug printed
});

/**
 * #21 — "Latest payout" was ordered by `created_at` alone.
 *
 * Payouts are written in batches, so every demo payout shares an identical `created_at` to the second. The
 * sort was therefore a full TIE and `->first()` returned an arbitrary row — in practice the OLDEST payout,
 * under a heading that says "Latest". Measured on the demo data: it printed April's €442.80 while the real
 * latest was August's €721.60.
 */
it('picks the genuinely latest payout when every created_at is tied', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);

    $stamp = now();   // IDENTICAL created_at on all three — the tie that broke the ordering
    foreach ([['2026-04-14', 44280], ['2026-08-23', 72160], ['2026-06-20', 46740]] as [$paidAt, $minor]) {
        $statement = OwnerStatement::create([
            'organization_id' => $s['org']->id,
            'owner_id' => $owner->id,
            'period_start' => $paidAt,
            'period_end' => $paidAt,
            'currency' => 'EUR',
            'source_currency' => 'EUR',
        ]);

        Payout::create([
            'organization_id' => $s['org']->id,
            'owner_id' => $owner->id,
            'owner_statement_id' => $statement->id,
            'amount_minor' => $minor,
            'currency' => 'EUR',
            'status' => 'paid',
            'paid_at' => $paidAt,
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ]);
    }

    // Pin the precondition: ordering by created_at alone is a full tie and cannot identify the latest.
    expect(Payout::query()->pluck('created_at')->unique())->toHaveCount(1);

    // Go through the REAL component — asserting the corrected query here would just restate the fix.
    $payout = Livewire::actingAs(ownerRoleUser($s))
        ->test(OwnerReports::class)
        ->viewData('payout');

    // August's €721.60, not April's €442.80 that the tied sort returned under a "Latest payout" heading.
    expect($payout['amount'])->toContain('721.60')
        ->and($payout['amount'])->not->toContain('442.80')
        ->and($payout['date'])->toBe('23 Aug 2026');
});
