<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Support\Events\AuditEventRecorder;
use App\Support\Export\CsvSafe;
use App\Support\Property\PropertyContext;
use App\Support\Unit\UnitListParams;
use App\Support\Unit\UnitRows;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The Units page's export (2026-09-30) — it replaces the "Export · Soon" that did nothing. A READ, gated `can:units.view`
 * on the route (#30) and offered only to a role holding it. Two doors, one file shape:
 *   · {@see view()} — what the page shows: the same filter, search and order, every page of it;
 *   · {@see selected()} — the units ticked in select mode.
 * Both read the ONE {@see UnitRows}, inside the viewer's accessible properties, so a CSV cannot list a view differently
 * from the screen, and an id from elsewhere is simply absent.
 *
 * ⚠️ NO ARRIVAL DATA, NOT EVEN AS A FLAG. A door code, a wifi password and arrival notes are credentials and directions to
 * a physical space; a spreadsheet travels further than a screen. The file holds identity, capacity and states only.
 * One audit row per download, with the count (the Guests export's shape).
 */
class UnitExportController extends Controller
{
    public function view(Request $request, UnitRows $rows, AuditEventRecorder $audit): StreamedResponse
    {
        $clean = UnitListParams::clean((array) $request->query());
        $sort = $clean['sort'] ?? 'unit';

        $all = $rows->load(app(PropertyContext::class)->readIds());
        $shown = array_values(array_filter($all, fn (array $r): bool => $rows->matches($r, $clean['filter'] ?? null, $clean['q'] ?? '')));

        return $this->download($rows->sort($shown, $sort, $clean['dir'] ?? UnitListParams::SORTS[$sort]), $audit);
    }

    public function selected(Request $request, UnitRows $rows, AuditEventRecorder $audit): StreamedResponse
    {
        $data = $request->validate([
            'unit_ids' => ['required', 'array', 'min:1', 'max:500'],
            'unit_ids.*' => ['string'],
        ]);

        $ids = array_map('strval', $data['unit_ids']);
        $picked = array_values(array_filter($rows->load(app(PropertyContext::class)->readIds()), fn (array $r): bool => in_array($r['id'], $ids, true)));

        return $this->download($rows->sort($picked, 'unit', 'asc'), $audit);
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function download(array $rows, AuditEventRecorder $audit): StreamedResponse
    {
        $audit->record('unit.exported', null, null, ['count' => count($rows)]);

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, CsvSafe::row([__('php/http/controllers/operator/unit_export_controller.unit'), __('php/http/controllers/operator/unit_export_controller.name'), __('php/http/controllers/operator/unit_export_controller.room_type'), __('php/http/controllers/operator/unit_export_controller.kind'), __('php/http/controllers/operator/unit_export_controller.sleeps'), __('php/http/controllers/operator/unit_export_controller.housekeeping'), __('php/http/controllers/operator/unit_export_controller.condition'), __('php/http/controllers/operator/unit_export_controller.sales'), __('php/http/controllers/operator/unit_export_controller.owner')]));
            foreach ($rows as $r) {
                fputcsv($out, CsvSafe::row([
                    (string) $r['code'],
                    (string) ($r['name'] ?? ''),
                    (string) $r['room_type'],
                    (string) $r['kind'],
                    (string) $r['occupancy'],
                    (string) $r['hk_label'],
                    (string) $r['condition_label'],
                    $r['is_active'] ? __('php/http/controllers/operator/unit_export_controller.taking_bookings') : __('php/http/controllers/operator/unit_export_controller.paused'),
                    (string) ($r['owner'] ?? __('php/http/controllers/operator/unit_export_controller.operator_owned')),
                ]));
            }
            fclose($out);
        }, 'Vyrobel-units-'.count($rows).'.csv', ['Content-Type' => 'text/csv']);
    }
}
