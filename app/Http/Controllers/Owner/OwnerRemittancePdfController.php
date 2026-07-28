<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Services\PdfService;
use App\Support\Exports\ExportAudit;
use App\Support\Pdf\OwnerRemittanceDocument;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Owner remittance-advice PDF download (Wave 1 · Sub-gate 2) — the payment document for ONE payout.
 * OWNER-SCOPED exactly like the statement PDF: the id is a plain string resolved via `findOrFail` under
 * the active OwnerScope + tenant scope (set by SetOwnerScopeForPortal on the `/owner` routes), so an
 * owner can download ONLY their own remittance — a cross-owner (or cross-tenant) id 404s, and a
 * non-linked user fails closed (empty scope → nothing found). Reads through the real Payout + its
 * finalized source statement; renders nothing invented.
 */
class OwnerRemittancePdfController extends Controller
{
    public function download(Request $request, string $payout, PdfService $pdf): Response
    {
        $model = Payout::query()->findOrFail($payout);

        // The statement relation is owner-scoped too, and lazy-loads onto $model — so the Document
        // reuses it rather than re-querying. Falls back to the payout key on the (FK-impossible)
        // statement-less payout rather than naming a period that does not exist.
        $statement = $model->statement;
        $slug = $statement !== null ? $statement->period_start->format('Y-m') : (string) $model->getKey();

        // Wave B · GATE 5 — record + watermark the download (who / when), the SAME identity on the file + the log.
        $watermark = app(ExportAudit::class)->stamp($request, 'remittance_pdf', 'Remittance advice', (string) $model->id);

        return $pdf->download('pdfs.owner-remittance', OwnerRemittanceDocument::data($model), 'vyrobel-remittance-'.$slug.'.pdf', $watermark);
    }
}
