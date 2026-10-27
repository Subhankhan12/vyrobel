<?php

declare(strict_types=1);

namespace App\Support\Schedule;

/**
 * Buckets a staff member's operator role(s) into a roster DEPARTMENT (Phase 7d · Team Schedule) — the grid's
 * department filter (all / frontdesk / housekeeping / manager). ONE definition of the role→department map,
 * derived from the REAL spatie roles ({@see config('authorization.roles')}), never a fabricated attribute.
 *
 * Portal roles (`External Owner` / `Guest`) are NOT staff and never appear on the roster.
 */
final class StaffDepartment
{
    public const MANAGER = 'manager';

    public const FRONTDESK = 'frontdesk';

    public const HOUSEKEEPING = 'housekeeping';

    /** The filter set (with the "all" roll-up), in display order. */
    public const FILTERS = ['all', self::FRONTDESK, self::HOUSEKEEPING, self::MANAGER];

    /** role name → department. A role absent here (Accountant / Marketing Manager) falls to "manager" (office). */
    private const ROLE_MAP = [
        'Property Owner' => self::MANAGER,
        'Group Admin' => self::MANAGER,
        'Hotel Manager' => self::MANAGER,
        'Revenue Manager' => self::MANAGER,
        'Front Desk' => self::FRONTDESK,
        'Reservation Agent' => self::FRONTDESK,
        'Guest Support Agent' => self::FRONTDESK,
        'Housekeeping' => self::HOUSEKEEPING,
        'Maintenance' => self::HOUSEKEEPING,
    ];

    /**
     * The department for a staff member from their role names. A user may hold several roles; the FIRST
     * mapped role in the config's privilege order wins (managers before ops), else "manager" (office).
     *
     * @param  list<string>  $roleNames
     */
    public static function forRoles(array $roleNames): string
    {
        foreach ([self::MANAGER, self::FRONTDESK, self::HOUSEKEEPING] as $dept) {
            foreach ($roleNames as $role) {
                if ((self::ROLE_MAP[$role] ?? null) === $dept) {
                    return $dept;
                }
            }
        }

        return self::MANAGER;
    }

    public static function isFilter(string $dept): bool
    {
        return in_array($dept, self::FILTERS, true);
    }

    public static function label(string $dept): string
    {
        return match ($dept) {
            'all' => __('php/support/schedule/staff_department.all_teams'),
            self::FRONTDESK => __('php/support/schedule/staff_department.front_desk'),
            self::HOUSEKEEPING => __('php/support/schedule/staff_department.housekeeping'),
            self::MANAGER => __('php/support/schedule/staff_department.manager'),
            default => ucfirst($dept),   // an unknown code, shown as itself
        };
    }
}
