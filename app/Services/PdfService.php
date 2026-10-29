<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Exports\ExportAudit;
use App\Support\I18n\Locales;
use App\Support\Pdf\ArabicPdfShaper;
use App\Support\Pdf\PdfFonts;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Adapter\CPDF;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thin PDF renderer over barryvdh/laravel-dompdf (dompdf). Renders a Blade view to a branded, PDF-safe
 * document. Content comes entirely from the caller's real data — this service invents nothing.
 *
 * An optional `$watermark` (Wave B · GATE 5) stamps a fixed downloader footer onto every page — the SAME
 * identity {@see ExportAudit} records in the download log, so a financial export always
 * carries who downloaded it.
 *
 * I18N-01 — every PDF is rendered in its DOCUMENT's language: the caller passes it (a guest's documents in the guest's,
 * an owner's in the owner's); without one, the language of the request. Here — the one renderer — the script is handled:
 * shaping for Arabic/Persian/Thai, bundled fonts for Chinese/Japanese/Korean/Thai, no capitals for Georgian, and English
 * wording for Hindi ({@see PdfFonts}). An English document renders exactly as before.
 */
class PdfService
{
    /** Bottom-right of A4 (595x842pt), clear of the centred downloader watermark at the very bottom. */
    private const FOOTER_X = 476.0;

    private const FOOTER_Y = 812.0;

    /**
     * Stream a rendered view as a downloadable PDF (Content-Type: application/pdf; attachment filename).
     *
     * @param  array<string, mixed>  $data
     */
    public function download(string $view, array $data, string $filename, ?string $watermark = null, ?string $locale = null): Response
    {
        $locale = $this->documentLocale($locale);

        return $this->paginate($this->make($view, $data, $watermark, $locale)->setPaper('a4'), $locale)->download($filename);
    }

    /**
     * Raw PDF bytes for a rendered view (used in tests / when embedding).
     *
     * @param  array<string, mixed>  $data
     */
    public function raw(string $view, array $data, ?string $watermark = null, ?string $locale = null): string
    {
        $locale = $this->documentLocale($locale);

        return $this->paginate($this->make($view, $data, $watermark, $locale)->setPaper('a4'), $locale)->output();
    }

    /** A finished language the document should speak (the caller's choice, else the request's), as it will be RENDERED. */
    private function documentLocale(?string $locale): string
    {
        return PdfFonts::renderLocale(Locales::resolve($locale ?? App::getLocale()));
    }

    /**
     * DO20-1 — stamp "Page N of M" into the bottom margin of EVERY page.
     *
     * ⚠️ WHY THIS IS NOT MARKUP. A statement or a report that runs to several pages cannot be checked for
     * completeness without a total: page 3 of an unnumbered document is indistinguishable from the end of it,
     * and a page lost in printing or emailing leaves no trace. So this belongs in the ONE renderer every PDF
     * already goes through, not in seventeen templates (§N.8f).
     *
     * ⚠️ AND IT CANNOT BE DONE IN HTML. dompdf's inline `{PAGE_NUM}` substitution is **commented out** in
     * `Renderer/Text.php`, so a placeholder written into the Blade view renders literally. The working
     * mechanism is the canvas page-script: {@see CPDF::page_text()} defers to a callback
     * invoked per page at OUTPUT time with the real `(pageNumber, pageCount)`, which is why **M is the true
     * total and never a count this service had to guess**. The document must therefore be rendered first —
     * hence the explicit `render()` here, which the wrapper's own `rendered` flag stops from happening twice.
     *
     * Positioned bottom-RIGHT so it never collides with the centred downloader watermark below, and set in
     * the same 7.5pt / #5f5853 as that stamp so the two footer marks read as one family.
     */
    private function paginate(\Barryvdh\DomPDF\PDF $pdf, string $locale): \Barryvdh\DomPDF\PDF
    {
        $pdf->render();
        $label = __('php/services/pdf_service.page_of', ['page' => '{PAGE_NUM}', 'pages' => '{PAGE_COUNT}'], $locale);
        if (Locales::script($locale) === 'Arab') {
            $label = ArabicPdfShaper::shape($label);
        }
        // the canvas takes a font FILE, not a CSS family: a family name it cannot resolve falls back to Helvetica, which has no
        // Arabic letters — "Page 1 of 1" printed "????? 1 ?? 1 ???" (seen 2026-10-29). Resolve the document's family first.
        $dompdf = $pdf->getDomPDF();
        $font = $locale === Locales::SOURCE ? 'sans-serif' : ($dompdf->getFontMetrics()->getFont(PdfFonts::family($locale)) ?? PdfFonts::family($locale));
        $dompdf->getCanvas()->page_text(self::FOOTER_X, self::FOOTER_Y, $label, $font, 7.5, [0.373, 0.345, 0.325]);   // #5f5853, the watermark's ink

        return $pdf;
    }

    /**
     * Build the dompdf instance, injecting a fixed-position downloader watermark footer when one is given (a
     * page-pinned div so it repeats on every page, drawn on top of the real content but visually unobtrusive).
     *
     * @param  array<string, mixed>  $data
     */
    private function make(string $view, array $data, ?string $watermark, string $locale)
    {
        // the view renders in the document's language; the request's own language is put back straight after
        $requestLocale = App::getLocale();
        App::setLocale($locale);
        try {
            $html = view($view, $data)->render();
        } finally {
            App::setLocale($requestLocale);
        }

        if ($watermark === null || $watermark === '') {
            return $this->load(PdfFonts::html($html, $locale), $locale);
        }

        // ⚠️ PDF-SWEEP GATE — THIS STAMP IS INFORMATIONAL, NOT DECORATIVE, so it is held to the text bar.
        // It carries WHO downloaded the document (the same identity {@see ExportAudit} writes to the download
        // log) plus "Confidential" — the fact that makes the Team security note's claim true. It is also NOT a
        // watermark in the usual sense: it is a footer line pinned in the bottom MARGIN, not a faint mark lying
        // behind content, so darkening it costs nothing it was designed to give. It was #a99b8e — measured on
        // real ink at 2.70 against paper, and 2.41 where it lands on a warm footer band: an audit stamp a
        // reader cannot read is a cosmetic feature. Now the same grey the 17 templates use for secondary text.
        // ⚠️ Its plate is still NON-DETERMINISTIC — `position:fixed` puts it over whatever the page ends with
        // (see DO-PDF-2); this fixes the ink, not that.
        // in another language its font is the documents' own (DejaVu Sans, swapped for a bundled family where the script needs
        // one): a generic sans-serif has no Arabic letters, and the stamp printed as "???" on an Arabic statement (seen 2026-10-29)
        $stampFont = $locale === Locales::SOURCE ? 'sans-serif' : '\'DejaVu Sans\'';   // English exactly as before
        $footer = '<div style="position:fixed;bottom:4px;left:0;right:0;text-align:center;font-size:7.5px;color:#5f5853;font-family:'.$stampFont.';letter-spacing:.02em;">'.e($watermark).'</div>';
        $html = str_contains($html, '</body>') ? str_replace('</body>', $footer.'</body>', $html) : $html.$footer;

        return $this->load(PdfFonts::html($html, $locale), $locale);
    }

    private function load(string $html, string $locale): \Barryvdh\DomPDF\PDF
    {
        $pdf = Pdf::loadHTML($html);
        PdfFonts::prepare($pdf->getDomPDF(), $locale);

        return $pdf;
    }
}
