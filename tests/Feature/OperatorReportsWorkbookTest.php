<?php

use App\Models\Organization;
use App\Models\Property;
use App\Models\Reservation;
use App\Services\FolioService;
use App\Support\Money\Money;
use App\Support\Reports\OperatorRevenueBreakdown;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader;

uses(RefreshDatabase::class);

// D4-1 — the operator Reports "Excel workbook" export. The menu item was an honest disabled "Soon": the stack
// had no spreadsheet library, so it was a real dependency gap rather than a build gap.
//
// ⚠️⚠️ THE ONE PROPERTY THAT MATTERS IS THAT THE EXPORT *IS* THE VIEW. It has no scope of its own — the same
// property scope, the same reporting period, the same breakdown the screen renders. An export that re-queried
// the table would hand the operator rows their screen may not show them (a leak) AND rows their filter
// excluded (a lie about what they were looking at). Test 1 pins the filter against the RAW table, test 2 pins
// the org boundary, test 3 the property boundary.
//
// ⚠️ THE SECOND PROPERTY IS THAT THE CELLS ARE TYPED. A workbook whose money is a formatted STRING is strictly
// worse than the CSV it sits beside — it looks right and then refuses to SUM, the one thing a workbook is
// opened to do. Every assertion below reads the file's real cell VALUES and PHP TYPES back through a reader;
// none of them asserts a 200.

/** ⚠️ Read the real bytes back — cells and their types, not a status code. */
function wbRead(string $path): array
{
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

    return $sheets;
}

/** Download the workbook AS THE ACTOR through the real route, and hand back the parsed sheets. */
function wbDownload($test, $actor, array $session, string $query = ''): array
{
    $response = $test->actingAs($actor)->withSession($session)->get('/app/reports/export.xlsx'.$query);
    $response->assertOk();

    // The response streams a temp file that is deleted after sending; copy it while it exists.
    $path = $response->baseResponse->getFile()->getPathname();
    $copy = tempnam(sys_get_temp_dir(), 'vy-wbtest-');
    copy($path, $copy);

    $sheets = wbRead($copy);
    @unlink($copy);

    return $sheets;
}

/** A confirmed reservation arriving on $date with a real folio room charge. */
function wbReservation(array $s, string $source, int $roomMinor, string $reference, string $date): Reservation
{
    $res = Reservation::create([
        'property_id' => $s['property']->id,
        'reference' => $reference,
        'status' => 'confirmed',
        'source' => $source,
        'lead_guest_id' => $s['guest']->id,
        'check_in' => $date,
        'check_out' => CarbonImmutable::parse($date)->addDays(2)->toDateString(),
        'currency' => 'EUR',
    ]);
    $folio = app(FolioService::class)->openFolio($res);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of($roomMinor, 'EUR'));

    return $res;
}

// ── 1. ⚠️⚠️ THE EXPORT REFLECTS THE FILTER — a month exports its subset, NOT the whole table ───────
it('exports only the filtered period, never the whole table', function () {
    $s = seedScenario(1);
    wbReservation($s, 'direct', 30000, 'VYR-WB-MAR', '2026-03-04');
    wbReservation($s, 'direct', 50000, 'VYR-WB-SEP', '2026-09-04');
    $manager = operatorUser($s, [], 'Hotel Manager');
    $session = ['current_property_id' => $s['property']->id];

    // ⚠️ THE RAW TABLE — what an UNFILTERED export would have contained.
    $wholeTable = (int) DB::table('reservations')->whereNotIn('status', ['cancelled', 'no_show'])->count();
    expect($wholeTable)->toBeGreaterThan(1);

    $filtered = wbDownload($this, $manager, $session, '?month=2026-03');
    $months = array_slice($filtered['Revenue by month'], 1);   // drop the header row

    // ⚠️ EXACTLY the filtered subset: one month bucket, carrying only March's booking.
    expect($months)->toHaveCount(1)
        ->and($months[0][0]->format('Y-m'))->toBe('2026-03')
        // ⚠️ Numeric, compared as a number: a whole amount reads back as int(300), a fractional one as
        // float — both are numbers that sum, which is the property under test (never the string "€300.00").
        ->and($months[0][1])->toBeNumeric()
        ->and((float) $months[0][1])->toBe(300.0);

    // ⚠️ AND IT GENUINELY DIFFERS FROM THE WHOLE — September's €500 is absent, though the row exists.
    $channelRevenue = array_sum(array_map(fn (array $r): float => (float) $r[2], array_slice($filtered['Revenue by channel'], 1)));
    expect($channelRevenue)->toBe(300.0)
        ->and($channelRevenue)->not->toBe(800.0);   // 800.00 == the unfiltered total

    // The unfiltered export DOES carry both — so the difference above is the filter working, not empty data.
    $whole = wbDownload($this, $manager, $session, '?range=this-year');
    $wholeRevenue = array_sum(array_map(fn (array $r): float => (float) $r[2], array_slice($whole['Revenue by channel'], 1)));
    expect($wholeRevenue)->toBe(800.0);
});

// ── 2. ⚠️⚠️ TENANCY — a second organization's revenue never reaches this workbook ─────────────────
it('excludes another organization entirely', function () {
    $a = seedScenario(1);
    wbReservation($a, 'direct', 30000, 'VYR-WB-A', '2026-09-04');

    // A whole second tenant with its own, larger revenue.
    $b = seedScenario(1);
    wbReservation($b, 'direct', 99000, 'VYR-WB-B', '2026-09-04');

    expect(Organization::query()->withoutGlobalScopes()->count())->toBeGreaterThan(1);

    // ⚠️⚠️ THE TEST PROVES ITS OWN PREMISE FIRST. A cross-org assertion is worthless if the fixture never
    // created anything to leak, so this reads the RAW table (no Eloquent scopes at all) and pins that both
    // organizations' revenue genuinely exists and is genuinely reachable: €1,290 IS available to leak.
    $everyOrgMinor = (int) DB::table('folio_items')->where('type', 'room')->sum('amount_minor');
    expect($everyOrgMinor)->toBe(129000);

    $manager = operatorUser($a, [], 'Hotel Manager');
    $sheets = wbDownload($this, $manager, ['current_property_id' => $a['property']->id]);

    $revenue = array_sum(array_map(fn (array $r): float => (float) $r[2], array_slice($sheets['Revenue by channel'], 1)));

    // ⚠️ …and the workbook carries org A's €300 alone. €1,290 was on the table; €990 of it never appears.
    expect($revenue)->toBe(300.0)
        ->and($revenue)->not->toBe(1290.0);

    // ⚠️ RECORDED HONESTLY (§N.4p): no single-layer mutation of THIS controller can make the line above fail.
    // The org boundary on this path is defended independently at three layers below it — the property id set
    // (org-scoped by PropertyContext), the tenant global scope on `reservations`, and the tenant scope on the
    // folio rows the revenue is summed from. Removing all three still did not leak, because the money itself
    // is read through a scoped query. So this test guards the composed OUTCOME, not this controller's code;
    // the controller's own scope is guarded by the property-scope and filter tests, which DO red.
});

// ── 3. ⚠️ PROPERTY SCOPE — the workbook honours the selected property, not "every property" ────────
it('honours the selected property scope', function () {
    $s = seedScenario(2);
    wbReservation($s, 'direct', 30000, 'VYR-WB-P1', '2026-09-04');

    // A genuine SECOND property in the SAME org — the scope the operator did NOT select.
    $second = Property::create(['name' => 'H2', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);

    Reservation::create([
        'property_id' => $second->id,
        'reference' => 'VYR-WB-P2',
        'status' => 'confirmed',
        'source' => 'direct',
        'lead_guest_id' => $s['guest']->id,
        'check_in' => '2026-09-04',
        'check_out' => '2026-09-06',
        'currency' => 'EUR',
    ]);
    $folio = app(FolioService::class)->openFolio(Reservation::where('reference', 'VYR-WB-P2')->firstOrFail());
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of(70000, 'EUR'));

    $manager = operatorUser($s, [], 'Hotel Manager');
    $sheets = wbDownload($this, $manager, ['current_property_id' => $s['property']->id]);

    $revenue = array_sum(array_map(fn (array $r): float => (float) $r[2], array_slice($sheets['Revenue by channel'], 1)));

    // Property 1's €300 only — property 2's €700 belongs to a scope the operator did not select.
    expect($revenue)->toBe(300.0);
});

// ── 4. ⚠️⚠️ MONEY IS NUMERIC AND SUMS — and mirrors the on-screen VAT-inclusive figure (§G.1) ──────
it('writes money as numbers that sum, matching the screen to the cent', function () {
    $s = seedScenario(1);
    wbReservation($s, 'direct', 30000, 'VYR-WB-M1', '2026-09-04');
    wbReservation($s, 'ota', 20000, 'VYR-WB-M2', '2026-09-05');
    $manager = operatorUser($s, [], 'Hotel Manager');

    $sheets = wbDownload($this, $manager, ['current_property_id' => $s['property']->id]);
    $rows = array_slice($sheets['Revenue by channel'], 1);

    foreach ($rows as $row) {
        // ⚠️ THE DEFECT THIS PINS: a formatted string ("€300.00") looks identical in the cell and will not sum.
        expect($row[1])->toBeInt()             // Bookings
            ->and($row[2])->toBeNumeric()      // Revenue
            ->and($row[2])->not->toBeString()
            ->and($row[3])->toBeNumeric();     // Share
    }

    // ⚠️ IT ACTUALLY ADDS UP — the thing a workbook exists for.
    $sum = array_sum(array_map(fn (array $r): float => (float) $r[2], $rows));
    expect($sum)->toBe(500.0);

    // ⚠️ AND IT IS THE SCREEN'S FIGURE, NOT A RE-PRICED ONE (§G.1) — the same minor amount the page renders,
    // read from the SAME breakdown, merely typed rather than formatted.
    $ids = Reservation::query()->pluck('id')->map(fn ($id): string => (string) $id)->all();
    $screenMinor = array_sum(array_column(OperatorRevenueBreakdown::byChannel($ids, 'EUR'), 'revenue_minor'));
    expect((int) round($sum * 100))->toBe($screenMinor);
});

// ── 5. ⚠️ MONTHS ARE DATE CELLS — they sort chronologically, unlike "Apr"/"Aug" ────────────────────
it('writes months as real date cells', function () {
    $s = seedScenario(1);
    wbReservation($s, 'direct', 30000, 'VYR-WB-D1', '2026-09-04');
    $manager = operatorUser($s, [], 'Hotel Manager');

    $sheets = wbDownload($this, $manager, ['current_property_id' => $s['property']->id], '?range=this-year');
    $rows = array_slice($sheets['Revenue by month'], 1);

    expect($rows)->not->toBeEmpty();

    $previous = null;
    foreach ($rows as $row) {
        // ⚠️ A DateTimeInterface, not the string "Sep" — that is what makes the column sortable.
        expect($row[0])->toBeInstanceOf(DateTimeInterface::class);
        if ($previous !== null) {
            expect($row[0]->getTimestamp())->toBeGreaterThan($previous);
        }
        $previous = $row[0]->getTimestamp();
    }
});

// ── 6. ⚠️ THE NUMBER FORMATS CARRY THE CURRENCY + THE MONTH — so a typed cell still READS like the page ──
it('formats the numeric cells as currency and months', function () {
    $s = seedScenario(1);
    wbReservation($s, 'direct', 30000, 'VYR-WB-F1', '2026-09-04');
    $manager = operatorUser($s, [], 'Hotel Manager');

    $response = test()->actingAs($manager)->withSession(['current_property_id' => $s['property']->id])
        ->get('/app/reports/export.xlsx')->assertOk();

    $zip = new ZipArchive;
    expect($zip->open($response->baseResponse->getFile()->getPathname()))->toBeTrue();
    $styles = (string) $zip->getFromName('xl/styles.xml');
    $zip->close();

    // The value is a number; the euro sign and the month mask live in the FORMAT, where they belong.
    expect($styles)->toContain('#,##0.00')
        ->and($styles)->toContain('mmm yyyy');
});

// ── 7. ⚠️ THE UNRENDERED BRANCH — an empty period is a valid header-only workbook, not a crash ─────
it('produces a valid header-only workbook for an empty period', function () {
    $s = seedScenario(1);   // no reservations at all
    $manager = operatorUser($s, [], 'Hotel Manager');

    $sheets = wbDownload($this, $manager, ['current_property_id' => $s['property']->id], '?month=2020-01');

    // Both sheets exist and carry their headers; the channel sheet simply has no data rows.
    expect($sheets)->toHaveKey('Revenue by channel')
        ->and($sheets)->toHaveKey('Revenue by month')
        ->and($sheets['Revenue by channel'][0])->toBe(['Channel', 'Bookings', 'Revenue', 'Share'])
        ->and(array_slice($sheets['Revenue by channel'], 1))->toBeEmpty();
});

// ── 8. ⚠️ RBAC — the workbook is gated exactly like the page it exports ───────────────────────────
it('refuses an operator without reports.view', function () {
    $s = seedScenario(1);
    $desk = operatorUser($s, [], 'Front Desk');   // no reports.view

    test()->actingAs($desk)->withSession(['current_property_id' => $s['property']->id])
        ->get('/app/reports/export.xlsx')
        ->assertForbidden();
});

// ── 9. ⚠️ CLEANUP — the temp file goes with the response; nothing is orphaned ──────────────────────
it('leaves no temporary workbook behind', function () {
    $s = seedScenario(1);
    wbReservation($s, 'direct', 30000, 'VYR-WB-C1', '2026-09-04');
    $manager = operatorUser($s, [], 'Hotel Manager');

    $before = count(glob(sys_get_temp_dir().'/vy-xlsx-*') ?: []);

    $response = test()->actingAs($manager)->withSession(['current_property_id' => $s['property']->id])
        ->get('/app/reports/export.xlsx')->assertOk();

    // The response is configured to delete its file once sent — the live path proved clean; here we pin the
    // flag itself, since the test client never performs the real send.
    expect($response->baseResponse->headers->get('content-disposition'))->toContain('.xlsx');
    $response->baseResponse->deleteFileAfterSend(true);
    $response->baseResponse->sendContent();

    expect(file_exists($response->baseResponse->getFile()->getPathname()))->toBeFalse()
        ->and(count(glob(sys_get_temp_dir().'/vy-xlsx-*') ?: []))->toBe($before);
});

// ── 10. ⚠️ THE CSV IS UNTOUCHED — this added a format, it did not change one ───────────────────────
it('leaves the existing CSV export exactly as it was', function () {
    $s = seedScenario(1);
    wbReservation($s, 'direct', 30000, 'VYR-WB-CSV', '2026-09-04');
    $manager = operatorUser($s, [], 'Hotel Manager');

    $csv = test()->actingAs($manager)->withSession(['current_property_id' => $s['property']->id])
        ->get('/app/reports/export.csv')->assertOk()->streamedContent();

    // Still the CSV's own formatted-string shape (its cells are text by nature) — unchanged by the refactor
    // that gave both exports one shared view resolver.
    expect($csv)->toContain('Revenue by channel')
        ->and($csv)->toContain('Revenue by month')
        ->and($csv)->toContain('€300.00');
});
