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
 * Three roles are accepted — `owner` (an external Owner party → the portal) and the two STAFF roles
 * `manager`/`accountant` (a Membership + real spatie role → the operator app). The service maps the
 * staff slugs to the exact catalog roles (Hotel Manager / Accountant) and validates they exist. The
 * server never trusts the client: an unknown role is rejected by validation, owner-only fields are
 * dropped for staff, and an email that already belongs to an active user surfaces as a clean flash
 * error (not a 500).
 */
class InviteOwnerController extends Controller
{
    public function store(Request $request, OwnerInviteService $invites): RedirectResponse
    {
        // ⚠️ RE-AUTH (2026-08-30): this mints a NEW principal — `manager`/`accountant` map to real back-office
        // roles (Hotel Manager / Accountant), so it changes who can reach the whole organisation.
        // Bare `current_password`: /app runs on the default `web` guard (verified).
        $request->validate(
            ['current_password' => ['required', 'current_password']],
            [],
            ['current_password' => 'current password'],
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'role' => ['required', Rule::in(['owner', 'manager', 'accountant'])],
            'commission_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'unit_ids' => ['sometimes', 'array'],
            'unit_ids.*' => ['string'],
        ]);

        $isOwner = $validated['role'] === 'owner';

        // Owner-only fields (assigned units + commission) apply ONLY to owner invites; a staff invite
        // never carries them (the modal hides them, and we drop them here too — defense-in-depth).
        // Tenant-scope the units: only THIS org's units survive, so a foreign unit id is silently dropped.
        $unitIds = $isOwner
            ? Unit::query()->whereIn('id', $validated['unit_ids'] ?? [])->pluck('id')->map(fn ($id): string => (string) $id)->all()
            : [];
        $commissionPct = $isOwner && isset($validated['commission_pct']) ? (float) $validated['commission_pct'] : null;

        try {
            $result = $invites->invite($validated['name'], $validated['email'], $unitIds, $commissionPct, $validated['role']);
        } catch (RuntimeException) {
            // e.g. the email already belongs to an active account, or an unmappable role — graceful, not a crash.
            return back()->withInput()->with('owners_error', 'That email can’t be invited — it may already have a Vyrobel account.');
        }

        // Feed the exact-match "Invite sent" confirmation view (rendered on this PRG): the real invited email +
        // the real 7-day expiry from the invite record. Structured flash (not derivable from owners_notice).
        return redirect()
            ->route('app.owners')
            ->with('owners_notice', 'Invite sent to '.$validated['email'].'.')
            ->with('owner_invite_sent', [
                'email' => $validated['email'],
                'expires' => $result['invite']->expires_at?->format('j M Y'),
            ]);
    }
}
