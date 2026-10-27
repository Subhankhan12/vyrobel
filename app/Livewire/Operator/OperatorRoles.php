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

        $matrix = $user !== null ? $perms->matrix($user) : ['roles' => [], 'sections' => [], 'permissions' => [], 'lockable' => []];
        $assignableRoles = $user !== null ? $roles->assignableRolesFor($user) : [];
        // ⚠️ M-14 — the data behind the REVOCATION control the page has always told operators to use.
        $assignments = $user !== null ? $roles->currentAssignments($user) : [];

        // I18N — the DISPLAYED words only. Every code the page and the form rely on (a role's `name`, its derived
        // `level`, a section's `section`, a section's `domain`, a permission's `key`) is left exactly as the service
        // built it; the translations ride beside them in their own fields (or replace a field that is only ever read).
        $matrix['roles'] = array_map(fn (array $role): array => array_merge($role, [
            'display_name' => $this->roleLabel((string) $role['name']),
            'desc' => $this->roleDescription((string) $role['name']),
            'level_label' => $this->levelLabel((string) $role['level']),
            'members_label' => trans_choice('php/livewire/operator/operator_roles.members', (int) $role['member_count'], ['count' => (int) $role['member_count']]),
        ]), $matrix['roles']);
        $matrix['sections'] = array_map(fn (array $section): array => array_merge($section, [
            'section_label' => $this->sectionLabel((string) ($section['permissions'][0]['resource'] ?? ''), (string) $section['section']),
            'permissions' => array_map(fn (array $perm): array => array_merge($perm, [
                'label' => $this->permissionLabel((string) $perm['key'], (string) $perm['label']),
            ]), $section['permissions']),
        ]), $matrix['sections']);

        $domainLabels = [];
        foreach (RolePermissionService::DOMAIN_ORDER as $domain) {
            $domainLabels[$domain] = $this->domainLabel($domain);
        }

        // ⚠️ The "N permissions granted" line moves as the toggles do, so its plural is worked out HERE for every
        // count the page can reach and the script only picks from the map (I18N rule 12).
        $maxGranted = count($matrix['permissions']);
        foreach ($matrix['roles'] as $role) {
            $maxGranted = max($maxGranted, count((array) $role['granted']) + count($matrix['permissions']));
        }
        $grantedLabels = [];
        for ($n = 0; $n <= $maxGranted; $n++) {
            $grantedLabels[$n] = trans_choice('php/livewire/operator/operator_roles.permissions_granted', $n, ['count' => $n]);
        }

        $roleLabels = [];
        foreach (array_merge($assignableRoles, array_column($assignments, 'role')) as $roleName) {
            $roleLabels[$roleName] = $this->roleLabel((string) $roleName);
        }

        return view('livewire.operator.operator-roles', [
            'viewer' => $this->viewer(),
            'matrix' => $matrix,
            'members' => $roles->orgMembers(),
            'assignableRoles' => $assignableRoles,
            'assignments' => $assignments,
            'domainLabels' => $domainLabels,
            'grantedLabels' => $grantedLabels,
            'roleLabels' => $roleLabels,
            'canEdit' => $canEdit,
            'canAssign' => (bool) $user?->can('users.manage'),
            'readOnly' => app(OperatorWorkspace::class)->isReadOnly(),
        ]);
    }

    /** A role's displayed name, keyed by its spatie name (the identifier stays untouched in data and logic). */
    private function roleLabel(string $roleName): string
    {
        return match ($roleName) {
            'Super Admin' => __('php/livewire/operator/operator_roles.role_super_admin'),
            'Property Owner' => __('php/livewire/operator/operator_roles.role_property_owner'),
            'Group Admin' => __('php/livewire/operator/operator_roles.role_group_admin'),
            'Hotel Manager' => __('php/livewire/operator/operator_roles.role_hotel_manager'),
            'Revenue Manager' => __('php/livewire/operator/operator_roles.role_revenue_manager'),
            'Front Desk' => __('php/livewire/operator/operator_roles.role_front_desk'),
            'Reservation Agent' => __('php/livewire/operator/operator_roles.role_reservation_agent'),
            'Housekeeping' => __('php/livewire/operator/operator_roles.role_housekeeping'),
            'Maintenance' => __('php/livewire/operator/operator_roles.role_maintenance'),
            'Accountant' => __('php/livewire/operator/operator_roles.role_accountant'),
            'Marketing Manager' => __('php/livewire/operator/operator_roles.role_marketing_manager'),
            'Guest Support Agent' => __('php/livewire/operator/operator_roles.role_guest_support_agent'),
            default => $roleName,
        };
    }

    /** The role card's one-line description, keyed by the role name ({@see RolePermissionService} writes the English). */
    private function roleDescription(string $roleName): string
    {
        return match ($roleName) {
            'Group Admin' => __('php/livewire/operator/operator_roles.desc_group_admin'),
            'Hotel Manager' => __('php/livewire/operator/operator_roles.desc_hotel_manager'),
            'Revenue Manager' => __('php/livewire/operator/operator_roles.desc_revenue_manager'),
            'Front Desk' => __('php/livewire/operator/operator_roles.desc_front_desk'),
            'Reservation Agent' => __('php/livewire/operator/operator_roles.desc_reservation_agent'),
            'Housekeeping' => __('php/livewire/operator/operator_roles.desc_housekeeping'),
            'Maintenance' => __('php/livewire/operator/operator_roles.desc_maintenance'),
            'Accountant' => __('php/livewire/operator/operator_roles.desc_accountant'),
            'Marketing Manager' => __('php/livewire/operator/operator_roles.desc_marketing_manager'),
            'Guest Support Agent' => __('php/livewire/operator/operator_roles.desc_guest_support_agent'),
            // the same catch-all the service's describe() falls to for any other role name
            default => __('php/livewire/operator/operator_roles.desc_default'),
        };
    }

    /** The derived authority tier as people read it, keyed by the tier code the service returns. */
    private function levelLabel(string $level): string
    {
        return match ($level) {
            'Admin' => __('php/livewire/operator/operator_roles.level_admin'),
            'Manager' => __('php/livewire/operator/operator_roles.level_manager'),
            'Supervisor' => __('php/livewire/operator/operator_roles.level_supervisor'),
            'Team' => __('php/livewire/operator/operator_roles.level_team'),
            default => $level,
        };
    }

    /** The coarse domain heading, keyed by the domain code in {@see RolePermissionService::DOMAIN_ORDER}. */
    private function domainLabel(string $domain): string
    {
        return match ($domain) {
            'Operations' => __('php/livewire/operator/operator_roles.domain_operations'),
            'Guests' => __('php/livewire/operator/operator_roles.domain_guests'),
            'Finance' => __('php/livewire/operator/operator_roles.domain_finance'),
            'System & access' => __('php/livewire/operator/operator_roles.domain_system_access'),
            'Other' => __('php/livewire/operator/operator_roles.domain_other'),
            default => $domain,
        };
    }

    /** A resource section heading, keyed by the permission resource (the service's English is the fallback). */
    private function sectionLabel(string $resource, string $fallback): string
    {
        return match ($resource) {
            'addons' => __('php/livewire/operator/operator_roles.section_addons'),
            'amenities' => __('php/livewire/operator/operator_roles.section_amenities'),
            'api' => __('php/livewire/operator/operator_roles.section_api'),
            'availability' => __('php/livewire/operator/operator_roles.section_availability'),
            'billing' => __('php/livewire/operator/operator_roles.section_billing'),
            'corporate_accounts' => __('php/livewire/operator/operator_roles.section_corporate_accounts'),
            'email_templates' => __('php/livewire/operator/operator_roles.section_email_templates'),
            'fees' => __('php/livewire/operator/operator_roles.section_fees'),
            'folios' => __('php/livewire/operator/operator_roles.section_folios'),
            'guest_documents' => __('php/livewire/operator/operator_roles.section_guest_documents'),
            'guest_portal' => __('php/livewire/operator/operator_roles.section_guest_portal'),
            'guests' => __('php/livewire/operator/operator_roles.section_guests'),
            'housekeeping' => __('php/livewire/operator/operator_roles.section_housekeeping'),
            'invoices' => __('php/livewire/operator/operator_roles.section_invoices'),
            'maintenance' => __('php/livewire/operator/operator_roles.section_maintenance'),
            'organizations' => __('php/livewire/operator/operator_roles.section_organizations'),
            'owner_portal' => __('php/livewire/operator/operator_roles.section_owner_portal'),
            'payments' => __('php/livewire/operator/operator_roles.section_payments'),
            'payouts' => __('php/livewire/operator/operator_roles.section_payouts'),
            'properties' => __('php/livewire/operator/operator_roles.section_properties'),
            'rates' => __('php/livewire/operator/operator_roles.section_rates'),
            'reports' => __('php/livewire/operator/operator_roles.section_reports'),
            'reservations' => __('php/livewire/operator/operator_roles.section_reservations'),
            'restrictions' => __('php/livewire/operator/operator_roles.section_restrictions'),
            'reviews' => __('php/livewire/operator/operator_roles.section_reviews'),
            'roles' => __('php/livewire/operator/operator_roles.section_roles'),
            'schedule' => __('php/livewire/operator/operator_roles.section_schedule'),
            'settings' => __('php/livewire/operator/operator_roles.section_settings'),
            'taxes' => __('php/livewire/operator/operator_roles.section_taxes'),
            'units' => __('php/livewire/operator/operator_roles.section_units'),
            'users' => __('php/livewire/operator/operator_roles.section_users'),
            default => $fallback,
        };
    }

    /** One permission row's label, keyed by the permission code (a permission added later keeps its English). */
    private function permissionLabel(string $code, string $fallback): string
    {
        return match ($code) {
            'organizations.manage' => __('php/livewire/operator/operator_roles.perm_organizations_manage'),
            'billing.manage' => __('php/livewire/operator/operator_roles.perm_billing_manage'),
            'users.manage' => __('php/livewire/operator/operator_roles.perm_users_manage'),
            'roles.manage' => __('php/livewire/operator/operator_roles.perm_roles_manage'),
            'settings.manage' => __('php/livewire/operator/operator_roles.perm_settings_manage'),
            'api.manage' => __('php/livewire/operator/operator_roles.perm_api_manage'),
            'properties.view' => __('php/livewire/operator/operator_roles.perm_properties_view'),
            'properties.manage' => __('php/livewire/operator/operator_roles.perm_properties_manage'),
            'email_templates.manage' => __('php/livewire/operator/operator_roles.perm_email_templates_manage'),
            'units.view' => __('php/livewire/operator/operator_roles.perm_units_view'),
            'units.manage' => __('php/livewire/operator/operator_roles.perm_units_manage'),
            'amenities.manage' => __('php/livewire/operator/operator_roles.perm_amenities_manage'),
            'rates.view' => __('php/livewire/operator/operator_roles.perm_rates_view'),
            'rates.manage' => __('php/livewire/operator/operator_roles.perm_rates_manage'),
            'availability.view' => __('php/livewire/operator/operator_roles.perm_availability_view'),
            'availability.manage' => __('php/livewire/operator/operator_roles.perm_availability_manage'),
            'restrictions.manage' => __('php/livewire/operator/operator_roles.perm_restrictions_manage'),
            'reservations.view' => __('php/livewire/operator/operator_roles.perm_reservations_view'),
            'reservations.create' => __('php/livewire/operator/operator_roles.perm_reservations_create'),
            'reservations.manage' => __('php/livewire/operator/operator_roles.perm_reservations_manage'),
            'reservations.assign' => __('php/livewire/operator/operator_roles.perm_reservations_assign'),
            'reservations.cancel' => __('php/livewire/operator/operator_roles.perm_reservations_cancel'),
            'reservations.checkin' => __('php/livewire/operator/operator_roles.perm_reservations_checkin'),
            'reservations.checkout' => __('php/livewire/operator/operator_roles.perm_reservations_checkout'),
            'reservations.checkout_override' => __('php/livewire/operator/operator_roles.perm_reservations_checkout_override'),
            'reservations.restriction_override' => __('php/livewire/operator/operator_roles.perm_reservations_restriction_override'),
            'guests.view' => __('php/livewire/operator/operator_roles.perm_guests_view'),
            'guests.manage' => __('php/livewire/operator/operator_roles.perm_guests_manage'),
            'guests.erase' => __('php/livewire/operator/operator_roles.perm_guests_erase'),
            'guest_documents.manage' => __('php/livewire/operator/operator_roles.perm_guest_documents_manage'),
            'corporate_accounts.manage' => __('php/livewire/operator/operator_roles.perm_corporate_accounts_manage'),
            'folios.view' => __('php/livewire/operator/operator_roles.perm_folios_view'),
            'folios.manage' => __('php/livewire/operator/operator_roles.perm_folios_manage'),
            'payments.view' => __('php/livewire/operator/operator_roles.perm_payments_view'),
            'payments.record' => __('php/livewire/operator/operator_roles.perm_payments_record'),
            'payments.refund' => __('php/livewire/operator/operator_roles.perm_payments_refund'),
            'invoices.view' => __('php/livewire/operator/operator_roles.perm_invoices_view'),
            'invoices.issue' => __('php/livewire/operator/operator_roles.perm_invoices_issue'),
            'taxes.manage' => __('php/livewire/operator/operator_roles.perm_taxes_manage'),
            'fees.manage' => __('php/livewire/operator/operator_roles.perm_fees_manage'),
            'addons.manage' => __('php/livewire/operator/operator_roles.perm_addons_manage'),
            'payouts.manage' => __('php/livewire/operator/operator_roles.perm_payouts_manage'),
            'housekeeping.view' => __('php/livewire/operator/operator_roles.perm_housekeeping_view'),
            'housekeeping.manage' => __('php/livewire/operator/operator_roles.perm_housekeeping_manage'),
            'maintenance.manage' => __('php/livewire/operator/operator_roles.perm_maintenance_manage'),
            'reviews.view' => __('php/livewire/operator/operator_roles.perm_reviews_view'),
            'reviews.manage' => __('php/livewire/operator/operator_roles.perm_reviews_manage'),
            'schedule.view' => __('php/livewire/operator/operator_roles.perm_schedule_view'),
            'schedule.manage' => __('php/livewire/operator/operator_roles.perm_schedule_manage'),
            'reports.view' => __('php/livewire/operator/operator_roles.perm_reports_view'),
            'owner_portal.view' => __('php/livewire/operator/operator_roles.perm_owner_portal_view'),
            'guest_portal.view' => __('php/livewire/operator/operator_roles.perm_guest_portal_view'),
            default => $fallback,
        };
    }
}
