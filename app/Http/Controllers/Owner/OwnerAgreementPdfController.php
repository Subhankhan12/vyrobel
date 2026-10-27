<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\AgreementAmendment;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Services\PdfService;
use App\Support\Exports\ExportAudit;
use App\Support\I18n\Locales;
use App\Support\Owner\OwnerContext;
use App\Support\Pdf\OwnerAgreementDocument;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Owner Agreement / Amendment PDF downloads (Owner Agreement exact-match #82). `$capExports`-gated on the
 * route (#42). OWNER-scoped EXPLICITLY — management_agreements / agreement_amendments carry no OwnerScope,
 * so the owner ids are resolved from the portal context and every read is constrained to them (fail-closed
 * for a non-linked user; a cross-owner amendment id 404s). Every figure is a certified read; nothing
 * invented ({@see OwnerAgreementDocument}). Watermarked + logged via the shared {@see ExportAudit}.
 */
class OwnerAgreementPdfController extends Controller
{
    public function agreement(Request $request, PdfService $pdf): Response
    {
        $context = app(OwnerContext::class);
        $ownerIds = $context->active() ? $context->ownerIds() : [];
        abort_if($ownerIds === [], 404);

        // The portfolio-wide agreement in effect today headlines the document (the same choice the page
        // makes); fall back to the most recent agreement if none is in effect right now.
        $agreement = ManagementAgreement::query()
            ->whereIn('owner_id', $ownerIds)
            ->whereNull('unit_id')
            ->effectiveOn(now()->toDateString())
            ->orderByDesc('effective_from')
            ->first()
            ?? ManagementAgreement::query()
                ->whereIn('owner_id', $ownerIds)
                ->orderByDesc('effective_from')
                ->first();

        abort_if(! $agreement instanceof ManagementAgreement, 404);

        $owner = Owner::query()->whereKey($agreement->owner_id)->firstOrFail();

        $watermark = app(ExportAudit::class)->stamp($request, 'agreement_pdf', 'Management agreement', (string) $agreement->id);

        return $pdf->download(
            'pdfs.owner-agreement',
            OwnerAgreementDocument::agreement($agreement, $owner, $ownerIds),
            'vyrobel-agreement-'.$agreement->effective_from->format('Y').'.pdf',
            $watermark,
            Locales::SOURCE,   // I18N-01 — a LEGAL document stays English until the lawyer has seen a translation
        );
    }

    public function amendment(Request $request, string $amendment, PdfService $pdf): Response
    {
        $context = app(OwnerContext::class);
        $ownerIds = $context->active() ? $context->ownerIds() : [];
        abort_if($ownerIds === [], 404);

        // Explicit owner scoping — only THIS owner's amendments; a cross-owner id 404s. Visible = published
        // or applied (never a draft).
        $model = AgreementAmendment::query()
            ->whereIn('owner_id', $ownerIds)
            ->visible()
            ->findOrFail($amendment);

        $owner = Owner::query()->whereKey($model->owner_id)->firstOrFail();

        $watermark = app(ExportAudit::class)->stamp($request, 'agreement_amendment_pdf', 'Agreement amendment '.$model->version, (string) $model->id);

        return $pdf->download(
            'pdfs.owner-agreement-amendment',
            OwnerAgreementDocument::amendment($model, $owner),
            'vyrobel-amendment-'.$model->effective_date->format('Y').'.pdf',
            $watermark,
            Locales::SOURCE,   // I18N-01 — a LEGAL document stays English until the lawyer has seen a translation
        );
    }
}
