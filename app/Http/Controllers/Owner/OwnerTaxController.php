<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Services\OwnerTaxService;
use App\Services\OwnerVatService;
use App\Services\PdfService;
use App\Support\Countries;
use App\Support\Export\CsvSafe;
use App\Support\Exports\ExportAudit;
use App\Support\Pdf\OwnerAnnualTaxDocument;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            // ⚠️ RE-AUTH (2026-08-31). `legal_name` is the payee printed on the remittance document, and
            // `tax_country` is the key WithholdingService and OwnerVatService look a rate up by — so this
            // changes what is DISCLOSED and what is WITHHELD. Saving a tax id also RELEASES held payouts.
            // ⚠️ THE BARE RULE, RE-VERIFIED not inherited: guards are web/guest/admin/api, `/owner` runs on
            // plain `auth`; a guard-qualified rule would silently validate nothing.
            // ⚠️ ORDER — and why the usual "ownership before identity" does NOT bind here: this route carries
            // NO owner id. OwnerTaxService resolves the owner from the AUTHENTICATED USER, so there is no
            // foreign target to probe and the validation error cannot become an existence oracle. Capability
            // is already enforced by the route's CAP_ACTIONS middleware, before any of this runs.
            'current_password' => ['required', 'current_password'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['individual', 'company'])],
            'tax_country' => ['nullable', 'string', 'max:60'],
            'tax_id' => ['required', 'string', 'max:100'],
            'vat_number' => ['nullable', 'string', 'max:100'],
            'vat_registered' => ['nullable', 'boolean'],
        ]);

        // ⚠️ NORMALISE THE RESIDENCY TO ITS ISO CODE (audit 2026-08-07). `tax_country` is the key that
        // WithholdingService and OwnerVatService look a configured rate up by, so a display name stored
        // here matches nothing and silently yields a ZERO withholding on a real payout. The form now
        // submits codes; this is the second line of defence, because a stale cached page — or any future
        // caller of this route — must not be able to poison the column. An unrecognised value is passed
        // through rather than discarded, so nothing an operator typed is silently lost.
        $data['tax_country'] = Countries::toCode($data['tax_country'] ?? null);

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

    /**
     * VAT return · CSV (Wave D · tax engine) — the REAL computed output/input/net VAT per quarter, from
     * FOUNDER-CONFIGURED rates ({@see OwnerVatService}). Owner self-only + CAP_EXPORTS. 404s when VAT isn't
     * available (not registered / no rate configured) — never a fabricated figure. Every file carries the honest
     * "computed from your configured rates — verify with your accountant; not tax advice" header, and an EXAMPLE-
     * rate file is loudly marked "NOT FOR FILING".
     */
    public function vatReturn(Request $request, string $year, OwnerVatService $vatService): StreamedResponse
    {
        if (preg_match('/^\d{4}$/', $year) !== 1) {
            abort(404);
        }

        try {
            $owner = $this->ownerFor($request);
        } catch (ModelNotFoundException) {
            abort(404);
        }

        $vat = $vatService->summary($owner, (int) $year);
        if (($vat['available'] ?? false) !== true) {
            abort(404);   // not registered / not configured — nothing to export, never a fabricated figure
        }

        $watermark = app(ExportAudit::class)->stamp($request, 'vat_return_csv', 'VAT return '.$year);

        return response()->streamDownload(function () use ($vat, $watermark, $year): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, CsvSafe::row(['# '.$watermark]));
            fputcsv($out, CsvSafe::row(['# Computed from the rates configured for '.$vat['jurisdiction'].' — verify with your accountant; this is not tax advice.']));
            if (($vat['is_example'] ?? false) === true) {
                fputcsv($out, CsvSafe::row(['# ⚠️ BASED ON EXAMPLE RATES — real rates are not configured for this jurisdiction. NOT FOR FILING.']));
            }
            fputcsv($out, CsvSafe::row(['Quarter', 'Rental income', 'Output VAT', 'Agency fee', 'Input VAT', 'Net VAT']));
            foreach ($vat['quarters'] as $q) {
                fputcsv($out, CsvSafe::row([$q['label'], $q['gross_display'], $q['output_display'], '', $q['input_display'], $q['net_display']]));
            }
            fputcsv($out, CsvSafe::row([$year.' total', '', $vat['annual']['output_display'], '', $vat['annual']['input_display'], $vat['annual']['net_display']]));
            fclose($out);
        }, 'vyrobel-vat-return-'.$year.'.csv', ['Content-Type' => 'text/csv']);
    }

    /** Self-resolve the authenticated user's owner (fail closed) — the same rule as {@see downloadAnnual}. */
    private function ownerFor(Request $request): Owner
    {
        return Owner::query()->where('user_id', $request->user()?->getKey())->firstOrFail();
    }
}
