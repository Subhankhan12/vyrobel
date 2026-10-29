<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Mail\OwnerReportMail;
use App\Models\OwnerStatement;
use App\Services\PdfService;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use App\Support\Pdf\OwnerStatementDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * "Email to me" (owner completeness sweep · Wave A · GATE 4) — queues the owner's latest finalized STATEMENT
 * PDF to their OWN inbox. Replaces the Reports page's honest "Soon" with a real send.
 *
 * SELF-ONLY + OWNER-SCOPED: the statement is resolved under the active {@see OwnerContext}/OwnerScope (never a
 * request id), and the mail goes ONLY to the authenticated user's own address — there is no recipient in the
 * request. GATED CAP_EXPORTS at the route (co-owner + accountant; a view-only sub-user is refused). The PDF is
 * built HERE, where the scope is active, then queued; the masked IBAN rides only for a payout-capable role (#28).
 * When there is no finalized statement yet, nothing is sent — an honest flash, never a fake email (#39).
 */
class OwnerReportEmailController extends Controller
{
    public function send(Request $request, OwnerContext $ownerContext, PdfService $pdf): RedirectResponse
    {
        $user = $request->user();
        $email = $user?->email;

        // Fail closed: no resolved owner, or no address to send to → nothing happens.
        if ($ownerContext->ownerIds() === [] || ! is_string($email) || $email === '') {
            abort(404);
        }

        // The latest FINALIZED statement, owner-scoped (OwnerScope on /owner → cross-owner rows never appear).
        $statement = OwnerStatement::query()
            ->whereNotNull('finalized_at')
            ->orderByDesc('period_start')
            ->first();

        if (! $statement instanceof OwnerStatement) {
            return back()->with('owner_notice', __('php/http/controllers/owner/owner_report_email_controller.no_statement_yet'));
        }

        // Payout-capable role ON THIS statement's owner → the masked IBAN appears on the PDF; an accountant
        // export omits it (#28). Per-owner (P1-1) — never the union role.
        $canSeePayout = $ownerContext->canOn((string) $statement->owner_id, OwnerTeamRole::CAP_PAYOUT);
        $pdfBytes = $pdf->raw('pdfs.owner-statement', OwnerStatementDocument::data($statement, $canSeePayout));
        $filename = 'vyrobel-statement-'.$statement->sequence_no.'-'.$statement->period_start->format('Y-m').'.pdf';

        Mail::to($email)->locale($user->preferredLocale())->queue(new OwnerReportMail(
            ownerName: (string) ($statement->owner->name ?? ($user->name ?? 'Owner')),
            periodLabel: $statement->period_start->translatedFormat('F Y'),
            filename: $filename,
            pdfBytes: $pdfBytes,
        ));

        return back()->with('owner_notice', __('php/http/controllers/owner/owner_report_email_controller.statement_sent', ['email' => $email]));
    }
}
