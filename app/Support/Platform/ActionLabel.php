<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Models\PrivacyRequest;
use App\Models\StatusIncident;
use Illuminate\Support\Facades\Lang;
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
    /**
     * The words for one of the console's own closed set of actions — null when the key is not in the set.
     * ⚠️ I18N-01: a `match` by CODE (the codes are what is stored; only the words are translated, at render time).
     */
    private static function label(string $action): ?string
    {
        return match ($action) {
            // Signing in and the admin's own account
            'platform.login' => __('php/support/platform/action_label.login'),
            'platform.2fa_enrolled' => __('php/support/platform/action_label.2fa_enrolled'),
            'platform.admin_created' => __('php/support/platform/action_label.admin_created'),
            'platform.account.profile_updated' => __('php/support/platform/action_label.account_profile_updated'),
            'platform.account.password_changed' => __('php/support/platform/action_label.account_password_changed'),
            'platform.account.email_change_requested' => __('php/support/platform/action_label.account_email_change_requested'),
            'platform.account.email_changed' => __('php/support/platform/action_label.account_email_changed'),
            'platform.account.recovery_codes_regenerated' => __('php/support/platform/action_label.account_recovery_codes_regenerated'),
            // Admins
            'platform.admin.invited' => __('php/support/platform/action_label.admin_invited'),
            'platform.admin.invite_reissued' => __('php/support/platform/action_label.admin_invite_reissued'),
            'platform.admin.invite_accepted' => __('php/support/platform/action_label.admin_invite_accepted'),
            'platform.admin.role_changed' => __('php/support/platform/action_label.admin_role_changed'),
            'platform.admin.revoked' => __('php/support/platform/action_label.admin_revoked'),
            'platform.admin.restored' => __('php/support/platform/action_label.admin_restored'),
            'platform.admin.signed_out' => __('php/support/platform/action_label.admin_signed_out'),
            // Organisations
            'platform.customer.suspend' => __('php/support/platform/action_label.customer_suspend'),
            'platform.customer.reactivate' => __('php/support/platform/action_label.customer_reactivate'),
            'platform.customer.archive' => __('php/support/platform/action_label.customer_archive'),
            'platform.customer.restore' => __('php/support/platform/action_label.customer_restore'),
            'platform.customer.assign_plan' => __('php/support/platform/action_label.customer_assign_plan'),
            'platform.customer.mark_internal' => __('php/support/platform/action_label.customer_mark_internal'),
            'platform.customer.mark_customer' => __('php/support/platform/action_label.customer_mark_customer'),
            'platform.customer.refused' => __('php/support/platform/action_label.customer_refused'),
            'platform.customer.impact' => __('php/support/platform/action_label.customer_impact'),
            'platform.customer.current_plan' => __('php/support/platform/action_label.customer_current_plan'),
            'platform.customer.update_details' => __('php/support/platform/action_label.customer_update_details'),
            'platform.customer.create' => __('php/support/platform/action_label.customer_create'),
            'platform.customer.created' => __('php/support/platform/action_label.customer_created'),
            'platform.customer.founder_link' => __('php/support/platform/action_label.customer_founder_link'),
            'platform.customer.delete' => __('php/support/platform/action_label.customer_delete'),
            'platform.customer.deleted' => __('php/support/platform/action_label.customer_deleted'),
            'platform.customer.delete_check' => __('php/support/platform/action_label.customer_delete_check'),
            // Support and break-glass
            'platform.support.lookup' => __('php/support/platform/action_label.support_lookup'),
            'platform.support.password_reset' => __('php/support/platform/action_label.support_password_reset'),
            'platform.support.resend_verification' => __('php/support/platform/action_label.support_resend_verification'),
            'platform.support.verify_email' => __('php/support/platform/action_label.support_verify_email'),
            'platform.support.unlink_identity' => __('php/support/platform/action_label.support_unlink_identity'),
            'platform.break_glass.start' => __('php/support/platform/action_label.break_glass_start'),
            'platform.break_glass.end' => __('php/support/platform/action_label.break_glass_end'),
            'platform.break_glass.page' => __('php/support/platform/action_label.break_glass_page'),
            'platform.break_glass.step_up_failed' => __('php/support/platform/action_label.break_glass_step_up_failed'),
            'platform.break_glass.write_refused' => __('php/support/platform/action_label.break_glass_write_refused'),
            // Platform settings
            'platform.switch.on' => __('php/support/platform/action_label.switch_on'),
            'platform.switch.off' => __('php/support/platform/action_label.switch_off'),
            'platform.plan.created' => __('php/support/platform/action_label.plan_created'),
            'platform.plan.updated' => __('php/support/platform/action_label.plan_updated'),
            'platform.plan.toggled' => __('php/support/platform/action_label.plan_toggled'),
            'platform.integration.updated' => __('php/support/platform/action_label.integration_updated'),
            'platform.integration.tested' => __('php/support/platform/action_label.integration_tested'),
            'platform.announcement.publish' => __('php/support/platform/action_label.announcement_publish'),
            'platform.announcement.withdraw' => __('php/support/platform/action_label.announcement_withdraw'),
            'platform.privacy.request_recorded' => __('php/support/platform/action_label.privacy_request_recorded'),
            'platform.status.incident_open' => __('php/support/platform/action_label.status_incident_open'),
            // Page views (reads) — the audit log hides these by default
            'platform.overview' => __('php/support/platform/action_label.overview'),
            'platform.dashboard' => __('php/support/platform/action_label.dashboard'),
            'platform.health.view' => __('php/support/platform/action_label.health_view'),
            'platform.customers.list' => __('php/support/platform/action_label.customers_list'),
            'platform.customers.view' => __('php/support/platform/action_label.customers_view'),
            'platform.customers.triage' => __('php/support/platform/action_label.customers_triage'),
            'platform.revenue' => __('php/support/platform/action_label.revenue'),
            'platform.funnel' => __('php/support/platform/action_label.funnel'),
            'platform.hotel_stats' => __('php/support/platform/action_label.hotel_stats'),
            'platform.audit.view' => __('php/support/platform/action_label.audit_view'),
            'platform.admins.view' => __('php/support/platform/action_label.admins_view'),
            'platform.switches.view' => __('php/support/platform/action_label.switches_view'),
            'platform.announcements.view' => __('php/support/platform/action_label.announcements_view'),
            'platform.privacy.view' => __('php/support/platform/action_label.privacy_view'),
            'platform.activity.audit_slice' => __('php/support/platform/action_label.activity_audit_slice'),
            'platform.break_glass.view' => __('php/support/platform/action_label.break_glass_view'),
            default => null,
        };
    }

    /** Words for one of the console's own ledger actions (`platform_admin_actions.action`). */
    public static function platform(string $action): string
    {
        // `mutate()` writes `<action>.failed` when the action threw — the words say so.
        if (str_ends_with($action, '.failed')) {
            return __('php/support/platform/action_label.failed', ['action' => self::platform(substr($action, 0, -strlen('.failed')))]);
        }

        if (($label = self::label($action)) !== null) {
            return $label;
        }

        // Two families carry the new status in the key itself.
        if (str_starts_with($action, 'platform.privacy.request_')) {
            $status = substr($action, strlen('platform.privacy.request_'));

            return __('php/support/platform/action_label.privacy_request_marked', ['status' => Str::lower(PrivacyRequest::STATUSES[$status] ?? self::words($status))]);
        }
        if (str_starts_with($action, 'platform.status.incident_')) {
            $status = substr($action, strlen('platform.status.incident_'));

            return __('php/support/platform/action_label.status_incident_updated', ['status' => Str::lower(StatusIncident::STATUSES[$status] ?? self::words($status))]);
        }

        return self::event(Str::after($action, 'platform.'));
    }

    /** Words for a tenant's business event (`audit_events.action`, e.g. `maintenance_ticket.opened`). */
    public static function event(string $action): string
    {
        $english = Str::ucfirst(self::words(str_replace('.', ' ', $action)));
        // I18N-01 — where the reservation timeline already words this event IDENTICALLY in English, its key says it in the
        // reader's language; any other event keeps the headline it always printed (English unchanged either way)
        $key = 'php/livewire/reservation_detail.action_'.str_replace('.', '_', $action);

        return Lang::has($key, 'en', false) && Lang::get($key, [], 'en', false) === $english ? (string) __($key) : $english;
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
