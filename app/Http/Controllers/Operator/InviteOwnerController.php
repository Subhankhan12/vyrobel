<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\OwnerInviteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Operator "invite owner" write (Auth Phase B · Step 4). The proven operator-mutation pattern:
 * POST → controller → OwnerInviteService → PRG. The POST re-runs the web middleware, so the tenant
 * context is set — the invite (and the units it may assign) are created org-scoped. Rate-limited +
 * CSRF-protected via the route/web group.
 *
 * Only the `owner` role is accepted (Manager/Account are disabled in the UI AND rejected here — the
 * server never trusts the client). An email that already belongs to an active user surfaces as a clean
 * flash error, not a 500.
 */
class InviteOwnerController extends Controller
{
    public function store(Request $request, OwnerInviteService $invites): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'role' => ['required', Rule::in(['owner'])],   // only owner is wired in Phase B
            'commission_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'unit_ids' => ['sometimes', 'array'],
            'unit_ids.*' => ['string'],
        ]);

        // Tenant-scope the assigned units: only THIS org's units survive, so a foreign unit id (another
        // org's inventory) is silently dropped — never assigned across orgs.
        $unitIds = Unit::query()
            ->whereIn('id', $validated['unit_ids'] ?? [])
            ->pluck('id')
            ->map(fn ($id): string => (string) $id)
            ->all();

        try {
            $invites->invite(
                $validated['name'],
                $validated['email'],
                $unitIds,
                isset($validated['commission_pct']) ? (float) $validated['commission_pct'] : null,
                'owner',
            );
        } catch (RuntimeException) {
            // e.g. the email already belongs to an active account — graceful, not a crash.
            return back()->withInput()->with('owners_error', 'That email can’t be invited — it may already have a Vyrobel account.');
        }

        return redirect()
            ->route('app.owners')
            ->with('owners_notice', 'Invite sent to '.$validated['email'].'.');
    }
}
