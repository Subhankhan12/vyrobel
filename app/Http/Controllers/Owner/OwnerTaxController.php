<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\OwnerTaxService;
use App\Services\PdfService;
use App\Support\Exports\ExportAudit;
use App\Support\Pdf\OwnerAnnualTaxDocument;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Owner Tax Center writes + downloads (Phase 2 · Sub-gate 4). SELF-ONLY throughout: {@see OwnerTaxService}
 * resolves the owner by `user_id` from the authenticated user — there is NO owner id in any request — so an
 * owner can only ever save their OWN tax details or download their OWN annual summary. A non-linked user 404s
 * (fail closed). Saving a tax ID RELEASES held payouts (the hold lives in OwnerPayoutService::pay()).
 */
class OwnerTaxController extends Controller
{
    public function save(Request $request, OwnerTaxService $tax): RedirectResponse
    {
        $data = $request->validate([
            'legal_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['individual', 'company'])],
            'tax_country' => ['nullable', 'string', 'max:60'],
            'tax_id' => ['required', 'string', 'max:100'],
            'vat_number' => ['nullable', 'string', 'max:100'],
            'vat_registered' => ['nullable', 'boolean'],
        ]);

        // The checkbox/toggle only submits when on; normalise to an explicit boolean, and never keep a VAT
        // number on file for an owner who isn't registered (the flag is the source of truth).
        $data['vat_registered'] = $request->boolean('vat_registered');
        if (! $data['vat_registered']) {
            $data['vat_number'] = null;
        }

        try {
            $tax->saveTaxDetails($request->user(), $data);
        } catch (ModelNotFoundException) {
            abort(404);
        }

        return redirect()->to(route('owner.tax'))->with('tax_notice', 'Tax details saved — any held payouts will be released.');
    }

    public function downloadAnnual(Request $request, string $year, OwnerTaxService $tax, PdfService $pdf): Response
    {
        if (preg_match('/^\d{4}$/', $year) !== 1) {
            abort(404);
        }

        try {
            $owner = $tax->owner($request->user());
        } catch (ModelNotFoundException) {
            abort(404);   // non-linked user — fail closed
        }

        $summary = $tax->annualSummary($owner, (int) $year);
        if ($summary === null) {
            abort(404);   // no committed statements for that year — nothing to summarise
        }

        // Wave B · GATE 5 — record + watermark the download (who / when), the SAME identity on the file + the log.
        $watermark = app(ExportAudit::class)->stamp($request, 'annual_tax_pdf', 'Annual tax summary '.$year);

        return $pdf->download('pdfs.owner-annual-tax', OwnerAnnualTaxDocument::data($owner, $summary), 'vyrobel-annual-tax-'.$year.'.pdf', $watermark);
    }
}
