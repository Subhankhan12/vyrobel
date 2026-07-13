<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\PdfService;
use App\Support\Pdf\FolioDocument;
use Symfony\Component\HttpFoundation\Response;

/**
 * Operator folio PDF download (Demo-readiness · Part B · Step B2). TENANT-scoped exactly like the
 * on-screen reservation detail: the reservation id is a plain string resolved via `findOrFail` under
 * the active tenant scope (SetTenantForWebRequest), so an operator can export ONLY their own org's
 * folio — a cross-org reservation id 404s. Reads the real append-only ledger; renders nothing invented.
 */
class FolioPdfController extends Controller
{
    public function download(string $reservation, PdfService $pdf): Response
    {
        $model = Reservation::query()->findOrFail($reservation);

        $filename = 'vyrobel-folio-'.$model->reference.'.pdf';

        return $pdf->download('pdfs.folio', FolioDocument::data($model), $filename);
    }
}
