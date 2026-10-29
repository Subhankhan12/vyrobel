<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerLedgerEntry;
use App\Models\Scopes\OwnerScope;
use App\Support\Export\CsvSafe;
use App\Support\Exports\ExportAudit;
use App\Support\Money\MoneyFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Owner Balance activity — CSV export. A REAL, owner-scoped download of the SAME `owner_ledger` rows the page
 * shows, with the DERIVED running balance (per currency, oldest→newest — exactly the page's math). Owner-scoped
 * by the active {@see OwnerScope} on the `/owner` routes → a non-linked user fails closed
 * (headers-only) and no cross-owner row appears. GATED `exports` (co-owner + accountant). No guest data; integer
 * money throughout (#17); nothing fabricated (#39).
 */
class OwnerBalanceExportController extends Controller
{
    public function download(Request $request): StreamedResponse
    {
        // Oldest→newest so the running balance accumulates correctly (per currency), like the page.
        $entries = OwnerLedgerEntry::query()
            ->orderBy('service_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $filename = 'balance-activity-'.Carbon::now()->format('Y-m-d').'.csv';

        // Wave B · GATE 5 — record + watermark the download (who / when); the SAME identity in the log + the file's comment row.
        $watermark = app(ExportAudit::class)->stamp($request, 'balance_csv', 'Balance activity export');

        return response()->streamDownload(function () use ($entries, $watermark): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, CsvSafe::row(['# '.$watermark]));
            fputcsv($out, CsvSafe::row([
                __('php/http/controllers/owner/owner_balance_export_controller.col_date'),
                __('php/http/controllers/owner/owner_balance_export_controller.col_activity'),
                __('php/http/controllers/owner/owner_balance_export_controller.col_type'),
                __('php/http/controllers/owner/owner_balance_export_controller.col_amount'),
                __('php/http/controllers/owner/owner_balance_export_controller.col_running_balance'),
                __('php/http/controllers/owner/owner_balance_export_controller.col_on_statement'),
            ]));

            /** @var array<string, int> $running */
            $running = [];

            foreach ($entries as $e) {
                $currency = (string) $e->currency;
                $signed = $e->signedMinor();
                $running[$currency] = ($running[$currency] ?? 0) + $signed;

                fputcsv($out, CsvSafe::row([
                    $e->service_date->format('Y-m-d'),
                    $e->label(),
                    (string) $e->entry_type,
                    MoneyFormatter::display($signed, $currency, signed: true),
                    MoneyFormatter::display($running[$currency], $currency),
                    $e->statement_id !== null ? __('php/http/controllers/owner/owner_balance_export_controller.on_statement_yes') : __('php/http/controllers/owner/owner_balance_export_controller.on_statement_not_yet'),
                ]));
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
