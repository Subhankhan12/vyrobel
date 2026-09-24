<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Models\PrivacyRequest;
use App\Models\StatusIncident;
use Illuminate\Support\Str;

/**
 * ⚠️ ADMIN 03 · F6 — THE ONE place an action KEY becomes words on a console page. Before it, four surfaces (organisation
 * detail's console actions + recent activity, security & activity, the audit log) printed `platform.customer.suspend`
 * and `maintenance_ticket.opened` as they sit in the database — accurate, and unreadable to anyone who did not write
 * the code. The key is never lost: every surface that shows a label keeps the key in reach (a title, or beneath it on
 * the audit log, which is the forensic view).
 *
 * `platform()` covers the console's OWN ledger — a closed set, so it is a map; an unmapped key still reads as words.
 * `event()` covers a tenant's `audit_events` — hundreds of keys across every module, so it is a rule, not a map.
 */
final class ActionLabel
{
    public const PLATFORM = [
        // Signing in and the admin's own account
        'platform.login' => 'Signed in',
        'platform.2fa_enrolled' => 'Set up two-factor authentication',
        'platform.admin_created' => 'Admin account created from the command line',
        'platform.account.profile_updated' => 'Updated their own profile',
        'platform.account.password_changed' => 'Changed their own password',
        'platform.account.email_change_requested' => 'Asked to change their own email',
        'platform.account.email_changed' => 'Changed their own email',
        'platform.account.recovery_codes_regenerated' => 'Made new recovery codes',
        // Admins
        'platform.admin.invited' => 'Invited an admin',
        'platform.admin.invite_reissued' => 'Sent a new invitation link',
        'platform.admin.invite_accepted' => 'Accepted an admin invitation',
        'platform.admin.role_changed' => 'Changed an admin’s role',
        'platform.admin.revoked' => 'Revoked an admin',
        'platform.admin.restored' => 'Restored an admin',
        'platform.admin.signed_out' => 'Signed an admin out',
        // Organisations
        'platform.customer.suspend' => 'Suspended the organisation',
        'platform.customer.reactivate' => 'Reactivated the organisation',
        'platform.customer.archive' => 'Archived the organisation',
        'platform.customer.restore' => 'Restored the organisation',
        'platform.customer.assign_plan' => 'Changed the plan',
        'platform.customer.mark_internal' => 'Marked as internal',
        'platform.customer.mark_customer' => 'Marked as a customer',
        'platform.customer.refused' => 'Refused — the organisation is protected',
        'platform.customer.impact' => 'Checked guests in house',
        // Support and break-glass
        'platform.support.lookup' => 'Looked up a person',
        'platform.support.password_reset' => 'Sent a password-reset link',
        'platform.support.resend_verification' => 'Sent a new verification email',
        'platform.support.verify_email' => 'Marked an email as verified',
        'platform.support.unlink_identity' => 'Unlinked a sign-in provider',
        'platform.break_glass.start' => 'Opened a break-glass session',
        'platform.break_glass.end' => 'Ended a break-glass session',
        'platform.break_glass.page' => 'Break-glass: opened a page',
        'platform.break_glass.step_up_failed' => 'Break-glass: 2FA code refused',
        'platform.break_glass.write_refused' => 'Break-glass: a change was refused',
        // Platform settings
        'platform.switch.on' => 'Turned a switch on',
        'platform.switch.off' => 'Turned a switch off',
        'platform.plan.created' => 'Created a plan',
        'platform.plan.updated' => 'Edited a plan',
        'platform.plan.toggled' => 'Turned a plan on or off',
        'platform.integration.updated' => 'Changed an integration',
        'platform.integration.tested' => 'Tested an integration',
        'platform.announcement.publish' => 'Published an announcement',
        'platform.announcement.withdraw' => 'Withdrew an announcement',
        'platform.privacy.request_recorded' => 'Recorded a privacy request',
        'platform.status.incident_open' => 'Posted a status incident',
        // Page views (reads) — the audit log hides these by default
        'platform.overview' => 'Viewed the overview',
        'platform.dashboard' => 'Viewed health',
        'platform.health.view' => 'Viewed health',
        'platform.customers.list' => 'Viewed the organisation list',
        'platform.customers.view' => 'Viewed an organisation',
        'platform.customers.triage' => 'Viewed triage',
        'platform.revenue' => 'Viewed revenue',
        'platform.funnel' => 'Viewed the signup funnel',
        'platform.hotel_stats' => 'Viewed statistics',
        'platform.audit.view' => 'Viewed the audit log',
        'platform.admins.view' => 'Viewed admins',
        'platform.switches.view' => 'Viewed kill switches',
        'platform.announcements.view' => 'Viewed announcements',
        'platform.privacy.view' => 'Viewed the privacy desk',
        'platform.activity.audit_slice' => 'Viewed security & activity',
        'platform.break_glass.view' => 'Viewed break-glass',
    ];

    /** Words for one of the console's own ledger actions (`platform_admin_actions.action`). */
    public static function platform(string $action): string
    {
        // `mutate()` writes `<action>.failed` when the action threw — the words say so.
        if (str_ends_with($action, '.failed')) {
            return self::platform(substr($action, 0, -strlen('.failed'))).' — failed';
        }

        if (isset(self::PLATFORM[$action])) {
            return self::PLATFORM[$action];
        }

        // Two families carry the new status in the key itself.
        if (str_starts_with($action, 'platform.privacy.request_')) {
            $status = substr($action, strlen('platform.privacy.request_'));

            return 'Marked a privacy request as '.Str::lower(PrivacyRequest::STATUSES[$status] ?? self::words($status));
        }
        if (str_starts_with($action, 'platform.status.incident_')) {
            $status = substr($action, strlen('platform.status.incident_'));

            return 'Updated a status incident: '.Str::lower(StatusIncident::STATUSES[$status] ?? self::words($status));
        }

        return self::event(Str::after($action, 'platform.'));
    }

    /** Words for a tenant's business event (`audit_events.action`, e.g. `maintenance_ticket.opened`). */
    public static function event(string $action): string
    {
        return Str::ucfirst(self::words(str_replace('.', ' ', $action)));
    }

    private static function words(string $key): string
    {
        $words = array_map(fn (string $w) => match ($w) {
            'ip' => 'IP',
            '2fa' => '2FA',
            'ota' => 'OTA',
            'vat' => 'VAT',
            'iban' => 'IBAN',
            'organization' => 'organisation',
            'canceled' => 'cancelled',
            'finalized' => 'finalised',
            'authorized' => 'authorised',
            default => $w,
        }, preg_split('/[\s_]+/', Str::lower(trim($key))) ?: []);

        return implode(' ', array_filter($words, fn (string $w) => $w !== ''));
    }
}
