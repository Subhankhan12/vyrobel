<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Exports\ExportAudit;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Adapter\CPDF;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thin PDF renderer over barryvdh/laravel-dompdf (dompdf). Renders a Blade view to a branded, PDF-safe
 * document. Content comes entirely from the caller's real data — this service invents nothing.
 *
 * An optional `$watermark` (Wave B · GATE 5) stamps a fixed downloader footer onto every page — the SAME
 * identity {@see ExportAudit} records in the download log, so a financial export always
 * carries who downloaded it.
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
    public function download(string $view, array $data, string $filename, ?string $watermark = null): Response
    {
        return $this->paginate($this->make($view, $data, $watermark)->setPaper('a4'))->download($filename);
    }

    /**
     * Raw PDF bytes for a rendered view (used in tests / when embedding).
     *
     * @param  array<string, mixed>  $data
     */
    public function raw(string $view, array $data, ?string $watermark = null): string
    {
        return $this->paginate($this->make($view, $data, $watermark)->setPaper('a4'))->output();
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
    private function paginate(\Barryvdh\DomPDF\PDF $pdf): \Barryvdh\DomPDF\PDF
    {
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(
            self::FOOTER_X, self::FOOTER_Y, 'Page {PAGE_NUM} of {PAGE_COUNT}',
            'sans-serif', 7.5, [0.373, 0.345, 0.325]   // #5f5853, the watermark's ink
        );

        return $pdf;
    }

    /**
     * Build the dompdf instance, injecting a fixed-position downloader watermark footer when one is given (a
     * page-pinned div so it repeats on every page, drawn on top of the real content but visually unobtrusive).
     *
     * @param  array<string, mixed>  $data
     */
    private function make(string $view, array $data, ?string $watermark)
    {
        if ($watermark === null || $watermark === '') {
            return Pdf::loadView($view, $data);
        }

        $html = view($view, $data)->render();
        // ⚠️ PDF-SWEEP GATE — THIS STAMP IS INFORMATIONAL, NOT DECORATIVE, so it is held to the text bar.
        // It carries WHO downloaded the document (the same identity {@see ExportAudit} writes to the download
        // log) plus "Confidential" — the fact that makes the Team security note's claim true. It is also NOT a
        // watermark in the usual sense: it is a footer line pinned in the bottom MARGIN, not a faint mark lying
        // behind content, so darkening it costs nothing it was designed to give. It was #a99b8e — measured on
        // real ink at 2.70 against paper, and 2.41 where it lands on a warm footer band: an audit stamp a
        // reader cannot read is a cosmetic feature. Now the same grey the 17 templates use for secondary text.
        // ⚠️ Its plate is still NON-DETERMINISTIC — `position:fixed` puts it over whatever the page ends with
        // (see DO-PDF-2); this fixes the ink, not that.
        $footer = '<div style="position:fixed;bottom:4px;left:0;right:0;text-align:center;font-size:7.5px;color:#5f5853;font-family:sans-serif;letter-spacing:.02em;">'.e($watermark).'</div>';
        $html = str_contains($html, '</body>') ? str_replace('</body>', $footer.'</body>', $html) : $html.$footer;

        return Pdf::loadHTML($html);
    }
}
