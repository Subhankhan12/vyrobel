<?php

use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\MaintenanceCostService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader;

uses(RefreshDatabase::class);

// D12b-1 — the Maintenance Costs page exports a real workbook: a Summary tab and a Line items tab.
//
// ⚠️ THE EXPORT CARRIES NO SCOPE OF ITS OWN. Both tabs come from ONE report() call resolved through the SAME
// private scope() the screen, the CSV and the PDF use — property scope from PropertyContext, period from the
// `?period=` preset. A filter exports its subset because there is nowhere else for the period to come from.
//
// ⚠️⚠️ THE TRAP CONFIRM-FIRST CAUGHT, AND THE REASON TEST 2 EXISTS. `report()` sums cost over EVERY resolved
// ticket in the window, while `billable` was capped at 60 rows for the screen's preview. That is fine for a
// page and a DEFECT in a workbook: above 60 tickets the Summary tab would silently exceed the Line items tab
// beneath it. The workbook asks for the complete set (`billableLimit: null`), so its summary reconciles to
// its own detail. Test 2 deliberately creates MORE than 60 costed tickets — a 3-ticket fixture would pass
// whether or not the cap was addressed.

const MCW_PERIOD_TICKETS = 63;   // ⚠️ deliberately > the 60-row screen preview

/** An operator who may manage maintenance (the page's own gate), plus a unit to cost against. */
function mcwSetup(): array
{
    $s = seedScenario(2);
    $manager = operatorUser($s, [], 'Hotel Manager');   // holds maintenance.manage

    return ['s' => $s, 'user' => $manager, 'session' => ['current_property_id' => $s['property']->id]];
}

/** A resolved, costed work order — the population the report sums and the line items list. */
function mcwTicket(array $f, int $costMinor, string $resolvedAt, ?Unit $unit = null, string $title = 'Fix'): MaintenanceTicket
{
    return MaintenanceTicket::create([
        'organization_id' => $f['s']['org']->id,
        'unit_id' => ($unit ?? $f['s']['units'][0])->id,
        'title' => $title,
        'category' => 'plumbing',
        'priority' => 'normal',
        'status' => 'resolved',
        'opened_at' => CarbonImmutable::parse($resolvedAt)->subDay(),
        'resolved_at' => $resolvedAt,
        'cost_minor' => $costMinor,
        'labor_minor' => $costMinor,
        'parts_minor' => 0,
        'currency' => 'EUR',
    ]);
}

/** ⚠️ Read the real bytes back — cells and their PHP types, never a status code. */
function mcwDownload($test, array $f, string $query = ''): array
{
    $response = $test->actingAs($f['user'])->withSession($f['session'])
        ->get('/app/maintenance/costs/export.xlsx'.$query);
    $response->assertOk();

    $path = $response->baseResponse->getFile()->getPathname();
    $reader = new Reader;
    $reader->open($path);

    $sheets = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        $rows = [];
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = $row->toArray();
        }
        $sheets[$sheet->getName()] = $rows;
    }
    $reader->close();
    // The test client never performs a real send(), so deleteFileAfterSend never fires here.
    @unlink($path);

    return $sheets;
}

/** The Line items tab's data rows (header dropped, the trailing Total row dropped). */
function mcwLineItems(array $sheets): array
{
    $rows = array_slice($sheets['Line items'], 1);

    return array_values(array_filter($rows, fn (array $r): bool => ($r[1] ?? '') !== 'Total'));
}

/** A labelled value from the Summary tab's Measure/Value block. */
function mcwSummary(array $sheets, string $measure): mixed
{
    foreach ($sheets['Summary'] as $row) {
        if (($row[0] ?? null) === $measure) {
            return $row[1];
        }
    }

    return null;
}

// ── 1. Both tabs exist, named, with the view's columns ────────────────────────────────────────────
it('writes a Summary tab and a Line items tab with the page columns', function () {
    $f = mcwSetup();
    mcwTicket($f, 18000, CarbonImmutable::today()->subDays(3)->toDateString());

    $sheets = mcwDownload($this, $f);

    expect(array_keys($sheets))->toBe(['Summary', 'Line items'])
        ->and($sheets['Line items'][0])->toBe(['Date', 'Work order', 'Unit', 'Category', 'Labour', 'Parts', 'Total'])
        // ⚠️ The sheet pads every row to the widest one (the breakdown blocks are 3 columns), so the
        // header arrives as ['Measure','Value','']. Compare the cells that carry meaning.
        ->and(array_slice($sheets['Summary'][0], 0, 2))->toBe(['Measure', 'Value']);
});

// ── 2. ⚠️⚠️ THE SUMMARY RECONCILES TO ITS OWN DETAIL — above the screen's 60-row preview ──────────
it('reconciles the summary total to the line items even beyond the screen preview', function () {
    $f = mcwSetup();

    // ⚠️ MORE than the 60-row preview. With three tickets this test passes whether or not the cap was
    // addressed — the defect only appears once the detail would have been truncated.
    $expectedMinor = 0;
    for ($i = 0; $i < MCW_PERIOD_TICKETS; $i++) {
        $cost = 1000 + $i;                     // distinct amounts, so a dropped row changes the sum
        $expectedMinor += $cost;
        mcwTicket($f, $cost, CarbonImmutable::today()->subDays(($i % 25) + 1)->toDateString(), null, 'WO '.$i);
    }

    $sheets = mcwDownload($this, $f, '?period=6m');
    $items = mcwLineItems($sheets);

    // ⚠️ THE COMPLETE DETAIL, not the preview.
    expect($items)->toHaveCount(MCW_PERIOD_TICKETS)
        ->and(count($items))->toBeGreaterThan(MaintenanceCostService::BILLABLE_PREVIEW);

    // ⚠️ AND THE TWO TABS AGREE, to the cent (#17).
    // ⚠️ COMPARED TO THE CENT, NOT BY FLOAT IDENTITY. Each cell is an exact amount (minor ÷ 100), but
    // accumulating 63 of them in binary drifts by ~1e-13 — Excel's own SUM() does the same. #17 means "to
    // the cent", and asserting float equality here would be testing IEEE-754, not the export.
    $detailMinor = (int) round(array_sum(array_map(fn (array $r): float => (float) $r[6], $items)) * 100);
    expect($detailMinor)->toBe($expectedMinor)
        ->and((int) round((float) mcwSummary($sheets, 'Total spend') * 100))->toBe($detailMinor);

    // The Line items tab's own trailing Total row agrees with both.
    $totalRow = collect($sheets['Line items'])->first(fn (array $r): bool => ($r[1] ?? '') === 'Total');
    expect((int) round((float) $totalRow[6] * 100))->toBe($detailMinor);
});

// ── 3. ⚠️⚠️ THE PERIOD FILTER EXPORTS ITS SUBSET — never the whole table ──────────────────────────
it('exports only the selected period, never every costed ticket', function () {
    $f = mcwSetup();
    mcwTicket($f, 5000, CarbonImmutable::today()->subDays(10)->toDateString(), null, 'Recent');
    mcwTicket($f, 90000, CarbonImmutable::today()->subMonths(9)->toDateString(), null, 'Ancient');

    // ⚠️ THE RAW TABLE — what an unfiltered export would have carried.
    $wholeTableMinor = (int) DB::table('maintenance_tickets')->whereNotNull('cost_minor')->sum('cost_minor');
    expect($wholeTableMinor)->toBe(95000);

    $sheets = mcwDownload($this, $f, '?period=3m');
    $items = mcwLineItems($sheets);

    // Only the in-window ticket; the 9-month-old €900 is excluded though it exists.
    expect($items)->toHaveCount(1)
        ->and((float) $items[0][6])->toBe(50.0)
        ->and((float) mcwSummary($sheets, 'Total spend'))->toBe(50.0)
        ->and((float) mcwSummary($sheets, 'Total spend'))->not->toBe(950.0);

    // ⚠️ And "all" DOES carry both — so the difference above is the filter working, not thin data.
    $all = mcwDownload($this, $f, '?period=all');
    expect((float) mcwSummary($all, 'Total spend'))->toBe(950.0);
});

// ── 4. ⚠️⚠️ PROPERTY SCOPE — another property's costs never reach this workbook ───────────────────
it('excludes costs from a property outside the selected scope', function () {
    $f = mcwSetup();
    mcwTicket($f, 5000, CarbonImmutable::today()->subDays(2)->toDateString(), null, 'Mine');

    // A real second property in the same org, with its own, larger cost.
    $second = Property::create(['name' => 'H2', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $roomType = RoomType::create(['property_id' => $second->id, 'name' => 'Std', 'kind' => 'room']);
    $otherUnit = Unit::create(['property_id' => $second->id, 'room_type_id' => $roomType->id, 'code' => 'X1']);
    mcwTicket($f, 77000, CarbonImmutable::today()->subDays(2)->toDateString(), $otherUnit, 'Not mine');

    // ⚠️ PREMISE FIRST: €820 genuinely exists on the raw table and is genuinely available to leak.
    expect((int) DB::table('maintenance_tickets')->whereNotNull('cost_minor')->sum('cost_minor'))->toBe(82000);

    $sheets = mcwDownload($this, $f, '?period=all');
    $items = mcwLineItems($sheets);

    expect($items)->toHaveCount(1)
        ->and($items[0][1])->toBe('Mine')
        ->and((float) mcwSummary($sheets, 'Total spend'))->toBe(50.0)
        ->and((float) mcwSummary($sheets, 'Total spend'))->not->toBe(820.0);
});

// ── 5. ⚠️ MONEY IS NUMERIC AND SUMS — mirroring the screen's minor-integer values (§G.1) ──────────
it('writes money as numbers that sum and match the service to the cent', function () {
    $f = mcwSetup();
    mcwTicket($f, 12345, CarbonImmutable::today()->subDays(4)->toDateString(), null, 'A');
    mcwTicket($f, 6789, CarbonImmutable::today()->subDays(5)->toDateString(), null, 'B');

    $sheets = mcwDownload($this, $f, '?period=all');
    $items = mcwLineItems($sheets);

    foreach ($items as $row) {
        // ⚠️ THE DEFECT THIS PINS: "€123.45" as a STRING looks identical in the cell and will not sum.
        expect($row[4])->toBeNumeric()->and($row[4])->not->toBeString()   // Labour
            ->and($row[5])->toBeNumeric()                                 // Parts
            ->and($row[6])->toBeNumeric()->and($row[6])->not->toBeString(); // Total
    }

    $sum = array_sum(array_map(fn (array $r): float => (float) $r[6], $items));
    expect($sum)->toBe(191.34);

    // ⚠️ NOT RE-PRICED — the same integer minor amounts the screen renders, merely typed (§G.1).
    $report = app(MaintenanceCostService::class)->report([$f['s']['property']->id], null, null, null);
    expect((int) round($sum * 100))->toBe((int) $report['total_minor']);
});

// ── 6. ⚠️ DATES ARE REAL DATE CELLS — sortable, unlike the screen's "5 Sep" ───────────────────────
it('writes work-order dates and months as date cells', function () {
    $f = mcwSetup();
    mcwTicket($f, 5000, CarbonImmutable::today()->subDays(6)->toDateString());

    $sheets = mcwDownload($this, $f, '?period=all');

    expect(mcwLineItems($sheets)[0][0])->toBeInstanceOf(DateTimeInterface::class);

    // The Summary's "By month" block carries date cells too.
    $monthRows = array_values(array_filter($sheets['Summary'], fn (array $r): bool => ($r[0] ?? null) instanceof DateTimeInterface));
    expect($monthRows)->not->toBeEmpty();
});

// ── 7. ⚠️ THE UNRENDERED BRANCH — an empty period is a valid header-only workbook, not a crash ────
it('produces a valid header-only workbook when nothing was costed', function () {
    $f = mcwSetup();   // no tickets at all

    $sheets = mcwDownload($this, $f, '?period=3m');

    expect($sheets)->toHaveKey('Summary')->toHaveKey('Line items')
        ->and($sheets['Line items'][0])->toBe(['Date', 'Work order', 'Unit', 'Category', 'Labour', 'Parts', 'Total'])
        ->and(mcwLineItems($sheets))->toBeEmpty()
        // ⚠️ An average over zero orders is honestly absent, never a fabricated 0.00.
        ->and(mcwSummary($sheets, 'Average per order'))->toBe('—');
});

// ── 8. ⚠️ RBAC — gated on maintenance.manage, the page's own capability (not reports.view) ────────
it('refuses an operator without maintenance.manage', function () {
    $f = mcwSetup();
    $desk = operatorUser($f['s'], [], 'Front Desk');

    test()->actingAs($desk)->withSession($f['session'])
        ->get('/app/maintenance/costs/export.xlsx')
        ->assertForbidden();
});

// ── 9. ⚠️ AUDITED like its siblings — who exported, never the values ──────────────────────────────
it('audits the workbook export', function () {
    $f = mcwSetup();
    mcwTicket($f, 5000, CarbonImmutable::today()->subDays(2)->toDateString());

    mcwDownload($this, $f, '?period=all');

    $row = DB::table('audit_events')->where('action', 'maintenance.costs_exported')->latest('id')->first();
    expect($row)->not->toBeNull()
        ->and((string) $row->after)->toContain('xlsx');
});

// ── 10. ⚠️ NO REGRESSION — the screen's 60-row preview and the CSV are untouched ──────────────────
it('leaves the screen preview capped, and the CSV spreadsheet-safe', function () {
    $f = mcwSetup();
    for ($i = 0; $i < MCW_PERIOD_TICKETS; $i++) {
        mcwTicket($f, 1000, CarbonImmutable::today()->subDays(($i % 25) + 1)->toDateString(), null, 'WO '.$i);
    }

    // ⚠️ The DEFAULT call — what the page and the CSV/PDF still get — is still the 60-row preview.
    $screen = app(MaintenanceCostService::class)->report([$f['s']['property']->id], null, null);
    expect($screen['billable'])->toHaveCount(MaintenanceCostService::BILLABLE_PREVIEW);

    // ⚠️ THIS ASSERTION USED TO PIN THE CSV'S FORMATTED SHAPE, on the reasoning that "a CSV's cells are
    //    text by nature". That reasoning was the defect: it accepted a money column no spreadsheet could
    //    sum because the workbook existed beside it. The CSV now emits bare decimals and an ISO currency
    //    column, so BOTH formats are usable and neither teaches the operator to distrust the other.
    $csv = test()->actingAs($f['user'])->withSession($f['session'])
        ->get('/app/maintenance/costs/export.csv')->assertOk()->streamedContent();
    expect($csv)->toContain('Date,"Work order"')
        ->and($csv)->toContain('10.00,EUR')
        ->and($csv)->not->toContain('€');
});
