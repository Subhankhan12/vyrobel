<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceTicket;
use App\Models\Membership;
use App\Models\Unit;
use App\Services\MaintenanceService;
use App\Support\Money\Money;
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
 *
 * The lifecycle writes (start/resolve/cancel) are driven from the styled ticket-detail screen and each
 * is offered ONLY when the real current state allows it — but the service is still the authority: an
 * illegal transition is caught and surfaced as a clean flash, never a 500. Assign is shared by the board
 * and the detail; an `origin=detail` hint returns the operator to the detail after the write.
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
            return $this->backTo($request, $model)->with('mt_error', 'That ticket can’t be reassigned — it’s already closed.');
        }

        return $this->backTo($request, $model)->with('mt_notice', 'Ticket assignment updated.');
    }

    /** Start work on an OPEN ticket (guarded: illegal from any other state → clean flash). */
    public function start(MaintenanceService $maintenance, string $ticket): RedirectResponse
    {
        $model = MaintenanceTicket::query()->findOrFail($ticket);   // tenant-scoped → cross-org 404

        try {
            $maintenance->start($model);
        } catch (RuntimeException) {
            return redirect()->route('app.maintenance.show', $model->id)
                ->with('mt_error', 'That ticket can’t be started — it’s no longer open.');
        }

        return redirect()->route('app.maintenance.show', $model->id)
            ->with('mt_notice', 'Work started on this ticket.');
    }

    /**
     * Resolve an IN-PROGRESS ticket with an optional cost. On an OWNED unit a cost posts a deductible
     * owner expense through the certified path (the service decides — this controller only passes Money);
     * on an operator-owned unit no owner expense is created. Cost is captured in the property currency.
     */
    public function resolve(Request $request, MaintenanceService $maintenance, string $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $model = MaintenanceTicket::query()->with('unit.property')->findOrFail($ticket);   // tenant-scoped → 404

        $cost = null;
        $costInput = $validated['cost'] ?? null;
        if ($costInput !== null && (float) $costInput > 0) {
            $currency = (string) $model->unit->property->currency;
            $cost = Money::of((int) round(((float) $costInput) * 100), $currency);
        }

        try {
            $maintenance->resolve($model, $cost);
        } catch (RuntimeException) {
            return redirect()->route('app.maintenance.show', $model->id)
                ->with('mt_error', 'That ticket can’t be resolved — it isn’t in progress.');
        }

        return redirect()->route('app.maintenance.show', $model->id)
            ->with('mt_notice', 'Ticket resolved.');
    }

    /** Cancel an ACTIVE ticket (guarded: illegal from a terminal state → clean flash). */
    public function cancel(MaintenanceService $maintenance, string $ticket): RedirectResponse
    {
        $model = MaintenanceTicket::query()->findOrFail($ticket);   // tenant-scoped → cross-org 404

        try {
            $maintenance->cancel($model);
        } catch (RuntimeException) {
            return redirect()->route('app.maintenance.show', $model->id)
                ->with('mt_error', 'That ticket can’t be cancelled — it’s already closed.');
        }

        return redirect()->route('app.maintenance.show', $model->id)
            ->with('mt_notice', 'Ticket cancelled.');
    }

    /** Where an assign returns to: the ticket detail when it came from there, else the board. */
    private function backTo(Request $request, MaintenanceTicket $model): RedirectResponse
    {
        return $request->input('origin') === 'detail'
            ? redirect()->route('app.maintenance.show', $model->id)
            : redirect()->route('app.maintenance');
    }
}
