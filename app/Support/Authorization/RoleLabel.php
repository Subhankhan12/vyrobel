<?php

declare(strict_types=1);

namespace App\Support\Authorization;

/**
 * A role's displayed name, keyed by its spatie name — the ONE map (I18N-01). The name is an identifier in data and logic and
 * is never translated there; every screen that SHOWS one (the Roles page, the console's organisation and support pages) says
 * it through here, in the reader's language. A role with no entry (an organisation's own) shows its name as stored.
 */
final class RoleLabel
{
    public static function for(string $roleName): string
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
}
