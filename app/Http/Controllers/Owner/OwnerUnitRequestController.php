<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\OwnerUnitRequestService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Owner add-unit REQUEST write (Phase 2 · Sub-gate 6) — POST → {@see OwnerUnitRequestService} → PRG. SELF-ONLY:
 * the request is filed against the owner resolved by user_id (never a request id). It creates NO inventory — the
 * operator reviews the request and creates the real unit as their own action.
 */
class OwnerUnitRequestController extends Controller
{
    public function store(Request $request, OwnerUnitRequestService $units): RedirectResponse
    {
        $data = $request->validate([
            'requested_name' => ['required', 'string', 'max:120'],
            'requested_kind' => ['required', Rule::in(array_keys(OwnerUnitRequestService::KINDS))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $units->submit($request->user(), $data);
        } catch (ModelNotFoundException) {
            abort(404);
        }

        return redirect()->to(route('owner.add-unit'))
            ->with('unit_request_notice', 'Request sent — your management team will review it and be in touch.');
    }
}
