<?php

use App\Models\MaintenanceTicket;
use App\Models\User;
use App\Services\MaintenanceCostService;
use App\Support\Money\MoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/** The CSV opens with a stated-unit line AND the column header before the first detail row. */
const MCC_CSV_PREAMBLE_LINES = 2;

/** Zero-based index of the Total column: Date, Work order, Unit, Category, Labor, Parts, Total, Currency. */
const MCC_CSV_TOTAL_COL = 6;

// THE MAINTENANCE COSTS SILENT CAP — reconciliation integrity.
//
// ⚠️ THE DEFECT WAS NOT A WRONG TOTAL. The totals sum EVERY resolved ticket in the period and always did;
// the DETAIL beneath them was silently capped at 60. So above 60 tickets a reader who summed the visible
// rows could not reach the total printed underneath them, and nothing in the output said why — a
// discrepancy that reads as a bug in the figures rather than as a truncated list.
//
// ⚠️⚠️ THE REGISTER NAMED ONE SURFACE; SWEEPING THE CALLERS FOUND FOUR (§N.27.1). CSV, PDF, the emailed PDF
// and the SCREEN all printed a reconciling Total under the same capped set. The resolution splits by what
// the surface IS: a DOWNLOAD exists to hold the detail, so the cap is removed and the total reconciles; a
// SCREEN legitimately previews, so it now says "showing 60 of N; the total reflects all N".
//
// ⚠️ THE ASSERTIONS READ THE REAL EXPORTED BYTES (DO24-1), and the total is verified against the FULL
// dataset computed raw — never against the capped set, which would agree with the bug.

const MCC_ROWS = 72;            // ⚠️ deliberately > the 60-row preview, or none of this is exercised

const MCC_LABOR = 1100;         // €11.00 per ticket

const MCC_PARTS = 400;          // €4.00 per ticket

/** An operator who may manage maintenance, plus MCC_ROWS costed resolved tickets. */
function mccSetup(int $rows = MCC_ROWS): array
{
    $s = seedScenario(1);
    $user = User::where('email', 'demo-staff@vyrobel-demo.test')->first() ?? operatorUser($s, [], 'Hotel Manager');

    for ($i = 0; $i < $rows; $i++) {
        MaintenanceTicket::create([
            'property_id' => $s['property']->id,
            'unit_id' => $s['units'][0]->id,
            'title' => 'Costed job '.$i,
            'category' => 'plumbing',
            'status' => 'resolved',
            'priority' => 'normal',
            'opened_at' => now()->subDays(($i % 25) + 1),
            'resolved_at' => now()->subDays($i % 25),
            'cost_minor' => MCC_LABOR + MCC_PARTS,
            'labor_minor' => MCC_LABOR,
            'parts_minor' => MCC_PARTS,
            'currency' => 'EUR',
        ]);
    }

    return ['s' => $s, 'user' => $user, 'rows' => $rows, 'session' => ['current_property_id' => $s['property']->id]];
}

/** ⚠️ RAW — the true totals, computed independently of the service under test (§N.18.1). */
function mccRawTotals(): array
{
    $r = DB::table('maintenance_tickets')->whereNotNull('cost_minor')->where('status', 'resolved')
        ->selectRaw('COUNT(*) c, SUM(cost_minor) t, SUM(COALESCE(labor_minor, cost_minor, 0)) l, SUM(COALESCE(parts_minor,0)) p')->first();

    return ['count' => (int) $r->c, 'total' => (int) $r->t, 'labor' => (int) $r->l, 'parts' => (int) $r->p];
}

/** ⚠️ THE REAL EXPORTED BYTES, not a view or a status code. */
function mccCsv(array $f): string
{
    $response = test()->actingAs($f['user'])->withSession($f['session'])
        ->get(route('app.maintenance.costs.csv'))->assertOk();

    ob_start();
    $response->baseResponse->sendContent();

    return (string) ob_get_clean();
}

// ── 1. ⚠️⚠️ THE CSV IS COMPLETE — every row exports, and the rows SUM to the printed total ───────
it('exports every line item and its rows sum to the printed total', function () {
    $f = mccSetup();
    $raw = mccRawTotals();

    // The fixture genuinely exceeds the preview cap — otherwise this test proves nothing.
    expect($raw['count'])->toBe(MCC_ROWS)
        ->and($raw['count'])->toBeGreaterThan(MaintenanceCostService::BILLABLE_PREVIEW);

    $csv = mccCsv($f);
    $lines = array_values(array_filter(explode("\n", trim($csv))));

    // the stated-unit line + the column header + every detail row + the totals row
    // ⚠️ The CSV gained a leading "Amounts, major units…" line when the export was made
    //    spreadsheet-safe. The GUARD is unchanged — every billable row must still appear.
    expect(count($lines))->toBe($raw['count'] + MCC_CSV_PREAMBLE_LINES + 1);

    // ⚠️ SUM THE VISIBLE ROWS THE WAY A READER WOULD — parse the last column back out of the bytes.
    $sum = 0;
    foreach (array_slice($lines, MCC_CSV_PREAMBLE_LINES, $raw['count']) as $line) {
        $cells = str_getcsv($line);
        // ⚠️ THE TOTAL COLUMN BY INDEX, NOT `end()`. The last cell is now the ISO currency code, and
        //    `(float) 'EUR'` is 0.0 — a sum loop that reads it would total zero and still "work" on an
        //    empty fixture. Index the column the header names.
        $sum += (int) round((float) $cells[MCC_CSV_TOTAL_COL] * 100);
    }

    // ⚠️ THE WHOLE POINT: the rows reconcile to the total beneath them, to the cent.
    expect($sum)->toBe($raw['total'])
        ->and($csv)->toContain(MoneyFormatter::csvAmount($raw['total']).',EUR');
});

// ── 2. ⚠️⚠️ THE TOTAL IS THE TRUE SUM — verified against ALL rows, never the capped set ─────────
it('prints a total that is the true sum of every row, not the capped preview', function () {
    $f = mccSetup();
    $raw = mccRawTotals();

    $csv = mccCsv($f);

    // ⚠️ What the capped set WOULD have summed to — the figure a broken total would show.
    $cappedTotal = MaintenanceCostService::BILLABLE_PREVIEW * (MCC_LABOR + MCC_PARTS);
    expect($cappedTotal)->not->toBe($raw['total']);          // the two are genuinely distinguishable

    expect($csv)->toContain(MoneyFormatter::csvAmount($raw['total']))
        ->and($csv)->toContain(MoneyFormatter::csvAmount($raw['labor']))
        ->and($csv)->toContain(MoneyFormatter::csvAmount($raw['parts']))
        // ⚠️ And no formatted figure survives anywhere — a money cell a spreadsheet cannot sum is the
        //    defect this export was fixed for.
        ->and($csv)->not->toContain('€');
});

// ── 3. ⚠️ ≤ 60 ROWS — the untruncated case still reconciles, and claims no truncation ───────────
it('reconciles trivially when the period holds fewer rows than the preview cap', function () {
    $f = mccSetup(5);
    $raw = mccRawTotals();

    $lines = array_values(array_filter(explode("\n", trim(mccCsv($f)))));

    expect($raw['count'])->toBe(5)
        ->and(count($lines))->toBe(5 + MCC_CSV_PREAMBLE_LINES + 1);   // preamble + 5 + totals
});

// ── 4. ⚠️⚠️ THE SCREEN SAYS IT IS A PREVIEW — the honest absence of reconciliation ──────────────
it('tells the reader on screen that the detail is capped and the total is not', function () {
    $f = mccSetup();
    $raw = mccRawTotals();

    $html = test()->actingAs($f['user'])->withSession($f['session'])
        ->get('/app/maintenance/costs')->assertOk()->getContent();

    // ⚠️ DERIVED FROM THE REAL NUMBERS, never hand-typed — the cap and the true count both come from the code.
    expect($html)->toContain('Showing the most recent '.MaintenanceCostService::BILLABLE_PREVIEW.' of '.$raw['count'].' line items')
        ->and($html)->toContain('reflects all '.$raw['count']);
});

// ── 5. ⚠️ …AND SAYS NOTHING WHEN THERE IS NOTHING TO SAY ───────────────────────────────────────
it('makes no truncation claim when the screen is showing everything', function () {
    $f = mccSetup(5);

    $html = test()->actingAs($f['user'])->withSession($f['session'])
        ->get('/app/maintenance/costs')->assertOk()->getContent();

    // A page that shows all 5 rows must not announce a truncation that did not happen.
    expect($html)->not->toContain('Showing the most recent')
        ->and($html)->toContain('Labour + parts reconcile to the total');   // the real recon line is untouched
});

// ── 6. ⚠️ THE SERVICE'S OWN CONTRACT — the count is independent of the cap ─────────────────────
it('reports the true line-item count regardless of the preview depth', function () {
    $f = mccSetup();
    $ids = [(string) $f['s']['property']->id];
    $costs = app(MaintenanceCostService::class);

    $preview = $costs->report($ids);              // capped at 60
    $complete = $costs->report($ids, null, null, null);

    // ⚠️ The capped report still KNOWS the true count — that is what lets a preview be honest.
    expect(count($preview['billable']))->toBe(MaintenanceCostService::BILLABLE_PREVIEW)
        ->and($preview['billable_total'])->toBe(MCC_ROWS)
        ->and(count($complete['billable']))->toBe(MCC_ROWS)
        // …and both report the SAME complete total, because the totals never were capped.
        ->and($preview['total_minor'])->toBe($complete['total_minor'])
        ->and($complete['total_minor'])->toBe(mccRawTotals()['total']);
});
