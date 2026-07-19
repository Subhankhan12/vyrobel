<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Services\RoleAssignmentService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator ROLES screen (Phase 1 · Sub-gate 3) — the security-first redesign. VIEW the fixed catalog roles +
 * their READ-ONLY permission grants + members, and ASSIGN an existing role to a team member. The whole page is
 * gated on `can:users.manage` (the route), so only an admin reaches it.
 *
 * ⚠️ What this screen deliberately DOES NOT do (the design's escalation weapon, refused): create a role, EDIT a
 * role's permissions ("Save changes"), or delete a role. The role→permission catalog is CODE-DEFINED
 * (`config/authorization.php`) and is the source of truth — it is shown read-only, never written from the UI.
 * The only mutation is the user↔role ASSIGNMENT, and even that is bounded by the privilege-subset rule in
 * {@see RoleAssignmentService} (you can't grant a role with more access than your own).
 */
#[Layout('components.layouts.operator')]
class OperatorRoles extends Component
{
    use FormatsOperatorData;

    public function render(RoleAssignmentService $roles): View
    {
        $user = auth()->user();

        return view('livewire.operator.operator-roles', [
            'viewer' => $this->viewer(),
            'roles' => $roles->overview(),
            'members' => $roles->orgMembers(),
            'assignableRoles' => $user !== null ? $roles->assignableRolesFor($user) : [],
        ]);
    }
}
