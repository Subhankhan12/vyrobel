<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerStatement;
use App\Services\PdfService;
use App\Support\Exports\ExportAudit;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use App\Support\Pdf\OwnerStatementDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * DO24-1 — the owner's FULL ARCHIVE as one ZIP: every statement PDF the Archive page lists, in a single
 * download. Until now that control was an honest disabled "Soon" (there was no builder); this is the builder.
 *
 * ⚠️ THIS IS A BULK-EGRESS SURFACE, AND SCOPE IS THE WHOLE GAME. One request now emits many documents at
 * once, so a scoping slip stops being one wrong page and becomes a bulk leak. Three boundaries hold it:
 *
 * 🔒 THE ROWS — {@see OwnerStatement} carries the owner + organization global scopes, so the query can only
 *    ever see THIS owner's statements inside THIS org. A non-linked user resolves to an empty set, not to
 *    everyone's. No status/owner predicate is hand-written here; the set comes from the shared
 *    {@see OwnerStatement::scopeArchived()} the Archive PAGE reads, so the bundle matches the list.
 * 🔒 THE GATE — the route sits in the `CAP_EXPORTS` group, the same capability that gates every individual
 *    statement PDF. Bundling them cannot become a way around the gate on downloading one.
 * 🔒 THE CONTENTS — each PDF is built by the SAME {@see OwnerStatementDocument::data()} the single-statement
 *    download uses, with the SAME per-owner payout check: the masked IBAN appears only for a payout-capable
 *    role (#28). An accountant may export the archive and still never receives an account number — the rule
 *    travels INTO the ZIP rather than being re-decided at its edge.
 *
 * ⚠️ READ-ONLY. Nothing here writes to a statement, a ledger or a payout; the archive reads what already
 * exists and renders it. The only row written is the export-audit record below.
 *
 * ⚠️ ONE AUDIT STAMP FOR THE WHOLE ARCHIVE, and that same watermark goes on every page of every PDF inside —
 * so a leaked document from a bundle is traceable to the person who took the bundle, exactly like a
 * single download (Wave B · GATE 5).
 *
 * ⚠️ SYNCHRONOUS, DELIBERATELY, AND BOUNDED BY A REAL NUMBER: an owner's archive is one statement per
 * period, so it is tens of documents, not thousands, and the page tells them the count before they click.
 * PDFs are rendered one at a time and written straight into the zip on disk — never all held in memory —
 * and the temp file is deleted after the response is sent. ⚠️ If archives ever grow past what a request can
 * comfortably render, the next step is a queued build + a signed link; that is logged, not built.
 */
class OwnerArchiveZipController extends Controller
{
    public function download(Request $request, PdfService $pdf, OwnerContext $context): BinaryFileResponse|RedirectResponse
    {
        // 🔒 Owner- + org-scoped by the model's global scopes; the SAME set the Archive page lists.
        $statements = OwnerStatement::query()->archived()->get();

        if ($statements->isEmpty()) {
            // An empty ZIP would be a file that says nothing. Say it in words instead.
            return back()->with('archive_zip_notice', __('php/http/controllers/owner/owner_archive_zip_controller.nothing_to_archive'));
        }

        // One stamp for the bundle; its watermark is applied to every document inside.
        $watermark = app(ExportAudit::class)->stamp($request, 'statement_archive_zip', 'Full statement archive');

        $tmp = tempnam(sys_get_temp_dir(), 'vy-archive-');
        if ($tmp === false) {
            throw new RuntimeException('Could not create a temporary file for the archive.');
        }

        $zip = new ZipArchive;
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            throw new RuntimeException('Could not open the archive for writing.');
        }

        try {
            foreach ($statements as $statement) {
                // 🔒 #28, per statement and per owner — identical to the single-PDF path.
                $canSeePayout = $context->canOn((string) $statement->owner_id, OwnerTeamRole::CAP_PAYOUT);

                $zip->addFromString(
                    $this->entryName($statement),
                    // Rendered one at a time; the string is handed to the zip and released each iteration.
                    $pdf->raw('pdfs.owner-statement', OwnerStatementDocument::data($statement, $canSeePayout), $watermark),
                );
            }
        } catch (\Throwable $e) {
            // Never leave a half-built archive behind — a partial ZIP is worse than none.
            $zip->close();
            @unlink($tmp);

            throw $e;
        }

        $zip->close();

        return response()
            ->download($tmp, 'vyrobel-statement-archive-'.now()->format('Y-m-d').'.zip', [
                'Content-Type' => 'application/zip',
            ])
            ->deleteFileAfterSend(true);   // ⚠️ the temp file goes with the response, always
    }

    /** The in-zip filename for one statement — the same naming the single download uses, so they match. */
    private function entryName(OwnerStatement $statement): string
    {
        return 'vyrobel-statement-'.$statement->sequence_no.'-'.$statement->period_start->format('Y-m').'.pdf';
    }
}
