<?php

declare(strict_types=1);

use App\Support\Pdf\PdfFonts;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Exceptions;

// I18N-01 · PDF fonts (2026-11-05). dompdf writes a bundled font's metrics into its font directory and cannot create it: on a
// fresh checkout `storage/fonts` did not exist, registerFont() failed, and a Thai / Chinese / Korean PDF printed in Times with no
// glyphs at all. The render pass found it; this keeps it found.

afterEach(fn () => app()->setLocale('en'));

it('creates a missing font directory and embeds the bundled Thai font', function () {
    $dir = sys_get_temp_dir().'/vy-pdf-fonts-'.bin2hex(random_bytes(4));   // a directory that does not exist yet
    expect(is_dir($dir))->toBeFalse();

    Exceptions::fake();   // PdfFonts::prepare reports (never throws): a failure must show its reason here, not only 'false is not true'
    app()->setLocale('th');
    $html = PdfFonts::html('<html><head><meta charset="utf-8"></head><body style="font-family:\'DejaVu Sans\'">สวัสดี ห้องพัก</body></html>', 'th');
    $pdf = Pdf::loadHTML($html);
    $options = $pdf->getDomPDF()->getOptions();
    $options->setFontDir($dir);
    $options->setFontCache($dir);
    PdfFonts::prepare($pdf->getDomPDF(), 'th');

    $reasons = collect(Exceptions::reported())->map(fn ($e) => get_class($e).': '.$e->getMessage())->all();
    expect($reasons)->toBe([]);

    $out = $pdf->output();

    expect(is_dir($dir))->toBeTrue()
        ->and(str_contains($out, 'vyrobelthaisans'))->toBeTrue()    // the bundled family is embedded …
        ->and(str_contains($out, 'Times-Roman'))->toBeFalse();      // … not the Times fallback that has no Thai glyphs

    foreach (glob($dir.'/*') ?: [] as $f) {
        @unlink($f);
    }
    @rmdir($dir);
});

it('switches capitals off for any Georgian text, even inside another language\'s document', function () {
    $html = '<html><head></head><body><div style="text-transform:uppercase">ინვოისი</div></body></html>';

    expect(PdfFonts::html($html, 'en'))->toContain('text-transform:none !important')
        ->and(PdfFonts::html('<html><head></head><body>Invoice</body></html>', 'en'))->not->toContain('text-transform:none');
});
