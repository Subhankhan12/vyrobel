<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\OwnerStatement;
use App\Models\Scopes\OwnerScope;
use App\Services\PdfService;
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

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['Month', 'Period', 'Gross', 'Net payable', 'Status']);

            foreach ($rows as $s) {
                fputcsv($out, [
                    $s->period_start->format('F Y'),
                    $s->period_start->format('j M Y').' – '.$s->period_end->format('j M Y'),
                    MoneyFormatter::display((int) $s->gross_minor, (string) $s->source_currency),
                    MoneyFormatter::display((int) $s->net_payable_minor, (string) $s->currency),
                    $this->statusMeta((string) $s->status)['label'],
                ]);
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

        return $pdf->download(
            'pdfs.owner-statement-history',
            OwnerStatementHistoryDocument::data($rows, $year),
            $filename,
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
