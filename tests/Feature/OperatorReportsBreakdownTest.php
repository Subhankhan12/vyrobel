<?php

use App\Models\Reservation;
use App\Services\FolioService;
use App\Support\Money\Money;
use App\Support\Reports\OperatorRevenueBreakdown;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #104 GATE 2 — the Reports revenue-by-channel table + revenue-by-month chart are REAL aggregates that reconcile
// to the report's gross (folio basis), and the CSV export streams the SAME figures (shared OperatorRevenueBreakdown).

/** A confirmed reservation arriving this year with a real folio room charge. */
function reservationWithRoom(array $s, string $source, int $roomMinor, string $reference): Reservation
{
    $res = Reservation::create([
        'property_id' => $s['property']->id,
        'reference' => $reference,
        'status' => 'confirmed',
        'source' => $source,
        'lead_guest_id' => $s['guest']->id,
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-03',
        'currency' => 'EUR',
    ]);
    $folio = app(FolioService::class)->openFolio($res);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of($roomMinor, 'EUR'));

    return $res;
}

it('breaks revenue down by channel + month, reconciling to the folio total (#17)', function () {
    $s = seedScenario(2);
    reservationWithRoom($s, 'direct', 30000, 'VYR-D1');
    reservationWithRoom($s, 'ota', 20000, 'VYR-O1');
    $ids = Reservation::query()->pluck('id')->map(fn ($id): string => (string) $id)->all();

    $channels = OperatorRevenueBreakdown::byChannel($ids, 'EUR');

    // The channel split reconciles to the €500 gross to the cent, richest first, with correct shares.
    expect(array_sum(array_column($channels, 'revenue_minor')))->toBe(50000)
        ->and($channels[0]['source'])->toBe('direct')
        ->and($channels[0]['revenue_minor'])->toBe(30000)
        ->and($channels[0]['share'])->toBe(60)
        ->and($channels[1]['source'])->toBe('ota')
        ->and($channels[1]['share'])->toBe(40);

    // The month buckets sum to the same gross (Sep carries both).
    $months = OperatorRevenueBreakdown::byMonth($ids, 'EUR', CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2027-01-01'));
    expect(array_sum(array_column($months, 'revenue_minor')))->toBe(50000);
    expect(collect($months)->firstWhere('month', '2026-09')['revenue_minor'])->toBe(50000);
});

it('renders the channel table + month chart and exports a real CSV', function () {
    $s = seedScenario(1);
    reservationWithRoom($s, 'direct', 30000, 'VYR-D2');
    $manager = operatorUser($s, [], 'Hotel Manager');   // holds reports.view
    $session = ['current_property_id' => $s['property']->id];

    $this->actingAs($manager)->withSession($session)->get(route('app.reports'))
        ->assertOk()
        ->assertSee('Revenue by channel')
        ->assertSee('Revenue by month')
        ->assertSee('Direct');

    $csv = $this->actingAs($manager)->withSession($session)->get(route('app.reports.export', ['range' => 'this-year']));
    $csv->assertOk();
    expect($csv->headers->get('content-type'))->toContain('text/csv');
    expect($csv->streamedContent())->toContain('Revenue by channel')->toContain('Direct');
});

it('refuses the export to a role without reports.view', function () {
    $s = seedScenario(1);
    $frontDesk = operatorUser($s, [], 'Front Desk');   // lacks reports.view
    $this->actingAs($frontDesk)->withSession(['current_property_id' => $s['property']->id])
        ->get(route('app.reports.export'))->assertForbidden();
});

// GATE 10.1 — the page leads with the REAL performance KPIs + a Reporting-month picker.
it('leads with real performance KPIs (occupancy · ADR · RevPAR · gross) and a month picker', function () {
    $s = seedScenario(1);
    reservationWithRoom($s, 'direct', 30000, 'VYR-KPI');
    $manager = operatorUser($s, [], 'Hotel Manager');

    $this->actingAs($manager)->withSession(['current_property_id' => $s['property']->id])
        ->get(route('app.reports'))
        ->assertOk()
        ->assertSee('Occupancy')
        ->assertSee('ADR')
        ->assertSee('RevPAR')
        ->assertSee('Gross revenue')
        ->assertSee('Jump to month');   // the Reporting-month picker
});

// GATE 10.1 — ?month=YYYY-MM scopes the whole report (page + subtitle) to that calendar month.
it('scopes the report to a specific reporting month via ?month=', function () {
    $s = seedScenario(1);
    reservationWithRoom($s, 'direct', 30000, 'VYR-SEP');   // arrives 2026-09-01
    $manager = operatorUser($s, [], 'Hotel Manager');
    $session = ['current_property_id' => $s['property']->id];

    // September has the arrival → its gross shows; a different month is empty (honest, not fabricated).
    $this->actingAs($manager)->withSession($session)->get(route('app.reports', ['month' => '2026-09']))
        ->assertOk()
        ->assertSee('arrivals in September 2026')
        ->assertSee('€300.00');

    $this->actingAs($manager)->withSession($session)->get(route('app.reports', ['month' => '2026-07']))
        ->assertOk()
        ->assertSee('arrivals in July 2026')
        ->assertSee('No arrivals or costs');   // real empty state for a month with nothing
});
