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
 * Operator MAINTENANCE SETTINGS (Phase 1 · Sub-gate 4) — SLA targets, the category→technician map, the
 * out-of-order policy, and notification preferences. READ view; the save is a guarded POST →
 * {@see MaintenanceSettingsController} → {@see MaintenanceSettingsService}
 * (typed columns, real persistence — never toast-only). Gated on `maintenance.manage`.
 *
 * ⚠️ Honesty: the SLA targets + category→technician map take effect on new work orders. The notification
 * toggles store the preference but the live dispatch waits on the Phase-3 notification subsystem; "require a
 * photo on resolve" is stored but its enforcement waits on ticket photos — both are shown as honest "not yet
 * live" in the UI, never faked.
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
            'categories' => MaintenanceSettingsService::CATEGORIES,
        ]);
    }
}
