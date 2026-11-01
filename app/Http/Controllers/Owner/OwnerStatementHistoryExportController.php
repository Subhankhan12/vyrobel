<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\OwnerStatement;
use App\Models\Scopes\OwnerScope;
use App\Services\PdfService;
use App\Support\Export\CsvSafe;
use App\Support\Exports\ExportAudit;
use App\Support\I18n\LocalDate;
use App\Support\Money\MoneyFormatter;
use App\Support\Pdf\OwnerStatementHistoryDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Owner Statement HISTORY export — CSV + PDF of the owner's FINALIZED statement history, optionally filtered to one
 * year (the same client-side year filter the page shows drives `?year=`). OWNER-SCOPED exactly like the on-screen
 * history: {@see OwnerStatement} reads through the active {@see OwnerScope} on the `/owner`
 * routes, so a non-linked user fails closed (empty) and no cross-owner row can appear. GATED `exports` (co-owner +
 * accountant) at the route. HONESTY: statements carry NO guest data (only period/money/status); the file/document
 * reconciles — the total net shown == Σ of the rows' net (#17); nothing invented (#39).
 */
class OwnerStatementHistoryExportController extends Controller
{
    use FormatsOwnerData;

    public function csv(Request $request): StreamedResponse
    {
        [$rows, $year] = $this->rows($request);

        $suffix = $year !== null ? (string) $year : 'all';
        $filename = 'statement-history-'.$suffix.'-'.Carbon::now()->format('Y-m-d').'.csv';

        // Wave B · GATE 5 — record + watermark the download (who / when); the SAME identity in the log + the file's comment row.
        $watermark = app(ExportAudit::class)->stamp($request, 'statement_history_csv', 'Statement history (CSV)');

        return response()->streamDownload(function () use ($rows, $watermark): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, CsvSafe::row(['# '.$watermark]));
            fputcsv($out, CsvSafe::row([
                __('php/http/controllers/owner/owner_statement_history_export_controller.col_month'),
                __('php/http/controllers/owner/owner_statement_history_export_controller.col_period'),
                __('php/http/controllers/owner/owner_statement_history_export_controller.col_gross'),
                __('php/http/controllers/owner/owner_statement_history_export_controller.col_net_payable'),
                __('php/http/controllers/owner/owner_statement_history_export_controller.col_status'),
            ]));

            foreach ($rows as $s) {
                fputcsv($out, CsvSafe::row([
                    $s->period_start->translatedFormat(LocalDate::pattern('F Y')),
                    __('php/http/controllers/owner/owner_statement_history_export_controller.period_range', ['start' => $s->period_start->translatedFormat(LocalDate::pattern('j M Y')), 'end' => $s->period_end->translatedFormat(LocalDate::pattern('j M Y'))]),
                    MoneyFormatter::display((int) $s->gross_minor, (string) $s->source_currency),
                    MoneyFormatter::display((int) $s->net_payable_minor, (string) $s->currency),
                    $this->statusMeta((string) $s->status)['label'],
                ]));
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function pdf(Request $request, PdfService $pdf): Response
    {
        [$rows, $year] = $this->rows($request);

        $suffix = $year !== null ? (string) $year : 'all';
        $filename = 'statement-history-'.$suffix.'-'.Carbon::now()->format('Y-m-d').'.pdf';

        // Wave B · GATE 5 — record + watermark the download (who / when), the SAME identity on the file + the log.
        $watermark = app(ExportAudit::class)->stamp($request, 'statement_history_pdf', 'Statement history');

        return $pdf->download(
            'pdfs.owner-statement-history',
            OwnerStatementHistoryDocument::data($rows, $year),
            $filename,
            $watermark,
        );
    }

    /**
     * The owner's finalized statements (most recent first), optionally clipped to one year. `?year=` is trusted
     * only as a numeric filter over the owner-scoped set — it can NEVER widen the set (owner scope is the boundary).
     *
     * @return array{0: Collection<int, OwnerStatement>, 1: int|null}
     */
    private function rows(Request $request): array
    {
        $yearRaw = $request->query('year');
        $year = is_numeric($yearRaw) ? (int) $yearRaw : null;

        $query = OwnerStatement::query()
            ->whereIn('status', ['finalized', 'paid'])
            ->orderByDesc('period_start');

        if ($year !== null) {
            $query->whereYear('period_start', $year);
        }

        return [$query->get(), $year];
    }
}
