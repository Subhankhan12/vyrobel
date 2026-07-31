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
            return $this->back('Your workspace is read-only right now — role changes are paused.');
        }

        /** @var User $actor */
        $actor = $request->user();

        $roleName = trim((string) $request->input('role'));
        /** @var list<string> $granted */
        $granted = array_values(array_filter((array) $request->input('permissions', []), 'is_string'));

        if ($roleName === '') {
            return $this->back('Choose a role to edit.');
        }

        try {
            $result = $perms->save($actor, $roleName, $granted);
        } catch (RoleAssignmentException $e) {
            return $this->back($e->getMessage());   // an expected refusal (editable / subset / last-admin) — never a 500
        }

        $changed = $result['granted'] + $result['revoked'];

        return $this->back($changed === 0
            ? 'No changes to “'.$roleName.'”.'
            : '“'.$roleName.'” updated — '.$result['granted'].' granted, '.$result['revoked'].' revoked.');
    }

    /**
     * @param  callable(User, User, string): void  $action
     */
    private function manage(Request $request, callable $action): RedirectResponse
    {
        if ($this->readOnly()) {
            return $this->back('Your workspace is read-only right now — role changes are paused.');
        }

        /** @var User $actor */
        $actor = $request->user();

        $target = User::query()->find((string) $request->input('user_id'));   // global lookup; the service tenant-checks
        $roleName = trim((string) $request->input('role'));

        if ($target === null || $roleName === '') {
            return $this->back('Choose a team member and a role.');
        }

        try {
            $action($actor, $target, $roleName);
        } catch (RoleAssignmentException $e) {
            return $this->back($e->getMessage());   // an expected refusal (escalation / self / cross-org) — never a 500
        }

        return $this->back('“'.$target->name.'” — role updated.');
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
