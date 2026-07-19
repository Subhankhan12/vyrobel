<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Exceptions\Authorization\RoleAssignmentException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RoleAssignmentService;
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
     * @param  callable(User, User, string): void  $action
     */
    private function manage(Request $request, callable $action): RedirectResponse
    {
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

    private function back(string $notice): RedirectResponse
    {
        return redirect()->to(route('app.roles'))->with('roles_notice', $notice);
    }
}
