<?php

/**
 * Vyrobel authorization catalog — the single source of truth for the RBAC seed.
 *
 * Model (spatie teams mode, team = organization):
 *   - PERMISSIONS are global (no team column): one shared catalog of capability strings.
 *   - ROLES are team-scoped (per organization), EXCEPT the platform-level `Super Admin`,
 *     which is a global role (team_id = null) that spans all tenants.
 *
 * The `RoleCatalogSeeder` installs the permissions + the global Super Admin role.
 * The `OrganizationRoleProvisioner` installs the 13 tenant roles for a given organization
 * (called at org-creation time and by the demo seeder in Step 8).
 */

return [

    'guard' => 'web',

    /*
     * The platform-level role that spans all tenants (team_id = null).
     */
    'super_admin_role' => 'Super Admin',

    /*
     * BACK-OFFICE ROLES — who may enter the OPERATOR app (/app). The security boundary between an
     * operator and an owner, enforced by App\Http\Middleware\EnsureBackOfficeAccess on the /app group.
     *
     * Every name here MUST exist in the `roles` matrix below (asserted by BackOfficeAccessTest).
     *
     * DELIBERATELY EXCLUDED — these are portal roles, not back-office roles:
     *   - `External Owner` (owner_portal.view only) — a property/unit owner. They belong in /owner, and
     *     their data is row-scoped by OwnerScope; letting them into /app would expose the whole org
     *     (OwnerScope is inactive off-portal) — the hole this list closes.
     *   - `Guest` (guest_portal.view only).
     * A user with NO role is also excluded: this is an allow-list, so it fails closed by construction.
     *
     * ⚠️ NAMING TRAP: `Property Owner` IS a back-office role — it is the ORGANISATION ADMIN (`['*']`,
     * "full control within their own organization"), i.e. the person who runs the hotel business. It is
     * NOT the property/unit owner who gets the owner portal — that is `External Owner`. Do not "fix"
     * this by removing Property Owner.
     *
     * NOT LISTED: `Super Admin`. It is a GLOBAL role (team_id = null) and this check is team-scoped, so
     * it could never match here anyway; worse, it is currently UNASSIGNABLE — `model_has_roles`
     * .organization_id is NOT NULL and part of the PRIMARY KEY, so a null-team assignment cannot be
     * stored (see docs/DECISIONS.md 2026-07-16). Adding it here would be untestable dead code in a
     * security gate. When that defect is fixed, grant platform staff access via the team-cleared idiom
     * used by HorizonServiceProvider::gate(), and cover it with a test.
     */
    'back_office_roles' => [
        'Property Owner',       // the ORG ADMIN (see the naming trap above), not a unit owner
        'Group Admin',
        'Hotel Manager',
        'Revenue Manager',
        'Front Desk',
        'Reservation Agent',
        'Housekeeping',
        'Maintenance',
        'Accountant',
        'Marketing Manager',
        'Guest Support Agent',
    ],

    /*
     * The full global permission catalog. Keep additive; never renumber.
     */
    'permissions' => [
        // Tenancy / platform administration
        'organizations.manage',
        'billing.manage',
        'users.manage',
        'roles.manage',
        'settings.manage',
        'api.manage',

        // Properties & inventory
        'properties.view',
        'properties.manage',
        'units.view',
        'units.manage',
        'amenities.manage',

        // Rates & availability
        'rates.view',
        'rates.manage',
        'availability.view',
        'availability.manage',
        'restrictions.manage',

        // Reservations
        'reservations.view',
        'reservations.create',
        'reservations.manage',
        'reservations.assign',
        'reservations.cancel',
        'reservations.checkin',
        'reservations.checkout',

        // Guests (PII)
        'guests.view',
        'guests.manage',
        'guests.erase',
        'guest_documents.manage',
        'corporate_accounts.manage',

        // Folios, payments, tax
        'folios.view',
        'folios.manage',
        'payments.view',
        'payments.record',
        'payments.refund',
        'invoices.view',
        'invoices.issue',
        'taxes.manage',
        'fees.manage',
        'addons.manage',
        // Owner payouts — money OUT (2026-07-17). ⚠️ ADDED because it was MISSING: the payout run has always
        // moved real money through OwnerPayoutService (create + pay, the ManualPayoutRail), and there was no
        // permission in this catalog to check it against — the coarse /app door was the only thing standing
        // in. Granted to the same set as `payments.refund` (Property Owner* / Group Admin / Accountant) and
        // deliberately NOT to Hotel Manager: paying an owner out is an accounting act, and a manager who
        // cannot hand a guest their money back should not be able to remit an owner's either.
        'payouts.manage',

        // Housekeeping & maintenance
        'housekeeping.view',
        'housekeeping.manage',
        'maintenance.manage',

        // Reporting & portals
        'reports.view',
        'owner_portal.view',
        'guest_portal.view',
    ],

    /*
     * Role -> permission matrix. `Super Admin` receives ALL permissions and is seeded as a
     * global role; the other 13 are provisioned per organization. `['*']` means "all".
     */
    'roles' => [

        'Super Admin' => ['*'],

        'Property Owner' => ['*'], // full control within their own organization

        'Group Admin' => [
            'users.manage',
            'properties.view', 'properties.manage',
            'units.view', 'units.manage', 'amenities.manage',
            'rates.view', 'rates.manage', 'availability.view', 'availability.manage', 'restrictions.manage',
            'reservations.view', 'reservations.create', 'reservations.manage', 'reservations.assign',
            'reservations.cancel', 'reservations.checkin', 'reservations.checkout',
            'guests.view', 'guests.manage', 'guests.erase', 'guest_documents.manage', 'corporate_accounts.manage',
            'folios.view', 'folios.manage', 'payments.view', 'payments.record', 'payments.refund',
            'payouts.manage',
            'invoices.view', 'invoices.issue', 'taxes.manage', 'fees.manage', 'addons.manage',
            'housekeeping.view', 'housekeeping.manage', 'maintenance.manage',
            'reports.view',
        ],

        'Hotel Manager' => [
            'properties.view',
            'units.view', 'units.manage', 'amenities.manage',
            'rates.view', 'rates.manage', 'availability.view', 'availability.manage', 'restrictions.manage',
            'reservations.view', 'reservations.create', 'reservations.manage', 'reservations.assign',
            'reservations.cancel', 'reservations.checkin', 'reservations.checkout',
            'guests.view', 'guests.manage', 'guest_documents.manage', 'corporate_accounts.manage',
            'folios.view', 'folios.manage', 'payments.view', 'payments.record',
            'invoices.view', 'invoices.issue', 'taxes.manage', 'fees.manage', 'addons.manage',
            'housekeeping.view', 'housekeeping.manage', 'maintenance.manage',
            'reports.view',
        ],

        'Revenue Manager' => [
            'properties.view', 'units.view',
            'rates.view', 'rates.manage', 'availability.view', 'availability.manage', 'restrictions.manage',
            'reservations.view',
            'reports.view',
        ],

        'Front Desk' => [
            'units.view',
            'availability.view',
            'reservations.view', 'reservations.create', 'reservations.manage', 'reservations.assign',
            'reservations.checkin', 'reservations.checkout',
            'guests.view', 'guests.manage', 'guest_documents.manage',
            'folios.view', 'folios.manage', 'payments.view', 'payments.record',
            'invoices.view', 'invoices.issue',
            'housekeeping.view',
        ],

        'Reservation Agent' => [
            'availability.view', 'rates.view',
            'reservations.view', 'reservations.create', 'reservations.manage', 'reservations.cancel',
            'guests.view', 'guests.manage',
            'folios.view',
        ],

        'Housekeeping' => [
            'units.view',
            'housekeeping.view', 'housekeeping.manage',
        ],

        'Maintenance' => [
            'units.view',
            'housekeeping.view',
            'maintenance.manage',
        ],

        'Accountant' => [
            'folios.view', 'folios.manage',
            'payments.view', 'payments.record', 'payments.refund',
            'payouts.manage',
            'invoices.view', 'invoices.issue',
            'taxes.manage', 'fees.manage', 'addons.manage',
            'reports.view',
        ],

        'Marketing Manager' => [
            'guests.view',
            'corporate_accounts.manage',
            'reports.view',
        ],

        'Guest Support Agent' => [
            'reservations.view',
            'guests.view',
            'folios.view',
            'housekeeping.view',
        ],

        'External Owner' => [
            'owner_portal.view',
        ],

        'Guest' => [
            'guest_portal.view',
        ],
    ],
];
