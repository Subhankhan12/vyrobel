<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Exceptions\Owner\UnitAssignmentRefused;
use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Services\UnitOwnerAssignmentService;
use App\Support\I18n\LocalDate;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Property\PropertyScope;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * ⚠️⚠️ MONEY PATH — DEC-p (2026-11-12): give an EXISTING owner a unit that has no owner, from a chosen day. POST → this → {@see UnitOwnerAssignmentService}
 * → PRG, back to the owner's page. A unit's owner decides who is paid for its stays, so every layer is on:
 *
 *   · `can:users.manage` + `throttle` on the ROUTE (#30) — the same gate as every other owner write, and no new permission;
 *   · the workspace read-only guard, then the owner (tenant-scoped: another organisation's owner is a 404 before anything is read);
 *   · the unit resolved INSIDE the actor's properties ({@see PropertyScope::unit()}) — a unit outside them is a 404 (DEC-k), never a "you can't";
 *   · the operator's CURRENT PASSWORD, as sending an invite asks (this changes whose income a unit is);
 *   · the service re-checks every rule inside one transaction under the unit's lock, and says plainly what refused it.
 *
 * A refusal reopens the dialog on the owner's page with the sentence in it and what was typed (`_form` = `give`).
 */
class OwnerUnitAssignController extends Controller
{
    public function store(Request $request, string $owner, UnitOwnerAssignmentService $service): RedirectResponse
    {
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->back($owner, 'owners_error', $this->pausedMessage());
        }

        // tenant-scoped: another organisation's owner — and an archived one — is a 404 BEFORE anything is validated (no existence oracle)
        $model = Owner::query()->findOrFail($owner);

        $data = $request->validate([
            'unit_id' => ['required', 'string'],
            'since' => ['nullable', 'string', 'date_format:Y-m-d'],
        ], [
            'unit_id.required' => __('php/http/controllers/operator/owner_unit_assign_controller.unit_required'),
            'since.date_format' => __('php/http/controllers/operator/owner_unit_assign_controller.date_invalid'),
        ]);

        // ⚠️ INSIDE THE ACTOR'S PROPERTIES (DEC-i / DEC-k): another property's unit id is a 404, whatever else was sent — never a "you can't"
        $unit = app(PropertyScope::class)->unit((string) $data['unit_id']);

        // ⚠️ RE-AUTH — bare `current_password` (/app runs on the default `web` guard), AFTER the lookups so a foreign id 404s whether or not one is sent
        $request->validate(['current_password' => ['required', 'current_password']], [
            'current_password.required' => __('php/http/controllers/operator/owner_unit_assign_controller.password_required'),
            'current_password.current_password' => __('php/http/controllers/operator/owner_unit_assign_controller.password_wrong'),
        ]);

        try {
            $result = $service->assign((string) $unit->getKey(), (string) $model->getKey(), ($data['since'] ?? '') !== '' ? (string) $data['since'] : null, $request->user() !== null ? (string) $request->user()->getAuthIdentifier() : null);
        } catch (UnitAssignmentRefused $e) {
            return $this->back($owner, 'owners_error', $e->getMessage())->withInput($request->only('_form', 'unit_id', 'since'));
        }

        $name = (string) ($unit->name !== null && (string) $unit->name !== '' ? $unit->name : $unit->code);
        $notice = __('php/http/controllers/operator/owner_unit_assign_controller.given', [
            'unit' => $name, 'owner' => (string) $model->name,
            'date' => CarbonImmutable::parse($result['since'])->translatedFormat(LocalDate::pattern('j M Y')),
        ]);
        if ($result['attributed'] > 0) {
            $notice .= ' '.trans_choice('php/http/controllers/operator/owner_unit_assign_controller.stays_worked_out', $result['attributed'], ['count' => $result['attributed'], 'owner' => (string) $model->name]);
        }
        if ($result['waiting'] > 0) {
            $notice .= ' '.trans_choice('php/http/controllers/operator/owner_unit_assign_controller.stays_waiting', $result['waiting'], ['count' => $result['waiting']]);
        }

        return $this->back($owner, 'owners_notice', $notice);
    }

    private function pausedMessage(): string
    {
        $until = app(OperatorWorkspace::class)->resumeLabel();

        return $until !== null && $until !== ''
            ? __('php/http/controllers/operator/owner_unit_assign_controller.workspace_paused_until', ['date' => $until])
            : __('php/http/controllers/operator/owner_unit_assign_controller.workspace_paused_for_now');
    }

    private function back(string $owner, string $key, string $message): RedirectResponse
    {
        return redirect()->route('app.owners.show', $owner)->with($key, $message);
    }
}
