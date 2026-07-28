<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\Scopes\OwnerScope;
use App\Support\Money\MoneyFormatter;
use App\Support\Payouts\RailLabel;
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
    public function download(): StreamedResponse
    {
        $payouts = Payout::query()->orderByDesc('created_at')->get();

        // Month labels from each payout's source statement (owner-scoped by the same global scope).
        $months = OwnerStatement::query()
            ->whereIn('id', $payouts->pluck('owner_statement_id')->filter()->unique()->all())
            ->get()
            ->mapWithKeys(fn (OwnerStatement $s): array => [(string) $s->id => $s->period_start->format('M Y')]);

        $filename = 'payout-history-'.Carbon::now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($payouts, $months): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['Month', 'Rail', 'Date', 'Status', 'Amount']);

            foreach ($payouts as $p) {
                $when = $p->paid_at ?? $p->failed_at ?? ($p->status === 'pending' ? null : $p->created_at);

                fputcsv($out, [
                    $months[(string) $p->owner_statement_id] ?? '—',
                    RailLabel::for((string) $p->rail),
                    $when !== null ? Carbon::parse($when)->format('j M Y') : 'Pending',
                    ucfirst((string) $p->status),
                    MoneyFormatter::display((int) $p->amount_minor, (string) $p->currency),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
