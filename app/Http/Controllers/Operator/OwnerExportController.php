<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Support\Events\AuditEventRecorder;
use App\Support\Export\CsvSafe;
use App\Support\Owner\OwnerListParams;
use App\Support\Owner\OwnerRows;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The Owners page's CSV (2026-10-01) — the owners IN VIEW (the same filter, search and order as the page, every page of it),
 * built from {@see OwnerRows}, the rows the page shows, so the file cannot disagree with the screen. `reports.view` (the
 * route) — the page's own read gate, owner finances included. Every cell through {@see CsvSafe} (a name starting `=` is a
 * formula in a spreadsheet), and every download is recorded: what was taken and how many rows, never the rows themselves.
 */
class OwnerExportController extends Controller
{
    public function download(Request $request, OwnerRows $rows, AuditEventRecorder $audit): StreamedResponse
    {
        $clean = OwnerListParams::clean((array) $request->query());
        $sort = $clean['sort'] ?? 'name';
        $all = $rows->load();
        $picked = $rows->sort(
            array_values(array_filter($all, fn (array $r): bool => $rows->matches($r, $clean['filter'] ?? null, $clean['q'] ?? ''))),
            $sort,
            $clean['dir'] ?? OwnerListParams::SORTS[$sort],
        );

        $audit->record('owner.list_exported', null, null, ['rows' => count($picked), 'filter' => $clean['filter'] ?? null, 'searched' => isset($clean['q'])]);

        return response()->streamDownload(function () use ($picked): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, CsvSafe::row([
                __('php/http/controllers/operator/owner_export_controller.owner'), __('php/http/controllers/operator/owner_export_controller.email'), __('php/http/controllers/operator/owner_export_controller.status'), __('php/http/controllers/operator/owner_export_controller.units'), __('php/http/controllers/operator/owner_export_controller.commission'), __('php/http/controllers/operator/owner_export_controller.statement'), __('php/http/controllers/operator/owner_export_controller.net_payable'), __('php/http/controllers/operator/owner_export_controller.reason'),
            ]));
            foreach ($picked as $r) {
                fputcsv($out, CsvSafe::row([
                    $r['name'], $r['email'], $r['pay_label'], implode(', ', $r['unit_labels']), $r['commission'],
                    $r['period'] ?? '', $r['net_minor'] !== null ? $r['net'] : '', $r['reason'] ?? '',
                ]));
            }
            fclose($out);
        }, 'owners-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
