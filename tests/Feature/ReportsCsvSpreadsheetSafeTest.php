<?php

declare(strict_types=1);

use App\Models\Reservation;
use App\Services\FolioService;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * ⚠️ THE EXPORT CSVs MUST BE USABLE BY A SPREADSHEET, ANYWHERE.
 *
 * ⚠️⚠️ THIS DEFECT WAS FOUND BY A DOC AUDIT MARKING A PAGE "FALSE", AND THE PAGE WAS THE RIGHT SIDE.
 * `guide-reports` said the CSV used ISO dates and unformatted numbers. It did not — so the page was
 * corrected to describe the broken export, which made the defect look INTENTIONAL and removed the only
 * remaining signal that it existed. The audit cannot tell you WHICH SIDE is wrong; this test is the code
 * side being made true instead.
 *
 * ⚠️ VALUES ARE UNCHANGED. Only the rendering moved: `parsedBack` below re-reads each new cell and asserts
 * it equals the integer minor units it came from, to the cent.
 */
it('⚠️⚠️ renders money as a BARE DECIMAL — no symbol, no thousands separator, dot decimal', function () {
    // A four-figure amount is the one that exposes grouping; a sub-unit amount exposes the decimal.
    expect(MoneyFormatter::csvAmount(123400))->toBe('1234.00')
        ->and(MoneyFormatter::csvAmount(44280))->toBe('442.80')
        ->and(MoneyFormatter::csvAmount(5))->toBe('0.05')
        ->and(MoneyFormatter::csvAmount(0))->toBe('0.00')
        ->and(MoneyFormatter::csvAmount(-9720))->toBe('-97.20');

    foreach ([123400, 44280, 5, 0, -9720, 100000000] as $minor) {
        $cell = MoneyFormatter::csvAmount($minor);

        // ⚠️ A THOUSANDS SEPARATOR OF EITHER CONVENTION MAKES THE CELL TEXT — and a comma decimal makes it
        //    text in one country and a different number in another.
        expect($cell)->not->toContain(',')
            ->and($cell)->toMatch('/^-?\d+\.\d{2}$/');

        // ⚠️ THE SYMBOL IS NOT IN THE NUMBER. The currency travels in its own column.
        expect($cell)->not->toContain('€')
            ->and($cell)->not->toContain('$')
            ->and($cell)->not->toContain('−');   // U+2212, which display() uses for negatives
    }
});

it('⚠️⚠️ VALUES ARE IDENTICAL — every rendered cell parses back to the exact minor units', function () {
    // The September demo figures plus the boundaries that expose a rounding or float error.
    $cases = [54000, 44280, -9720, 88000, 33210, 66420, 1, 99, 100, 999999999];

    foreach ($cases as $minor) {
        $parsedBack = (int) round((float) MoneyFormatter::csvAmount($minor) * 100);

        expect($parsedBack)->toBe($minor);
    }
});

it('⚠️ is exact for a value a float cannot hold — no drift at the cent', function () {
    // 0.1 + 0.2 !== 0.3 in binary floating point; the printed cell must still be exact.
    expect(MoneyFormatter::csvAmount(10))->toBe('0.10')
        ->and(MoneyFormatter::csvAmount(20))->toBe('0.20')
        ->and(MoneyFormatter::csvAmount(30))->toBe('0.30')
        ->and((int) round((float) MoneyFormatter::csvAmount(30) * 100))->toBe(30);
});

it('⚠️ display() is UNCHANGED — the screen still gets a formatted figure', function () {
    // ⚠️ The fix must not have reached the screen: a report page showing `1234.00` would be the same
    //    defect pointed the other way.
    expect(MoneyFormatter::display(123400, 'EUR'))->toBe('€1,234.00')
        ->and(MoneyFormatter::display(-9720, 'EUR'))->toBe('−€97.20');
});

// ---------------------------------------------------------------------------------------------------
// END TO END — the bytes an operator actually downloads.

uses(RefreshDatabase::class);

/** Parse a CSV body into rows, ignoring blank separator lines. */
function csvRows(string $body): array
{
    $rows = [];
    foreach (explode("\n", trim($body)) as $line) {
        if (trim($line) === '') {
            continue;
        }
        $rows[] = str_getcsv($line);
    }

    return $rows;
}

it('⚠️⚠️ the downloaded reports CSV has a currency COLUMN, bare decimals and ISO months', function () {
    $s = seedScenario(1);
    $res = Reservation::create([
        'property_id' => $s['property']->id, 'reference' => 'VYR-CSV1', 'status' => 'confirmed',
        'source' => 'direct', 'lead_guest_id' => $s['guest']->id,
        'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'currency' => 'EUR',
    ]);
    $folio = app(FolioService::class)->openFolio($res);
    // ⚠️ Four figures on purpose — a grouped `1,234.00` is the cell that breaks totalling.
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of(123400, 'EUR'));

    $manager = operatorUser($s, [], 'Hotel Manager');
    $body = $this->actingAs($manager)->withSession(['current_property_id' => $s['property']->id])
        ->get(route('app.reports.export', ['range' => 'this-year']))->streamedContent();

    $rows = csvRows($body);

    // The unit is stated in the file rather than assumed by whoever opens it.
    expect($body)->toContain('major units, decimal point, no thousands separator');

    $channelHeader = collect($rows)->first(fn (array $r): bool => ($r[0] ?? '') === 'Channel');
    expect($channelHeader)->toBe(['Channel', 'Bookings', 'Revenue', 'Currency', 'Share %']);

    $direct = collect($rows)->first(fn (array $r): bool => ($r[0] ?? '') === 'Direct');
    expect($direct[2])->toBe('1234.00')          // ⚠️ bare decimal, NOT €1,234.00
        ->and($direct[3])->toBe('EUR');          // ⚠️ the currency is not lost — it has its own column

    $monthHeader = collect($rows)->first(fn (array $r): bool => ($r[0] ?? '') === 'Month');
    expect($monthHeader)->toBe(['Month', 'Revenue', 'Currency']);

    $sep = collect($rows)->first(fn (array $r): bool => ($r[0] ?? '') === '2026-09');
    expect($sep)->not->toBeNull()                // ⚠️ ISO `2026-09`, not 'Sep' and not '09/2026'
        ->and($sep[1])->toBe('1234.00')
        ->and($sep[2])->toBe('EUR');

    // ⚠️ NO money cell anywhere carries a symbol or a thousands separator.
    foreach ($rows as $row) {
        foreach ($row as $cell) {
            expect($cell)->not->toContain('€');
        }
    }

    // ⚠️ VALUE IDENTICAL: the cell parses back to the exact minor units the folio holds.
    expect((int) round((float) $direct[2] * 100))->toBe(123400);
});
