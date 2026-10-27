<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerExpense;
use App\Models\Scopes\OwnerScope;
use App\Support\Export\CsvSafe;
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
            fputcsv($out, CsvSafe::row(['# '.$watermark]));
            fputcsv($out, CsvSafe::row([
                __('php/http/controllers/owner/owner_expenses_export_controller.col_date'),
                __('php/http/controllers/owner/owner_expenses_export_controller.col_unit'),
                __('php/http/controllers/owner/owner_expenses_export_controller.col_category'),
                __('php/http/controllers/owner/owner_expenses_export_controller.col_description'),
                __('php/http/controllers/owner/owner_expenses_export_controller.col_vendor'),
                __('php/http/controllers/owner/owner_expenses_export_controller.col_amount'),
                __('php/http/controllers/owner/owner_expenses_export_controller.col_deductible'),
                __('php/http/controllers/owner/owner_expenses_export_controller.col_voided'),
            ]));

            foreach ($expenses as $e) {
                $unit = $e->unit;

                fputcsv($out, CsvSafe::row([
                    Carbon::parse($e->expense_date)->format('Y-m-d'),
                    $unit === null ? __('php/http/controllers/owner/owner_expenses_export_controller.unassigned') : (string) ($unit->name ?? $unit->code),
                    match ((string) $e->category) {
                        'cleaning' => __('php/http/controllers/owner/owner_expenses_export_controller.category_cleaning'),
                        'maintenance' => __('php/http/controllers/owner/owner_expenses_export_controller.category_maintenance'),
                        'other' => __('php/http/controllers/owner/owner_expenses_export_controller.category_other'),
                    },
                    (string) $e->description,
                    (string) ($e->vendor ?? ''),
                    MoneyFormatter::display((int) $e->amount_minor, (string) $e->currency),
                    match ($e->deductible) {
                        null => __('php/http/controllers/owner/owner_expenses_export_controller.pending_review'),
                        default => $e->deductible ? __('php/http/controllers/owner/owner_expenses_export_controller.deductible') : __('php/http/controllers/owner/owner_expenses_export_controller.not_deductible'),
                    },
                    $e->voided_at !== null ? __('php/http/controllers/owner/owner_expenses_export_controller.voided_yes') : __('php/http/controllers/owner/owner_expenses_export_controller.voided_no'),
                ]));
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
