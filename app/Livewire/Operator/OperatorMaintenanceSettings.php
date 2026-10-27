<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\MaintenanceSettingsController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Services\MaintenanceSettingsService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator MAINTENANCE SETTINGS (Phase 1 · Sub-gate 4; rebuilt to the wireframe in #89) — SLA resolution
 * targets, the auto-assignment map + master toggle, the Technicians panel, the out-of-order policy, and
 * notification preferences. READ view; the save is a guarded POST → {@see MaintenanceSettingsController} →
 * {@see MaintenanceSettingsService} (typed columns, real persistence — never toast-only). Gated on
 * `maintenance.manage`.
 *
 * ⚠️ Honesty (#89): the SLA targets drive a REAL breach shown on the board, and when the auto-assignment master
 * toggle is on a new order is REALLY routed to its category's technician. The notification toggles store the
 * preference but maintenance does not dispatch an alert yet (no maintenance notifier is wired); "require a photo
 * on resolve" is stored but resolve does not yet enforce it — both shown honestly, never faked. Inviting a
 * technician links to the real staff-invite flow (no fabricated technician role).
 */
#[Layout('components.layouts.operator')]
class OperatorMaintenanceSettings extends Component
{
    use FormatsOperatorData;

    public function render(MaintenanceSettingsService $settings): View
    {
        return view('livewire.operator.operator-maintenance-settings', [
            'viewer' => $this->viewer(),
            'settings' => $settings->forOrg(),
            'technicians' => $settings->technicians(),
            'technicianPanel' => $settings->technicianPanel(),
            'categories' => MaintenanceSettingsService::CATEGORIES,
            // The shared category label (same English as the old ucfirst for every category above).
            'categoryLabel' => fn (string $c): string => $this->maintenanceCategoryLabel($c),
        ]);
    }
}
