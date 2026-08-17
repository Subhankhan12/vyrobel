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
use App\Support\Export\Workbook;
use App\Support\Maintenance\MaintenancePeriod;
use App\Support\Money\MoneyFormatter;
use App\Support\Property\PropertyContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Maintenance Costs EXPORTS (#89) — the Costs report's Export menu. REAL: a PDF report + a CSV of the billable
 * line items (the totals reconcile to what the owner is billed). Gated `can:maintenance.manage` on the routes
 * (the operator analytics gate — the operator OWNS this cost data, so no CAP_EXPORTS owner gate applies). Every
 * export is AUDITED (who + how many, never the values). "Email to accountant" sends the PDF to the operator's
 * OWN email (MailSafetyGuard → log/Mailpit outside production) — never a fabricated send.
 *
 * ⚠️ D12b-1 — Excel is no longer deferred. It was deferred because no spreadsheet library was in the stack and
 * an `.xls`-as-HTML is a hack, not a workbook; D4-1 paid that fork, so this reuses {@see Workbook} as-is.
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
        // ⚠️ NULL = THE COMPLETE LINE-ITEM SET. This took the 60-row default, so the reconciling totals row at
        // the foot of the file sat under a SILENTLY truncated detail: above 60 tickets a reader summing the
        // rows could not reach the total, and nothing in the file said why. A CSV is a DOWNLOAD, not a
        // preview — the whole reason to export it is to hold the detail — so the cap does not belong here.
        // Same fix, same reason as the workbook's (D12b-1).
        $report = $costs->report($ids, $from, $to, null);
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

    /**
     * D12b-1 — the Costs report as a real WORKBOOK: a Summary tab and a Line items tab.
     *
     * ⚠️ IT CARRIES NO SCOPE OF ITS OWN. Both tabs come from ONE {@see report()} call resolved through the
     * SAME {@see scope()} the screen, the CSV and the PDF use — so a chosen period exports that period because
     * there is nowhere else for the period to come from. Gated `can:maintenance.manage` at the route and
     * audited like its siblings.
     *
     * ⚠️ THE SUMMARY TAB RECONCILES TO THE LINE-ITEM TAB, WHICH IS WHY IT ASKS FOR THE COMPLETE DETAIL.
     * `report()` defaults to the screen's 60-row preview while its totals sum EVERY resolved ticket in the
     * window — perfectly fine for a page, and a defect in a workbook, where the two tabs would silently
     * disagree above 60 tickets. Passing NULL takes the whole set, so the summary equals its own detail.
     *
     * ⚠️ TYPED CELLS: money is numeric (it SUMS; the currency lives in the number format) and dates are real
     * date cells. Nothing is re-priced — these are the same integer minor amounts the screen renders (§G.1).
     */
    public function xlsx(Request $request, MaintenanceCostService $costs, AuditEventRecorder $audit): BinaryFileResponse
    {
        [$ids, $from, $to] = $this->scope($request);
        $report = $costs->report($ids, $from, $to, null);   // ⚠️ NULL = the complete line-item set
        $currency = (string) $report['currency'];
        $audit->record('maintenance.costs_exported', null, null, ['format' => 'xlsx', 'orders' => $report['orders_closed']]);

        return Workbook::download('Vyrobel-maintenance-costs.xlsx', function (Writer $writer) use ($report, $currency): void {
            $this->summarySheet($writer, $report, $currency);
            $this->lineItemSheet($writer, $report, $currency);
        });
    }

    /** Tab 1 — the page's KPIs and its three breakdowns, every figure numeric. */
    private function summarySheet(Writer $writer, array $report, string $currency): void
    {
        $writer->getCurrentSheet()->setName('Summary');

        $writer->addRow(Workbook::headingRow(['Measure', 'Value']));
        foreach ([
            ['Total spend', Workbook::money((int) $report['total_minor'], $currency)],
            ['Labour', Workbook::money((int) $report['labor_minor'], $currency)],
            ['Parts', Workbook::money((int) $report['parts_minor'], $currency)],
            ['Orders closed', (int) $report['orders_closed']],
            ['Owner-billed', Workbook::money((int) $report['owner_units_minor'], $currency)],
            ['Operator-borne', Workbook::money((int) $report['operator_units_minor'], $currency)],
            ['Parts on order', (int) $report['parts_on_order']],
        ] as $row) {
            $writer->addRow(Workbook::row($row));
        }
        // ⚠️ An average over zero orders is NOT zero — the service returns null and so does this cell.
        $writer->addRow(Workbook::row(['Average per order', $report['avg_minor'] !== null ? Workbook::money((int) $report['avg_minor'], $currency) : '—']));

        $writer->addRow(Workbook::row([]));
        $writer->addRow(Workbook::headingRow(['By category', 'Orders', 'Total']));
        foreach ($report['by_category'] as $c) {
            $writer->addRow(Workbook::row([ucfirst((string) $c['category']), (int) $c['count'], Workbook::money((int) $c['total_minor'], $currency)]));
        }

        $writer->addRow(Workbook::row([]));
        $writer->addRow(Workbook::headingRow(['By month', 'Orders', 'Total']));
        foreach ($report['by_month'] as $m) {
            $writer->addRow(Workbook::row([
                Workbook::date(CarbonImmutable::parse(((string) $m['month']).'-01'), Workbook::MONTH_FORMAT),
                (int) $m['count'],
                Workbook::money((int) $m['total_minor'], $currency),
            ]));
        }

        $writer->addRow(Workbook::row([]));
        $writer->addRow(Workbook::headingRow(['Top units', 'Orders', 'Total']));
        foreach ($report['top_units'] as $u) {
            $writer->addRow(Workbook::row([(string) $u['code'], (int) $u['count'], Workbook::money((int) $u['total_minor'], $currency)]));
        }
    }

    /** Tab 2 — the complete billable detail, the same columns the CSV and the screen show. */
    private function lineItemSheet(Writer $writer, array $report, string $currency): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Line items');

        $writer->addRow(Workbook::headingRow(['Date', 'Work order', 'Unit', 'Category', 'Labour', 'Parts', 'Total']));

        foreach ($report['billable'] as $row) {
            $writer->addRow(Workbook::row([
                // A real date cell where the raw date exists; an unresolved date stays honestly blank.
                $row['date_iso'] !== null ? Workbook::date(CarbonImmutable::parse((string) $row['date_iso'])) : '—',
                (string) $row['title'],
                (string) $row['unit'],
                ucfirst((string) $row['category']),
                Workbook::money((int) $row['labor_minor'], $currency),
                Workbook::money((int) $row['parts_minor'], $currency),
                Workbook::money((int) $row['total_minor'], $currency),
            ]));
        }

        // The reconciling totals row — labour + parts === total (#17), and equal to the Summary tab because
        // both read the same complete population.
        $writer->addRow(Workbook::row([
            '', 'Total', '', '',
            Workbook::money((int) $report['labor_minor'], $currency),
            Workbook::money((int) $report['parts_minor'], $currency),
            Workbook::money((int) $report['total_minor'], $currency),
        ]));
    }

    public function pdf(Request $request, MaintenanceCostService $costs, PdfService $pdf, AuditEventRecorder $audit): SymfonyResponse
    {
        [$ids, $from, $to] = $this->scope($request);
        // ⚠️ THE SAME DEFECT, FOUND BY SWEEPING THE CALLERS RATHER THAN FROM THE REGISTER (§N.27.1). The
        // register named the CSV; the PDF prints "Total spend" in its header AND a reconciling Total row at
        // the foot of the same capped table, so it disagreed with itself above 60 tickets.
        $report = $costs->report($ids, $from, $to, null);
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
        // ⚠️ AND THIS ONE IS SENT. It renders the same PDF template, so it carried the same truncated detail
        // under the same complete total — into an inbox, where it cannot be corrected after the fact.
        $report = $costs->report($ids, $from, $to, null);
        $bytes = $pdf->raw('pdfs.maintenance-costs', ['report' => $report]);
        Mail::to($email)->send(new MaintenanceCostReportMail($bytes));

        return redirect()->route('app.maintenance.costs')->with('mt_notice', "Costs report emailed to {$email}.");
    }
}
