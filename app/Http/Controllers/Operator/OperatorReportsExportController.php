<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Reservation;
use App\Services\PdfService;
use App\Support\Export\CsvSafe;
use App\Support\Money\MoneyFormatter;
use App\Support\Property\PropertyContext;
use App\Support\Reports\OperatorReportData;
use App\Support\Reports\OperatorRevenueBreakdown;
use App\Support\Reports\ReportPeriod;
use Illuminate\Http\Request;
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

        $channels = OperatorRevenueBreakdown::byChannel($reservationIds, $currency);
        $months = OperatorRevenueBreakdown::byMonth($reservationIds, $currency, $range['start'], $range['end']);

        $filename = 'reports-'.str_replace(':', '-', $range['key']).'.csv';

        return response()->streamDownload(function () use ($channels, $months, $currency, $range): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, CsvSafe::row(['Vyrobel report', $range['label']]));
            fputcsv($out, CsvSafe::row([]));

            fputcsv($out, CsvSafe::row(['Revenue by channel']));
            fputcsv($out, CsvSafe::row(['Channel', 'Bookings', 'Revenue', 'Share %']));
            foreach ($channels as $c) {
                fputcsv($out, CsvSafe::row([
                    $this->sourceLabel($c['source']),
                    $c['bookings'],
                    MoneyFormatter::display($c['revenue_minor'], $currency),
                    $c['share'],
                ]));
            }
            fputcsv($out, CsvSafe::row([]));

            fputcsv($out, CsvSafe::row(['Revenue by month']));
            fputcsv($out, CsvSafe::row(['Month', 'Revenue']));
            foreach ($months as $m) {
                fputcsv($out, CsvSafe::row([(string) $m['label'], MoneyFormatter::display($m['revenue_minor'], $currency)]));
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
            ? (string) (Property::query()->whereKey($id)->value('name') ?? 'Property')
            : 'All properties';

        $filename = 'report-'.str_replace(':', '-', $range['key']).'.pdf';

        return $pdf->download('pdfs.operator-report', [
            'data' => OperatorReportData::build($ids, $range, $currency),
            'range' => $range,
            'currency' => $currency,
            'scopeLabel' => $scopeLabel,
        ], $filename);
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
