<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Services\RoleAssignmentService;
use App\Services\RolePermissionService;
use App\Support\Operator\OperatorWorkspace;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator ROLES & PERMISSIONS (task #102) — the EDITABLE permission matrix + role list + members, matched to
 * `Operator Roles.html`. VIEW the real catalog roles + their DB permission grants + members; EDIT a role's
 * permissions (the toggles genuinely grant/revoke real capabilities via {@see RolePermissionService} — spatie
 * DB, mutation-provable); ASSIGN a role to a member ({@see RoleAssignmentService}).
 *
 * ⚠️ SENSITIVE: the matrix edit is gated `can:roles.manage` (org-admin: Property Owner + Super Admin — a tighter
 * tier than the `can:users.manage` page gate), enforces the editable-role / subset / last-admin bounds in the
 * service, and is audited. Wildcard/portal roles are LOCKED. New-role / delete-role are HONESTLY deferred: back-
 * office access is anchored on the config `back_office_roles` allow-list (the certified #26 gate), so the role
 * CATALOGUE is fixed — you edit each catalog role's permissions, you don't mint arbitrary ones here.
 */
#[Layout('components.layouts.operator')]
class OperatorRoles extends Component
{
    use FormatsOperatorData;

    public function render(RoleAssignmentService $roles, RolePermissionService $perms): View
    {
        $user = auth()->user();
        $canEdit = (bool) $user?->can('roles.manage');

        return view('livewire.operator.operator-roles', [
            'viewer' => $this->viewer(),
            'matrix' => $user !== null ? $perms->matrix($user) : ['roles' => [], 'sections' => [], 'permissions' => [], 'lockable' => []],
            'members' => $roles->orgMembers(),
            'assignableRoles' => $user !== null ? $roles->assignableRolesFor($user) : [],
            'canEdit' => $canEdit,
            'canAssign' => (bool) $user?->can('users.manage'),
            'readOnly' => app(OperatorWorkspace::class)->isReadOnly(),
        ]);
    }
}
