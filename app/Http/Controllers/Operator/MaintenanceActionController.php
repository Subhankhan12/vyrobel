<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceTicket;
use App\Models\Membership;
use App\Models\Unit;
use App\Services\MaintenanceService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Operator Maintenance writes — the REAL open + assign actions driven from the styled Maintenance board
 * (design pass · Maintenance UI) via POST → controller → {@see MaintenanceService} → redirect (PRG), the
 * same tenant-safe pattern as the housekeeping/front-desk mutations. A normal POST re-runs the web
 * middleware (SetTenantForWebRequest sets context), so every write is genuinely tenant-scoped, and both
 * `{ticket}`/`unit_id` are resolved through the tenant-scoped global scope AFTER the middleware — so a
 * cross-org id 404s. No schema/guarantee/availability path is touched here; the service does the work
 * (open() may take the unit out of service; assign() is guarded to active tickets only).
 *
 * Only the real `category`/`priority` enum values are accepted — an unknown value is rejected by
 * validation, never invented. A guarded illegal transition surfaces as a clean flash error, not a 500.
 */
class MaintenanceActionController extends Controller
{
    /** Open a new ticket on one of the org's OWN units. */
    public function store(Request $request, MaintenanceService $maintenance): RedirectResponse
    {
        $validated = $request->validate([
            'unit_id' => ['required', 'string'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', Rule::in(['plumbing', 'electrical', 'appliance', 'structural', 'other'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'takes_out_of_service' => ['sometimes', 'boolean'],
        ]);

        // Tenant-scope the unit: a foreign unit id 404s (never opens a ticket on another org's unit).
        $unit = Unit::query()->findOrFail($validated['unit_id']);

        $ticket = $maintenance->open([
            'unit_id' => $unit->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'takes_out_of_service' => (bool) ($validated['takes_out_of_service'] ?? false),
        ]);

        return redirect()->route('app.maintenance')
            ->with('mt_notice', "Ticket “{$ticket->title}” opened on unit {$unit->code}.");
    }

    /** Assign (or unassign) a ticket to an active member of the org. */
    public function assign(Request $request, MaintenanceService $maintenance, string $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'string'],
        ]);

        $model = MaintenanceTicket::query()->findOrFail($ticket);   // tenant-scoped → cross-org 404

        $userId = $validated['user_id'] ?? null;
        if ($userId !== null && $userId !== '') {
            // The assignee must be an active member of THIS org — a foreign/unknown user is rejected.
            $orgId = app(TenantContext::class)->id();
            $isMember = Membership::query()
                ->where('organization_id', $orgId)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->exists();

            if (! $isMember) {
                abort(422, 'That user is not a member of this organization.');
            }
        } else {
            $userId = null;   // "— Unassigned —"
        }

        try {
            $maintenance->assign($model, $userId);
        } catch (RuntimeException) {
            // e.g. the ticket is already resolved/cancelled — guarded, surfaced cleanly, not a 500.
            return redirect()->route('app.maintenance')
                ->with('mt_error', 'That ticket can’t be reassigned — it’s already closed.');
        }

        return redirect()->route('app.maintenance')
            ->with('mt_notice', 'Ticket assignment updated.');
    }
}
