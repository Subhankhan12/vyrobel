<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerStatement;
use App\Services\PdfService;
use App\Support\Pdf\OwnerBookingSummaryDocument;
use Symfony\Component\HttpFoundation\Response;

/**
 * Owner booking-summary PDF download (Wave 1 · Sub-gate 2) — the REAL bookings behind one finalized
 * statement's gross. OWNER-SCOPED exactly like the statement PDF it sits beside: the id is a plain string
 * resolved via `findOrFail` under the active OwnerScope + tenant scope (set by SetOwnerScopeForPortal on
 * the `/owner` routes), so an owner can download ONLY their own — a cross-owner (or cross-tenant) id 404s,
 * and a non-linked user fails closed (empty scope → nothing found). Reads through the real, finalized
 * statement; renders nothing invented and no guest PII.
 */
class OwnerBookingSummaryPdfController extends Controller
{
    public function download(string $statement, PdfService $pdf): Response
    {
        $model = OwnerStatement::query()->findOrFail($statement);

        $filename = 'vyrobel-bookings-'.$model->sequence_no.'-'.$model->period_start->format('Y-m').'.pdf';

        return $pdf->download('pdfs.owner-booking-summary', OwnerBookingSummaryDocument::data($model), $filename);
    }
}
