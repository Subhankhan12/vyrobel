<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\Unit;
use App\Services\OwnerInviteService;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Owner\OwnerListParams;
use App\Support\Property\PropertyContext;
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
 * Three roles are accepted — `owner` (an external Owner party → the portal) and the two STAFF roles
 * `manager`/`accountant` (a Membership + real spatie role → the operator app). The service maps the
 * staff slugs to the exact catalog roles (Hotel Manager / Accountant) and validates they exist. The
 * server never trusts the client: an unknown role is rejected by validation, owner-only fields are
 * dropped for staff, and an email that already belongs to an active user surfaces as a clean flash
 * error (not a 500).
 *
 * ⚠️⚠️ 2026-10-01 — A UNIT THAT HAS AN OWNER IS NEVER GIVEN THROUGH AN INVITE. A unit's owner decides who is paid for it;
 * the dialog offered owned units and acceptance wrote the new owner over the old one. Such a unit is now refused here with
 * a sentence (and acceptance re-checks, {@see OwnerInviteService::accept()}). The units are also kept inside the person's
 * properties — the `readIds()` rule every unit and rate write follows since DEC-k and DEC-l.
 */
class InviteOwnerController extends Controller
{
    public function store(Request $request, OwnerInviteService $invites): RedirectResponse
    {
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->back($request)->withInput($request->except('current_password'))
                ->with('owners_error', 'Workspace paused — inviting people is read-only for now.');
        }

        // ⚠️ RE-AUTH (2026-08-30): this mints a NEW principal — `manager`/`accountant` map to real back-office
        // roles (Hotel Manager / Accountant), so it changes who can reach the whole organisation.
        // Bare `current_password`: /app runs on the default `web` guard (verified).
        $request->validate(
            ['current_password' => ['required', 'current_password']],
            [
                'current_password.required' => 'Enter your password to send the invite.',
                'current_password.current_password' => 'That password isn’t right — enter the one you sign in with.',
            ],
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'role' => ['required', Rule::in(['owner', 'manager', 'accountant'])],
            'commission_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'unit_ids' => ['sometimes', 'array'],
            'unit_ids.*' => ['string'],
        ], [
            'name.required' => 'Enter the person’s or company’s name.',
            'email.required' => 'Enter the email address the invite goes to.',
            'email.email' => 'That doesn’t look like an email address.',
            'commission_pct.numeric' => 'The commission must be a number between 0 and 100.',
            'commission_pct.min' => 'The commission must be a number between 0 and 100.',
            'commission_pct.max' => 'The commission must be a number between 0 and 100.',
        ]);

        $isOwner = $validated['role'] === 'owner';

        // Owner-only fields (assigned units + commission) apply ONLY to owner invites; a staff invite never carries them.
        // The units are this organisation's (the tenant scope drops a foreign id) and inside the person's properties.
        $unitIds = [];
        if ($isOwner && ($validated['unit_ids'] ?? []) !== []) {
            $scope = app(PropertyContext::class)->readIds();
            $units = Unit::query()->whereIn('id', $validated['unit_ids'])
                ->when($scope !== [], fn ($q) => $q->whereIn('property_id', $scope))
                ->get(['id', 'code', 'name', 'owner_id']);

            $owned = $units->filter(fn (Unit $u): bool => $u->owner_id !== null);
            if ($owned->isNotEmpty()) {
                $names = Owner::query()->whereIn('id', $owned->pluck('owner_id'))->pluck('name', 'id');
                $which = $owned->map(fn (Unit $u): string => ($u->name ?: $u->code).' (owned by '.($names[$u->owner_id] ?? 'another owner').')')->implode(', ');

                return $this->back($request)->withInput($request->except('current_password'))->withErrors(['unit_ids' => $which
                    .' already '.($owned->count() === 1 ? 'has an owner' : 'have owners')
                    .'. A unit’s owner decides who is paid for it, so an invite can only give units that have none.']);
            }
            $unitIds = $units->pluck('id')->map(fn ($id): string => (string) $id)->all();
        }
        $commissionPct = $isOwner && isset($validated['commission_pct']) ? (float) $validated['commission_pct'] : null;

        try {
            $result = $invites->invite($validated['name'], $validated['email'], $unitIds, $commissionPct, $validated['role']);
        } catch (RuntimeException) {
            // e.g. the email already belongs to an active account, or an unmappable role — graceful, not a crash.
            return $this->back($request)->withInput($request->except('current_password'))
                ->with('owners_error', 'That email can’t be invited — it may already have a Vyrobel account.');
        }

        // Feed the "Invite sent" confirmation view (rendered on this PRG): the real invited email + the real 7-day expiry.
        return (OwnerListParams::redirect($request) ?? redirect()->route('app.owners'))
            ->with('owners_notice', 'Invite sent to '.$validated['email'].'.')
            ->with('owner_invite_sent', [
                'email' => $validated['email'],
                'expires' => $result['invite']->expires_at?->format('j M Y'),
            ]);
    }

    /** Back to the list as the operator had it when the dialog was opened there; otherwise back. */
    private function back(Request $request): RedirectResponse
    {
        return OwnerListParams::redirect($request) ?? back();
    }
}
