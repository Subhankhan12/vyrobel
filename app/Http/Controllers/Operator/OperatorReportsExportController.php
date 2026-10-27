<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Reservation;
use App\Services\PdfService;
use App\Support\Export\CsvSafe;
use App\Support\Export\Workbook;
use App\Support\Money\MoneyFormatter;
use App\Support\Property\PropertyContext;
use App\Support\Reports\OperatorReportData;
use App\Support\Reports\OperatorRevenueBreakdown;
use App\Support\Reports\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * #104 GATE 2 — the operator Reports CSV export (the wireframe's "Export" was a dead "Soon" pill; it is not
 * infra-blocked, so it was a build gap, not an honest deferral). Streams the REAL by-channel + by-month revenue
 * breakdown for the current property scope + range, through the SAME {@see OperatorRevenueBreakdown} the screen
 * renders — so the CSV and the page can never disagree (#17). Gated `can:reports.view` at the route (the same
 * gate as the screen); scope resolves through {@see PropertyContext} (never a request-supplied property id).
 */
class OperatorReportsExportController extends Controller
{
    public function csv(Request $request): StreamedResponse
    {
        ['range' => $range, 'currency' => $currency, 'channels' => $channels, 'months' => $months] = $this->view($request);

        $filename = 'reports-'.str_replace(':', '-', $range['key']).'.csv';

        return response()->streamDownload(function () use ($channels, $months, $currency, $range): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, CsvSafe::row([__('php/http/controllers/operator/operator_reports_export_controller.csv_title'), $range['label']]));
            // ⚠️ THE UNIT IS STATED IN THE FILE, not assumed by whoever opens it. Amounts are major units
            //    (euros, not cents) as a bare decimal; the ISO 4217 code sits in its own column per row.
            fputcsv($out, CsvSafe::row([__('php/http/controllers/operator/operator_reports_export_controller.amounts'), __('php/http/controllers/operator/operator_reports_export_controller.amounts_unit_note')]));
            fputcsv($out, CsvSafe::row([]));

            fputcsv($out, CsvSafe::row([__('php/http/controllers/operator/operator_reports_export_controller.revenue_by_channel')]));
            fputcsv($out, CsvSafe::row([__('php/http/controllers/operator/operator_reports_export_controller.channel'), __('php/http/controllers/operator/operator_reports_export_controller.bookings'), __('php/http/controllers/operator/operator_reports_export_controller.revenue'), __('php/http/controllers/operator/operator_reports_export_controller.currency'), __('php/http/controllers/operator/operator_reports_export_controller.share_percent')]));
            foreach ($channels as $c) {
                fputcsv($out, CsvSafe::row([
                    $this->sourceLabel($c['source']),
                    $c['bookings'],
                    MoneyFormatter::csvAmount($c['revenue_minor']),
                    $currency,
                    $c['share'],
                ]));
            }
            fputcsv($out, CsvSafe::row([]));

            fputcsv($out, CsvSafe::row([__('php/http/controllers/operator/operator_reports_export_controller.revenue_by_month')]));
            fputcsv($out, CsvSafe::row([__('php/http/controllers/operator/operator_reports_export_controller.month'), __('php/http/controllers/operator/operator_reports_export_controller.revenue'), __('php/http/controllers/operator/operator_reports_export_controller.currency')]));
            foreach ($months as $m) {
                // ⚠️ `month` (ISO `Y-m`), NOT `label` ('Sep'). The breakdown has carried both all along and
                //    this file took the wrong one — a human label does not sort, and `09/2026` would be read
                //    as a date, a fraction or text depending on where the file is opened.
                fputcsv($out, CsvSafe::row([
                    (string) $m['month'],
                    MoneyFormatter::csvAmount($m['revenue_minor']),
                    $currency,
                ]));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * GATE 10.2 — the wireframe's "PDF report" export: revenue, KPIs, channel mix + top units for the current
     * scope + period, rendered from the SAME {@see OperatorReportData} the screen builds (so the printed report
     * can never disagree with the page). Gated `can:reports.view`; scope via {@see PropertyContext}.
     */
    public function pdf(Request $request, PdfService $pdf): Response
    {
        $ctx = app(PropertyContext::class);
        $ids = $ctx->readIds();
        $range = ReportPeriod::resolve($request->query('range'), $request->query('month'));
        $currency = $this->currency($ctx);

        $id = $ctx->id();
        $scopeLabel = $id !== null
            ? (string) (Property::query()->whereKey($id)->value('name') ?? __('php/http/controllers/operator/operator_reports_export_controller.property_fallback'))
            : __('php/http/controllers/operator/operator_reports_export_controller.all_properties');

        $filename = 'report-'.str_replace(':', '-', $range['key']).'.pdf';

        return $pdf->download('pdfs.operator-report', [
            'data' => OperatorReportData::build($ids, $range, $currency),
            'range' => $range,
            'currency' => $currency,
            'scopeLabel' => $scopeLabel,
        ], $filename);
    }

    /**
     * D4-1 — the wireframe's "Excel workbook" export, which until now was an honest disabled "Soon" (there was
     * no spreadsheet library in the stack, so it was a genuine dependency gap, not a build gap).
     *
     * ⚠️⚠️ AN EXPORT IS ONLY HONEST IF IT IS THE VIEW. It carries no scope of its own: the rows come from
     * {@see view()} — the SAME property scope, the SAME period and the SAME breakdown the screen and the CSV
     * use. A workbook that re-queried the table would quietly hand the operator rows their screen is not
     * allowed to show them (a leak) and rows their filter excluded (a lie about what they were looking at).
     *
     * ⚠️ THE REASON IT IS A WORKBOOK AND NOT A SECOND CSV: the cells are TYPED (see {@see Workbook}). Money is
     * a number that SUMS, months are date cells that sort chronologically, shares are percent cells.
     * ⚠️ THIS DOCBLOCK USED TO SAY "the CSV stays exactly as it was". It no longer does, and the reasoning
     * behind that sentence was the defect: it treated a CSV nobody could total as an acceptable second
     * format because a good one existed beside it. The CSV now emits bare decimals, an ISO currency column
     * and ISO months, so both formats are usable and neither teaches the operator to distrust the other.
     *
     * Two sheets, matching the design's "Trend + channels tabs". Gated `can:reports.view` at the route, the
     * same gate as the screen.
     */
    public function xlsx(Request $request): BinaryFileResponse
    {
        ['range' => $range, 'currency' => $currency, 'channels' => $channels, 'months' => $months] = $this->view($request);

        $filename = 'reports-'.str_replace(':', '-', $range['key']).'.xlsx';

        return Workbook::download($filename, function (Writer $writer) use ($channels, $months, $currency): void {
            // ── Sheet 1 · Revenue by channel ──────────────────────────────────────────────────────
            $writer->getCurrentSheet()->setName(Workbook::sheetName(__('php/http/controllers/operator/operator_reports_export_controller.revenue_by_channel')));
            $writer->addRow(Workbook::headingRow([__('php/http/controllers/operator/operator_reports_export_controller.channel'), __('php/http/controllers/operator/operator_reports_export_controller.bookings'), __('php/http/controllers/operator/operator_reports_export_controller.revenue'), __('php/http/controllers/operator/operator_reports_export_controller.share')]));

            foreach ($channels as $c) {
                $writer->addRow(Workbook::row([
                    OperatorRevenueBreakdown::sourceLabel($c['source']),
                    (int) $c['bookings'],                                  // numeric
                    Workbook::money((int) $c['revenue_minor'], $currency),  // 🔢 numeric — it SUMS
                    Workbook::percent((int) $c['share']),                   // 📊 percent cell
                ]));
            }

            // ── Sheet 2 · Revenue by month (the trend) ────────────────────────────────────────────
            $trend = $writer->addNewSheetAndMakeItCurrent();
            $trend->setName(Workbook::sheetName(__('php/http/controllers/operator/operator_reports_export_controller.revenue_by_month')));
            $writer->addRow(Workbook::headingRow([__('php/http/controllers/operator/operator_reports_export_controller.month'), __('php/http/controllers/operator/operator_reports_export_controller.revenue')]));

            foreach ($months as $m) {
                $writer->addRow(Workbook::row([
                    // 📅 a real date cell for the month start — sorts chronologically, unlike "Apr"/"Aug".
                    Workbook::date(CarbonImmutable::parse($m['month'].'-01'), Workbook::MONTH_FORMAT),
                    Workbook::money((int) $m['revenue_minor'], $currency),
                ]));
            }
        });
    }

    /**
     * ⚠️ THE ONE DEFINITION OF "WHAT THE OPERATOR IS LOOKING AT" — the property scope, the active period and
     * the resulting breakdown. The CSV, the workbook (and the screen, through the same collaborators) all read
     * it, so an export cannot drift from the view it claims to be (§N.8f).
     *
     * 🔒 SCOPE — {@see PropertyContext::readIds()}, never a request-supplied property id (#38). No accessible
     *    property yields an EMPTY id set, and an empty set short-circuits to no reservations at all rather
     *    than to an unfiltered query.
     * 🔒 PERIOD — {@see ReportPeriod::resolve()} on the request's `range`/`month`, the same resolver the screen
     *    uses, so a chosen reporting month exports that month and nothing else.
     *
     * @return array{range: array{key: string, label: string, start: CarbonImmutable, end: CarbonImmutable}, currency: string, channels: list<array<string, mixed>>, months: list<array<string, mixed>>}
     */
    private function view(Request $request): array
    {
        $ctx = app(PropertyContext::class);
        $ids = $ctx->readIds();
        $range = ReportPeriod::resolve($request->query('range'), $request->query('month'));
        $currency = $this->currency($ctx);

        $reservationIds = $ids === []
            ? []
            : Reservation::query()
                ->whereIn('property_id', $ids)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->whereDate('check_in', '>=', $range['start']->toDateString())
                ->whereDate('check_in', '<', $range['end']->toDateString())
                ->pluck('id')->map(fn ($id): string => (string) $id)->all();

        return [
            'range' => $range,
            'currency' => $currency,
            'channels' => OperatorRevenueBreakdown::byChannel($reservationIds, $currency),
            'months' => OperatorRevenueBreakdown::byMonth($reservationIds, $currency, $range['start'], $range['end']),
        ];
    }

    private function currency(PropertyContext $ctx): string
    {
        // Derive the currency from the SAME property the screen does — {@see PropertyContext::id()}, which in the
        // "all" view already resolves to the first ACCESSIBLE property (accessibleIds[0]), matching the page's
        // currentReadIds[0]. So the export can't pick a different currency than the page (#17); when the scope is
        // empty (no accessible property) both fall back to EUR (the report is empty anyway). NEVER the org-wide
        // first-by-name property, which a property_user pivot may exclude (#38).
        return (string) (Property::query()->whereKey($ctx->id())->value('currency') ?? 'EUR');
    }

    private function sourceLabel(string $source): string
    {
        return OperatorRevenueBreakdown::sourceLabel($source);   // the ONE operator source-label map (anti-drift)
    }
}
