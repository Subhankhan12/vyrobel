<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerStatement;
use App\Services\PdfService;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use App\Support\Pdf\OwnerStatementDocument;
use Symfony\Component\HttpFoundation\Response;

/**
 * Owner statement PDF download (Demo-readiness · Part B · Step B1). OWNER-SCOPED exactly like the
 * on-screen statement: the id is a plain string resolved via `findOrFail` under the active OwnerScope +
 * tenant scope (set by SetOwnerScopeForPortal on the `/owner` routes), so an owner can download ONLY
 * their own statement — a cross-owner (or cross-tenant) id 404s, and a non-linked user fails closed
 * (empty scope → nothing found). Reads through the real, finalized statement; renders nothing invented.
 */
class OwnerStatementPdfController extends Controller
{
    public function download(string $statement, PdfService $pdf): Response
    {
        $model = OwnerStatement::query()->findOrFail($statement);

        // 🔒 The masked payout IBAN appears on the document ONLY for a payout-capable role (CAP_PAYOUT = co-owner);
        // an accountant (who may export, CAP_EXPORTS) never leaks the account number (#28). The estimated payout
        // DATE stays refused (no field). All figures reconcile to the statement to the cent (#17).
        $canSeePayout = app(OwnerContext::class)->can(OwnerTeamRole::CAP_PAYOUT);

        $filename = 'vyrobel-statement-'.$model->sequence_no.'-'.$model->period_start->format('Y-m').'.pdf';

        return $pdf->download('pdfs.owner-statement', OwnerStatementDocument::data($model, $canSeePayout), $filename);
    }
}
