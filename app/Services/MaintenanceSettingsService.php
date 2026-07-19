<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MaintenanceSetting;
use App\Models\Membership;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Tenancy\TenantContext;

/**
 * The write path for the operator MAINTENANCE SETTINGS surface (Phase 1 · Sub-gate 4) — mirrors
 * {@see SettingsService}: whitelisted TYPED columns on {@see MaintenanceSetting} (never the dormant KV `Setting`
 * scaffold), one audit event naming WHICH fields changed (not their values). TENANT-safe by construction
 * (MaintenanceSetting is BelongsToOrganization; a supplied technician id is verified to be a member of the
 * acting org, so a cross-org user can never be routed maintenance).
 *
 * ⚠️ WHAT IS STORED vs LIVE (honesty): the SLA targets + category→technician map are fully real. The
 * notification toggles STORE the preference; the live dispatch waits on the Phase-3 notification subsystem (this
 * service never claims to send anything). `require_photo_on_resolve` is stored, but its enforcement waits on
 * ticket photos (not yet a feature) — the UI says so; the setting is real and ready for when it ships.
 */
class MaintenanceSettingsService
{
    /** @var list<string> the maintenance category enum (already on maintenance_tickets) */
    public const CATEGORIES = ['plumbing', 'electrical', 'appliance', 'structural', 'other'];

    /** @var list<string> */
    private const SLA_FIELDS = ['sla_urgent_hours', 'sla_high_hours', 'sla_normal_hours', 'sla_low_hours'];

    /** @var list<string> */
    private const TOGGLE_FIELDS = [
        'require_photo_on_resolve', 'block_room_from_sale', 'notify_front_desk',
        'notify_new_urgent', 'notify_out_of_service', 'notify_parts_arrive', 'notify_sla_breach', 'notify_daily_digest',
    ];

    public function __construct(
        private TenantContext $tenant,
        private AuditEventRecorder $audit,
    ) {}

    /** The acting org's settings row, created with defaults on first read. */
    public function forOrg(): MaintenanceSetting
    {
        return MaintenanceSetting::query()->firstOrCreate([]);
    }

    /**
     * Persist a settings change — typed + whitelisted (a POST can never mass-assign a column the form doesn't
     * own). SLA hours are bounded; toggles are booleans; each assignee is verified to be an org member or nulled.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(array $input): MaintenanceSetting
    {
        $settings = $this->forOrg();
        $changed = [];

        foreach (self::SLA_FIELDS as $field) {
            if (array_key_exists($field, $input)) {
                $settings->{$field} = max(1, min(720, (int) $input[$field]));   // 1h … 30 days
                $changed[] = $field;
            }
        }

        foreach (self::TOGGLE_FIELDS as $field) {
            if (array_key_exists($field, $input)) {
                $settings->{$field} = (bool) $input[$field];
                $changed[] = $field;
            }
        }

        foreach (self::CATEGORIES as $category) {
            $field = 'assignee_'.$category.'_id';
            if (array_key_exists($field, $input)) {
                $settings->{$field} = $this->validAssignee($input[$field]);   // org member or null
                $changed[] = $field;
            }
        }

        $settings->save();

        // Record WHICH fields changed, never their values (the settings-write discipline).
        $this->audit->record('maintenance_settings.updated', $settings, null, ['fields' => $changed]);

        return $settings;
    }

    /**
     * The org members eligible to be routed maintenance (the technician picker).
     *
     * @return list<array{id: string, name: string}>
     */
    public function technicians(): array
    {
        $userIds = Membership::query()
            ->where('organization_id', $this->orgId())
            ->where('status', 'active')
            ->pluck('user_id');

        return User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->get()
            ->map(fn (User $u): array => ['id' => (string) $u->getKey(), 'name' => (string) $u->name])
            ->all();
    }

    /** A user id that is an active member of the acting org — else null (a cross-org id is silently dropped). */
    private function validAssignee(mixed $userId): ?string
    {
        $userId = is_string($userId) ? trim($userId) : '';
        if ($userId === '') {
            return null;
        }

        $isMember = Membership::query()
            ->where('organization_id', $this->orgId())
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();

        return $isMember ? $userId : null;
    }

    private function orgId(): string
    {
        return (string) $this->tenant->id();
    }
}
