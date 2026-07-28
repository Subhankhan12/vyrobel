<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerExpense;
use App\Models\Scopes\OwnerScope;
use App\Support\Exports\ExportAudit;
use App\Support\Money\MoneyFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Owner Expenses — CSV export. A REAL, owner-scoped download of the SAME `owner_expenses` rows the page shows
 * (the certified history), owner-scoped by the active {@see OwnerScope} on the `/owner`
 * routes → a non-linked user fails closed (headers-only) and no cross-owner row appears. GATED `exports`
 * (co-owner + accountant). The `deductible` TRI-STATE is exported honestly (deductible / not deductible /
 * pending review — NULL never coerced); a voided row is flagged, not dropped. Vendor is a REAL column now
 * (owner_expenses.vendor), exported when captured + blank when unknown; integer money throughout (#17). No guest data.
 */
class OwnerExpensesExportController extends Controller
{
    public function download(Request $request): StreamedResponse
    {
        $expenses = OwnerExpense::query()
            ->with('unit')
            ->orderByDesc('expense_date')
            ->orderByDesc('created_at')
            ->get();

        $filename = 'expenses-'.Carbon::now()->format('Y-m-d').'.csv';

        // Wave B · GATE 5 — record + watermark the download (who / when); the SAME identity in the log + the file's comment row.
        $watermark = app(ExportAudit::class)->stamp($request, 'expenses_csv', 'Expenses export');

        return response()->streamDownload(function () use ($expenses, $watermark): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['# '.$watermark]);
            fputcsv($out, ['Date', 'Unit', 'Category', 'Description', 'Vendor', 'Amount', 'Deductible', 'Voided']);

            foreach ($expenses as $e) {
                $unit = $e->unit;

                fputcsv($out, [
                    Carbon::parse($e->expense_date)->format('Y-m-d'),
                    $unit === null ? 'Unassigned' : (string) ($unit->name ?? $unit->code),
                    ucfirst((string) $e->category),
                    (string) $e->description,
                    (string) ($e->vendor ?? ''),
                    MoneyFormatter::display((int) $e->amount_minor, (string) $e->currency),
                    match ($e->deductible) {
                        null => 'pending review',
                        default => $e->deductible ? 'deductible' : 'not deductible',
                    },
                    $e->voided_at !== null ? 'yes' : 'no',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
