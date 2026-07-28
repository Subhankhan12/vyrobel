<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Exports\ExportAudit;
use Barryvdh\DomPDF\Facade\Pdf;
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
    /**
     * Stream a rendered view as a downloadable PDF (Content-Type: application/pdf; attachment filename).
     *
     * @param  array<string, mixed>  $data
     */
    public function download(string $view, array $data, string $filename, ?string $watermark = null): Response
    {
        return $this->make($view, $data, $watermark)->setPaper('a4')->download($filename);
    }

    /**
     * Raw PDF bytes for a rendered view (used in tests / when embedding).
     *
     * @param  array<string, mixed>  $data
     */
    public function raw(string $view, array $data, ?string $watermark = null): string
    {
        return $this->make($view, $data, $watermark)->setPaper('a4')->output();
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
        $footer = '<div style="position:fixed;bottom:4px;left:0;right:0;text-align:center;font-size:7.5px;color:#a99b8e;font-family:sans-serif;letter-spacing:.02em;">'.e($watermark).'</div>';
        $html = str_contains($html, '</body>') ? str_replace('</body>', $footer.'</body>', $html) : $html.$footer;

        return Pdf::loadHTML($html);
    }
}
