<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\UnitRequest;
use App\Services\OwnerUnitRequestService;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Property\PropertyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * #104 GATE 1 — the operator side of the owner Add-Unit request, made OPERATIONAL. The review screen was
 * read-only (a request could never leave `pending`); this controller lets the operator APPROVE (→ create the real
 * unit) or DECLINE (→ notify the owner) as their own action, through the certified {@see OwnerUnitRequestService}.
 *
 * The locked operator-mutation pattern: POST → controller → service → redirect (PRG). The {request} is a string
 * id resolved through the tenant-scoped global scope (never RMB), so a cross-org id 404s. Both actions are gated
 * `can:units.manage` at the route (the controller carries no authorize(); the route middleware is the actor gate,
 * #20/#30) and refuse a read-only (paused/lapsed) workspace.
 */
class UnitRequestActionController extends Controller
{
    public function approve(Request $request, string $unitRequest, OwnerUnitRequestService $service): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        $model = UnitRequest::query()->findOrFail($unitRequest);

        $data = $request->validate([
            'property_id' => ['required', 'string'],
            'room_type_id' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'property_id.required' => __('php/http/controllers/operator/unit_request_action_controller.choose_property'),
            'room_type_id.required' => __('php/http/controllers/operator/unit_request_action_controller.choose_room_type'),
            'note.max' => __('php/http/controllers/operator/unit_request_action_controller.note_max'),
        ]);

        // ⚠️⚠️ DEC-k (2026-10-03, the rule he ruled for every unit write on 2026-09-30): the unit is created only in a property
        //    the person works in — `PropertyContext::readIds()`, the set the page offers. 🔬 It was found through the tenant
        //    alone, so staff limited to one property could create a unit in another by posting its id (UnitRequestsPageTest).
        //    Another property is NOT FOUND — a 404, not merely hidden — and nothing is written.
        $ids = app(PropertyContext::class)->readIds();
        $property = Property::query()->when($ids !== [], fn ($q) => $q->whereIn('id', $ids))->findOrFail($data['property_id']);

        try {
            $unit = $service->approve($model, $request->user(), (string) $property->getKey(), $data['room_type_id'], $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return $this->back('error', $e->getMessage());
        }

        return $this->back('notice', __('php/http/controllers/operator/unit_request_action_controller.unit_created', ['unit' => $unit->name, 'owner' => $model->owner->name ?? __('php/http/controllers/operator/unit_request_action_controller.the_owner')]));
    }

    public function decline(Request $request, string $unitRequest, OwnerUnitRequestService $service): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly()) {
            return $readOnly;
        }

        $model = UnitRequest::query()->findOrFail($unitRequest);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'note.max' => __('php/http/controllers/operator/unit_request_action_controller.reason_max'),
        ]);

        try {
            $service->decline($model, $request->user(), $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return $this->back('error', $e->getMessage());
        }

        // ⚠️ (2026-10-03) it said "the owner has been notified" — untrue for an owner with no portal login, or one who has
        //    turned these notices off; the dialog says which before it is sent
        return $this->back('notice', __('php/http/controllers/operator/unit_request_action_controller.request_declined', ['name' => $model->requested_name]));
    }

    private function guardReadOnly(): ?RedirectResponse
    {
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->back('error', __('php/http/controllers/operator/unit_request_action_controller.read_only'));
        }

        return null;
    }

    private function back(string $key, string $message): RedirectResponse
    {
        return redirect()->to(route('app.unit-requests'))->with('unit_request_'.$key, $message);
    }
}
