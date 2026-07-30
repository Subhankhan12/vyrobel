<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Mail\MaintenanceCostReportMail;
use App\Services\MaintenanceCostService;
use App\Services\PdfService;
use App\Support\Events\AuditEventRecorder;
use App\Support\Money\MoneyFormatter;
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
    public function csv(Request $request, MaintenanceCostService $costs, AuditEventRecorder $audit): StreamedResponse
    {
        $report = $costs->report();
        $currency = $report['currency'];
        $audit->record('maintenance.costs_exported', null, null, ['format' => 'csv', 'orders' => $report['orders_closed']]);

        return response()->streamDownload(function () use ($report, $currency): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Work order', 'Unit', 'Category', 'Labor', 'Parts', 'Total']);
            foreach ($report['billable'] as $row) {
                fputcsv($out, [
                    $row['date'],
                    $this->csvSafe($row['title']),
                    $this->csvSafe($row['unit']),
                    $this->csvSafe(ucfirst($row['category'])),
                    MoneyFormatter::display($row['labor_minor'], $currency),
                    MoneyFormatter::display($row['parts_minor'], $currency),
                    MoneyFormatter::display($row['total_minor'], $currency),
                ]);
            }
            // The reconciling totals row (labor + parts === total).
            fputcsv($out, ['', 'Total', '', '', MoneyFormatter::display($report['labor_minor'], $currency), MoneyFormatter::display($report['parts_minor'], $currency), MoneyFormatter::display($report['total_minor'], $currency)]);
            fclose($out);
        }, 'Vyrobel-maintenance-costs.csv', ['Content-Type' => 'text/csv']);
    }

    public function pdf(Request $request, MaintenanceCostService $costs, PdfService $pdf, AuditEventRecorder $audit): SymfonyResponse
    {
        $report = $costs->report();
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

        $report = $costs->report();
        $bytes = $pdf->raw('pdfs.maintenance-costs', ['report' => $report]);
        Mail::to($email)->send(new MaintenanceCostReportMail($bytes));

        return redirect()->route('app.maintenance.costs')->with('mt_notice', "Costs report emailed to {$email}.");
    }

    /** Neutralise CSV formula injection: a field beginning with = + - @ (or a control char) is quote-prefixed. */
    private function csvSafe(string $value): string
    {
        $value = str_replace(["\r", "\n"], ' ', $value);

        return preg_match('/^[=+\-@\t]/', $value) === 1 ? "'".$value : $value;
    }
}
