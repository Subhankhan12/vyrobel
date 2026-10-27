<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Services\MaintenanceSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Operator MAINTENANCE SETTINGS write (Phase 1 · Sub-gate 4) — a REAL, typed persist (POST → service → PRG),
 * NOT a toast-only save. Gated on `maintenance.manage` (route). The service whitelists every column, bounds the
 * SLA hours, and verifies each technician is an org member; this controller just marshals the form into the
 * input array (checkbox absence → false, the standard semantics).
 */
class MaintenanceSettingsController extends Controller
{
    public function update(Request $request, MaintenanceSettingsService $settings): RedirectResponse
    {
        $input = [];

        foreach (['sla_urgent_hours', 'sla_high_hours', 'sla_normal_hours', 'sla_low_hours'] as $field) {
            if ($request->filled($field)) {
                $input[$field] = (int) $request->input($field);
            }
        }

        foreach ([
            'auto_assign_enabled', 'require_photo_on_resolve', 'block_room_from_sale', 'notify_front_desk',
            'notify_new_urgent', 'notify_out_of_service', 'notify_parts_arrive', 'notify_sla_breach', 'notify_daily_digest',
        ] as $field) {
            $input[$field] = $request->boolean($field);   // an unchecked box isn't submitted → false
        }

        foreach (MaintenanceSettingsService::CATEGORIES as $category) {
            $input['assignee_'.$category.'_id'] = (string) $request->input('assignee_'.$category.'_id', '');
        }

        $settings->update($input);

        return redirect()->to(route('app.maintenance.settings'))->with('maintenance_settings_notice', __('php/http/controllers/operator/maintenance_settings_controller.saved'));
    }
}
