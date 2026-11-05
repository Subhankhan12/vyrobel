<?php

declare(strict_types=1);

use App\Support\I18n\Locales;
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

    $res = realpath(resource_path('fonts/pdf')) ?: resource_path('fonts/pdf');
    $diag = [
        'group' => Locales::pdfGroup('th'), 'family' => PdfFonts::family('th'), 'locale' => app()->getLocale(),
        'ttf' => collect(glob($res.'/VyrobelThai*.ttf') ?: [])->map(fn ($f) => basename($f).':'.filesize($f))->all(),
        'dir' => collect(glob($dir.'/*') ?: [])->map(fn ($f) => basename($f).':'.filesize($f))->all(),
        'families' => array_keys($pdf->getDomPDF()->getFontMetrics()->getFontFamilies()),
        'chroot' => $options->getChroot(), 'fontDir' => $options->getFontDir(), 'bytes' => strlen($out),
        'baseFonts' => array_slice(array_unique(preg_match_all('#/BaseFont\s*/([A-Za-z0-9_+-]+)#', $out, $m) ? $m[1] : []), 0, 5),
    ];
    expect(stripos((string) json_encode($diag), 'vyrobelthaisans') !== false)->toBeTrue(json_encode($diag));   // on failure the message carries the whole picture

    expect(is_dir($dir))->toBeTrue()
        ->and(stripos($out, 'vyrobelthaisans') !== false)->toBeTrue()    // the bundled family is embedded (named 'vyrobelthaisans…' or, once subset, 'VyrobelThaiSans-…': match either) …
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
