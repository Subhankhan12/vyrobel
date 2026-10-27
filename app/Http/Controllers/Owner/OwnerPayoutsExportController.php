<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\Scopes\OwnerScope;
use App\Support\Export\CsvSafe;
use App\Support\Exports\ExportAudit;
use App\Support\Money\MoneyFormatter;
use App\Support\Payouts\RailLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Owner Payout history — CSV export. A REAL, owner-scoped download of the SAME payout rows the page shows:
 * {@see Payout} reads through the active {@see OwnerScope} on the `/owner` routes, so a
 * non-linked user fails closed (empty, headers-only) and no cross-owner payout can appear. GATED `exports`
 * (co-owner + accountant). HONESTY: no guest data (a payout carries none); the amount reconciles to the real
 * payout; the month comes from the payout's source statement; real states only (no fabricated payout, #39).
 */
class OwnerPayoutsExportController extends Controller
{
    public function download(Request $request): StreamedResponse
    {
        $payouts = Payout::query()->orderByDesc('created_at')->get();

        // Month labels from each payout's source statement (owner-scoped by the same global scope).
        $months = OwnerStatement::query()
            ->whereIn('id', $payouts->pluck('owner_statement_id')->filter()->unique()->all())
            ->get()
            ->mapWithKeys(fn (OwnerStatement $s): array => [(string) $s->id => $s->period_start->translatedFormat('M Y')]);

        $filename = 'payout-history-'.Carbon::now()->format('Y-m-d').'.csv';

        // Wave B · GATE 5 — record + watermark the download (who / when); the SAME identity in the log + the file's comment row.
        $watermark = app(ExportAudit::class)->stamp($request, 'payouts_csv', 'Payouts export');

        return response()->streamDownload(function () use ($payouts, $months, $watermark): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, CsvSafe::row(['# '.$watermark]));
            fputcsv($out, CsvSafe::row([
                __('php/http/controllers/owner/owner_payouts_export_controller.col_month'),
                __('php/http/controllers/owner/owner_payouts_export_controller.col_rail'),
                __('php/http/controllers/owner/owner_payouts_export_controller.col_date'),
                __('php/http/controllers/owner/owner_payouts_export_controller.col_status'),
                __('php/http/controllers/owner/owner_payouts_export_controller.col_amount'),
            ]));

            foreach ($payouts as $p) {
                $when = $p->paid_at ?? $p->failed_at ?? ($p->status === 'pending' ? null : $p->created_at);

                fputcsv($out, CsvSafe::row([
                    $months[(string) $p->owner_statement_id] ?? '—',
                    RailLabel::for((string) $p->rail),
                    $when !== null ? Carbon::parse($when)->translatedFormat('j M Y') : __('php/http/controllers/owner/owner_payouts_export_controller.date_pending'),
                    match ((string) $p->status) {
                        'pending' => __('php/http/controllers/owner/owner_payouts_export_controller.status_pending'),
                        'processing' => __('php/http/controllers/owner/owner_payouts_export_controller.status_processing'),
                        'paid' => __('php/http/controllers/owner/owner_payouts_export_controller.status_paid'),
                        'failed' => __('php/http/controllers/owner/owner_payouts_export_controller.status_failed'),
                        'reversed' => __('php/http/controllers/owner/owner_payouts_export_controller.status_reversed'),
                        'held' => __('php/http/controllers/owner/owner_payouts_export_controller.status_held'),
                        default => ucfirst((string) $p->status),
                    },
                    MoneyFormatter::display((int) $p->amount_minor, (string) $p->currency),
                ]));
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
