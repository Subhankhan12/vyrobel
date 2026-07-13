<?php

declare(strict_types=1);

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thin PDF renderer over barryvdh/laravel-dompdf (dompdf). Renders a Blade view to a branded, PDF-safe
 * document. Content comes entirely from the caller's real data — this service invents nothing.
 */
class PdfService
{
    /**
     * Stream a rendered view as a downloadable PDF (Content-Type: application/pdf; attachment filename).
     *
     * @param  array<string, mixed>  $data
     */
    public function download(string $view, array $data, string $filename): Response
    {
        return Pdf::loadView($view, $data)->setPaper('a4')->download($filename);
    }

    /**
     * Raw PDF bytes for a rendered view (used in tests / when embedding).
     *
     * @param  array<string, mixed>  $data
     */
    public function raw(string $view, array $data): string
    {
        return Pdf::loadView($view, $data)->setPaper('a4')->output();
    }
}
