<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\MaintenanceCostExportController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Services\MaintenanceCostService;
use App\Support\Maintenance\MaintenancePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator MAINTENANCE COSTS (Phase 1 · Sub-gate 4; rebuilt to the wireframe in #89) — cost analytics DERIVED
 * entirely from real resolved maintenance tickets ({@see MaintenanceCostService}). READ-ONLY; tenant-scoped;
 * gated on `maintenance.manage`. #89 makes the Labor/Parts split REAL (labor + parts === cost_minor, #17) and
 * the billable line items reconcile to what the owner is billed; an empty period shows "—", never a fabricated
 * €0.00. Real exports (PDF/CSV) + email-to-self live on the {@see MaintenanceCostExportController}.
 */
#[Layout('components.layouts.operator')]
class OperatorMaintenanceCosts extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    public function render(MaintenanceCostService $costs): View
    {
        // ⚠️ Scope + period resolved EXACTLY as MaintenanceCostExportController resolves them, so the PDF/CSV
        // can never cover a different window or property set than this page (#17). The read-set comes from
        // the property context (never a request-supplied id — that would be an isolation hole, #38).
        $preset = is_string(request()->query('period')) ? (string) request()->query('period') : '6m';
        $from = MaintenancePeriod::presetFrom($preset);
        $report = $costs->report($this->currentReadIds, $from, null);
        $currency = $report['currency'];

        return view('livewire.operator.operator-maintenance-costs', [
            'viewer' => $this->viewer(),
            'report' => $report,
            'period' => ['active' => $preset, 'options' => array_keys(MaintenancePeriod::PRESETS)],
            'hasData' => $report['orders_closed'] > 0,
            // A formatter the view can call (money() is protected on the trait); null → "—".
            'money' => fn (?int $minor): string => $minor === null ? '—' : $this->money($minor, $currency),
            // The shared category label (same English as the old ucfirst for every real category; ucfirst stays its fallback).
            'categoryLabel' => fn (string $c): string => $this->maintenanceCategoryLabel($c),
            'monthLabel' => fn (string $ym): string => CarbonImmutable::createFromFormat('Y-m', $ym)->translatedFormat('M Y'),
        ]);
    }
}
