<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\OwnerPortalService;
use App\Support\Export\CsvSafe;
use App\Support\Exports\ExportAudit;
use App\Support\Owner\OwnerOccupancy;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Owner My Units — month occupancy CSV export (My Units · GATE 2). A REAL, owner-scoped download: one row per
 * day of the month with how many of the OWNER's OWN units were booked, from the shared {@see OwnerOccupancy}
 * derivation (physical-unit-scoped — a non-owner unit in the same room type is excluded, #28). Guest COUNTS
 * only — no guest names/PII in the export. Gated `exports` (co-owner + accountant); a non-linked owner's export
 * is an empty (headers-only) file — fail-closed, never all owners.
 */
class OwnerUnitsExportController extends Controller
{
    public function download(Request $request, OwnerPortalService $portal): StreamedResponse
    {
        $param = $request->query('month');
        $month = is_string($param) && preg_match('/^\d{4}-\d{2}$/', $param) === 1
            ? Carbon::parse($param.'-01')->startOfMonth()
            : Carbon::now()->startOfMonth();

        // ⚠️ DATA LEAVING THE PRODUCT, and it must match the screen it is exported from (My Units). Live
        // properties only — an archived property's unit would otherwise land in someone's spreadsheet as
        // current inventory, and skew the occupancy denominator with it.
        $unitIds = array_map('strval', $portal->unitsAtLiveProperties()->pluck('id')->all());
        $occ = OwnerOccupancy::forMonth($unitIds, $month);

        $filename = 'my-units-occupancy-'.$month->format('Y-m').'.csv';

        // Wave B · GATE 5 — record + watermark the download (who / when); the SAME identity in the log + the file's comment row.
        $watermark = app(ExportAudit::class)->stamp($request, 'units_csv', 'Units export');

        return response()->streamDownload(function () use ($occ, $month, $watermark): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, CsvSafe::row(['# '.$watermark]));
            fputcsv($out, CsvSafe::row([
                __('php/http/controllers/owner/owner_units_export_controller.col_date'),
                __('php/http/controllers/owner/owner_units_export_controller.col_units_booked'),
                __('php/http/controllers/owner/owner_units_export_controller.col_total_units'),
                __('php/http/controllers/owner/owner_units_export_controller.col_booked_pct'),
            ]));

            for ($d = 1; $d <= $occ['days']; $d++) {
                $count = $occ['heat'][$d] ?? 0;
                // D-OWNERSINCE-WINDOWS: the units the owner HAD that day (all of them, unless one was given to them part-way through the month)
                $held = $occ['held'][$d] ?? $occ['unit_count'];
                $pct = $held > 0 ? (int) round($count / $held * 100) : 0;
                fputcsv($out, CsvSafe::row([
                    $month->copy()->day($d)->toDateString(),
                    $count,
                    $held,
                    $pct.'%',
                ]));
            }

            fputcsv($out, CsvSafe::row([]));
            fputcsv($out, CsvSafe::row([
                __('php/http/controllers/owner/owner_units_export_controller.month_total'),
                trans_choice('php/http/controllers/owner/owner_units_export_controller.total_nights', (int) $occ['nights'], ['count' => $occ['nights']]),
                trans_choice('php/http/controllers/owner/owner_units_export_controller.total_units', (int) $occ['unit_count'], ['count' => $occ['unit_count']]),
                $occ['pct'].'%',
            ]));
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
