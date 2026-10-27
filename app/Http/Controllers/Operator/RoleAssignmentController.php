<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Exceptions\Authorization\RoleAssignmentException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RoleAssignmentService;
use App\Services\RolePermissionService;
use App\Support\Operator\OperatorWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Operator ROLES write (Phase 1 · Sub-gate 3) — assign / revoke a catalog back-office role on a team member.
 * The ROUTE carries `can:users.manage` (only an admin may manage roles — the same gate that closed the
 * owner-invite escalation, invariant #30); the DATA bounds (privilege-subset, no self-change, tenant, catalog
 * role) live in {@see RoleAssignmentService}. This controller NEVER touches the role→permission catalog — that
 * is code-defined and read-only; only the user↔role assignment is written.
 */
class RoleAssignmentController extends Controller
{
    /**
     * ⚠️⚠️ RE-AUTHENTICATE BEFORE CHANGING WHAT A BACK-OFFICE PRINCIPAL CAN DO (2026-08-30).
     *
     * assign · revoke · savePermissions all changed org-wide access with NO password. The owner-side fixes
     * protected one owner's slice; a back-office role reaches the WHOLE organisation.
     *
     * ⚠️ REPRODUCED, no password: Housekeeping granted "Front Desk"; the grant revoked again; and — the quiet
     * one — the Front Desk role stripped from 20 permissions to 2, which changes NO user record and NO
     * assignment while silently altering what EVERYONE holding that role can do.
     *
     * ⚠️ WHAT THIS DOES AND DOES NOT ACHIEVE. It stops a session that does not know the password. It does NOT
     * stop a hijacked session whose holder has the password, and it is NOT what prevents escalation — that is
     * {@see RolePermissionService}'s subset rule and {@see RoleAssignmentService}'s no-self-management rule,
     * both of which already refuse to grant beyond the actor's own access. This is a second factor on a
     * high-blast-radius action, not the boundary itself.
     *
     * ⚠️ THE BARE `current_password` — VERIFIED, not assumed: /app runs on plain `auth` and AUTH_GUARD
     * defaults to `web`, so `current_password:admin` would validate NOTHING here, silently.
     */
    private function reauthenticate(Request $request): void
    {
        $request->validate(
            ['current_password' => ['required', 'current_password']],
            [],
            ['current_password' => __('php/http/controllers/operator/role_assignment_controller.attr_current_password')],
        );
    }

    public function assign(Request $request, RoleAssignmentService $roles): RedirectResponse
    {
        return $this->manage($request, fn (User $actor, User $target, string $role) => $roles->assign($actor, $target, $role));
    }

    public function revoke(Request $request, RoleAssignmentService $roles): RedirectResponse
    {
        return $this->manage($request, fn (User $actor, User $target, string $role) => $roles->revoke($actor, $target, $role));
    }

    /**
     * Save the editable permission MATRIX for one role (task #102 · Decision RL1) — the toggled permissions
     * genuinely grant/revoke real capabilities (spatie DB). Route-gated `can:roles.manage` (org-admin); the DATA
     * bounds (editable-role, catalog-only, subset, last-admin) + the audit live in {@see RolePermissionService}.
     */
    public function savePermissions(Request $request, RolePermissionService $perms): RedirectResponse
    {
        if ($this->readOnly()) {
            return $this->back(__('php/http/controllers/operator/role_assignment_controller.read_only'));
        }

        /** @var User $actor */
        $actor = $request->user();

        $roleName = trim((string) $request->input('role'));
        /** @var list<string> $granted */
        $granted = array_values(array_filter((array) $request->input('permissions', []), 'is_string'));

        if ($roleName === '') {
            return $this->back(__('php/http/controllers/operator/role_assignment_controller.choose_role'));
        }

        $this->reauthenticate($request);   // ⚠️ the QUIET one — no user or assignment changes, yet everyone with the role is affected

        try {
            $result = $perms->save($actor, $roleName, $granted);
        } catch (RoleAssignmentException $e) {
            return $this->back($e->getMessage());   // an expected refusal (editable / subset / last-admin) — never a 500
        }

        $changed = $result['granted'] + $result['revoked'];

        return $this->back($changed === 0
            ? __('php/http/controllers/operator/role_assignment_controller.no_changes', ['role' => $roleName])
            : __('php/http/controllers/operator/role_assignment_controller.permissions_updated', ['role' => $roleName, 'granted' => $result['granted'], 'revoked' => $result['revoked']]));
    }

    /**
     * @param  callable(User, User, string): void  $action
     */
    private function manage(Request $request, callable $action): RedirectResponse
    {
        if ($this->readOnly()) {
            return $this->back(__('php/http/controllers/operator/role_assignment_controller.read_only'));
        }

        /** @var User $actor */
        $actor = $request->user();

        // ⚠️ Covers BOTH assign and revoke (each proven separately in the tests). Placed AFTER the actor is
        // resolved and BEFORE the target is looked up, so identity is checked before anything is disclosed.
        $this->reauthenticate($request);

        $target = User::query()->find((string) $request->input('user_id'));   // global lookup; the service tenant-checks
        $roleName = trim((string) $request->input('role'));

        if ($target === null || $roleName === '') {
            return $this->back(__('php/http/controllers/operator/role_assignment_controller.choose_member_and_role'));
        }

        try {
            $action($actor, $target, $roleName);
        } catch (RoleAssignmentException $e) {
            return $this->back($e->getMessage());   // an expected refusal (escalation / self / cross-org) — never a 500
        }

        return $this->back(__('php/http/controllers/operator/role_assignment_controller.role_updated', ['name' => $target->name]));
    }

    /** #102 — the role write path now respects the paused/read-only workspace (was a gap). */
    private function readOnly(): bool
    {
        return app(OperatorWorkspace::class)->isReadOnly();
    }

    private function back(string $notice): RedirectResponse
    {
        return redirect()->to(route('app.roles'))->with('roles_notice', $notice);
    }
}
