<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Livewire\Operator\OperatorMaintenanceCosts;
use App\Mail\MaintenanceCostReportMail;
use App\Services\MaintenanceCostService;
use App\Services\PdfService;
use App\Support\Events\AuditEventRecorder;
use App\Support\Export\CsvSafe;
use App\Support\Maintenance\MaintenancePeriod;
use App\Support\Money\MoneyFormatter;
use App\Support\Property\PropertyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Maintenance Costs EXPORTS (#89) — the Costs report's Export menu. REAL: a PDF report + a CSV of the billable
 * line items (the totals reconcile to what the owner is billed). Gated `can:maintenance.manage` on the routes
 * (the operator analytics gate — the operator OWNS this cost data, so no CAP_EXPORTS owner gate applies). Every
 * export is AUDITED (who + how many, never the values). "Email to accountant" sends the PDF to the operator's
 * OWN email (MailSafetyGuard → log/Mailpit outside production) — never a fabricated send.
 *
 * ⚠️ Excel is honestly deferred — the CSV is the machine-readable export; an `.xls`-as-HTML is a hack, not a
 * real workbook, so it is not offered as a fake.
 */
class MaintenanceCostExportController extends Controller
{
    /**
     * The EXACT scope + period the screen uses (auth/isolation pass, 2026-08-09).
     *
     * ⚠️ THE TWO-READERS TRAP: this controller and {@see OperatorMaintenanceCosts}
     * call the SAME service, so if the export resolved its scope or period differently the PDF/CSV would
     * quietly disagree with the screen it claims to export (#17). Both read the SAME period query params and
     * the SAME {@see PropertyContext::readIds()} — never a request-supplied property id, which would be an
     * isolation hole (#38).
     *
     * ⚠️ ONE param name, ONE resolution. An earlier cut of this fix had the screen read `?period=` while the
     * export read `?from=`/`?to=` — which would have reintroduced the very drift it exists to prevent (the
     * export links carry no dates, so every export would have silently been ALL-TIME beside a 6-month page).
     *
     * @return array{0: list<string>, 1: ?string, 2: ?string}
     */
    private function scope(Request $request): array
    {
        return [
            app(PropertyContext::class)->readIds(),
            MaintenancePeriod::presetFrom($request->query('period', '6m')),
            null,
        ];
    }

    public function csv(Request $request, MaintenanceCostService $costs, AuditEventRecorder $audit): StreamedResponse
    {
        [$ids, $from, $to] = $this->scope($request);
        $report = $costs->report($ids, $from, $to);
        $currency = $report['currency'];
        $audit->record('maintenance.costs_exported', null, null, ['format' => 'csv', 'orders' => $report['orders_closed']]);

        return response()->streamDownload(function () use ($report, $currency): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, CsvSafe::row(['Date', 'Work order', 'Unit', 'Category', 'Labor', 'Parts', 'Total']));
            foreach ($report['billable'] as $row) {
                fputcsv($out, CsvSafe::row([
                    $row['date'],
                    $row['title'],
                    $row['unit'],
                    ucfirst($row['category']),
                    MoneyFormatter::display($row['labor_minor'], $currency),
                    MoneyFormatter::display($row['parts_minor'], $currency),
                    MoneyFormatter::display($row['total_minor'], $currency),
                ]));
            }
            // The reconciling totals row (labor + parts === total).
            fputcsv($out, CsvSafe::row(['', 'Total', '', '', MoneyFormatter::display($report['labor_minor'], $currency), MoneyFormatter::display($report['parts_minor'], $currency), MoneyFormatter::display($report['total_minor'], $currency)]));
            fclose($out);
        }, 'Vyrobel-maintenance-costs.csv', ['Content-Type' => 'text/csv']);
    }

    public function pdf(Request $request, MaintenanceCostService $costs, PdfService $pdf, AuditEventRecorder $audit): SymfonyResponse
    {
        [$ids, $from, $to] = $this->scope($request);
        $report = $costs->report($ids, $from, $to);
        $audit->record('maintenance.costs_exported', null, null, ['format' => 'pdf', 'orders' => $report['orders_closed']]);

        return $pdf->download('pdfs.maintenance-costs', ['report' => $report], 'Vyrobel-maintenance-costs.pdf');
    }

    /** Email the PDF report to the operator's OWN email (a real send; never to a fabricated accountant address). */
    public function email(Request $request, MaintenanceCostService $costs, PdfService $pdf): RedirectResponse
    {
        $email = (string) $request->user()->email;
        if ($email === '') {
            return redirect()->route('app.maintenance.costs')->with('mt_error', 'Your account has no email address.');
        }

        [$ids, $from, $to] = $this->scope($request);
        $report = $costs->report($ids, $from, $to);
        $bytes = $pdf->raw('pdfs.maintenance-costs', ['report' => $report]);
        Mail::to($email)->send(new MaintenanceCostReportMail($bytes));

        return redirect()->route('app.maintenance.costs')->with('mt_notice', "Costs report emailed to {$email}.");
    }
}
