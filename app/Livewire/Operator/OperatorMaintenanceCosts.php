<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Services\MaintenanceCostService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator MAINTENANCE COSTS (Phase 1 · Sub-gate 4) — cost analytics DERIVED entirely from real resolved
 * maintenance tickets ({@see MaintenanceCostService}). READ-ONLY; tenant-scoped; gated on `maintenance.manage`.
 * The prototype's Labor/Parts split is fabricated (one `cost_minor` per ticket) and is NOT rendered; an empty
 * period shows "—", never a fabricated €0.00.
 */
#[Layout('components.layouts.operator')]
class OperatorMaintenanceCosts extends Component
{
    use FormatsOperatorData;

    public function render(MaintenanceCostService $costs): View
    {
        $report = $costs->report();
        $currency = $report['currency'];

        return view('livewire.operator.operator-maintenance-costs', [
            'viewer' => $this->viewer(),
            'report' => $report,
            'hasData' => $report['orders_closed'] > 0,
            // A formatter the view can call (money() is protected on the trait); null → "—".
            'money' => fn (?int $minor): string => $minor === null ? '—' : $this->money($minor, $currency),
            'categoryLabel' => fn (string $c): string => ucfirst($c),
            'monthLabel' => fn (string $ym): string => CarbonImmutable::createFromFormat('Y-m', $ym)->format('M Y'),
        ]);
    }
}
