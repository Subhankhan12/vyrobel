<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MaintenanceSetting;
use App\Models\MaintenanceTicket;
use App\Models\Membership;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Maintenance\MaintenanceSla;
use App\Support\Tenancy\TenantContext;

/**
 * The write path for the operator MAINTENANCE SETTINGS surface (Phase 1 · Sub-gate 4) — mirrors
 * {@see SettingsService}: whitelisted TYPED columns on {@see MaintenanceSetting} (never the dormant KV `Setting`
 * scaffold), one audit event naming WHICH fields changed (not their values). TENANT-safe by construction
 * (MaintenanceSetting is BelongsToOrganization; a supplied technician id is verified to be a member of the
 * acting org, so a cross-org user can never be routed maintenance).
 *
 * ⚠️ WHAT IS STORED vs LIVE (honesty): the SLA targets are fully real — {@see MaintenanceSla}
 * computes a live breach from them and the board shows it (#89). The category→technician map is real and, when the
 * `auto_assign_enabled` master toggle is on, {@see MaintenanceService::open()} routes a new order to
 * the category's technician (#89). The notification toggles STORE the preference; maintenance does not dispatch an
 * alert yet (no maintenance notifier is wired) — the UI says so, and this service never claims to send anything.
 * `require_photo_on_resolve` is stored; resolve does not yet enforce it — the UI says so; the setting is real and
 * ready for when the enforcement ships.
 */
class MaintenanceSettingsService
{
    /** @var list<string> the maintenance category enum (already on maintenance_tickets) */
    public const CATEGORIES = ['plumbing', 'electrical', 'appliance', 'structural', 'other'];

    /** @var list<string> */
    private const SLA_FIELDS = ['sla_urgent_hours', 'sla_high_hours', 'sla_normal_hours', 'sla_low_hours'];

    /** @var list<string> */
    private const TOGGLE_FIELDS = [
        'auto_assign_enabled', 'require_photo_on_resolve', 'block_room_from_sale', 'notify_front_desk',
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

    /**
     * The Technicians panel (#89): every org member who is a configured category assignee OR currently holds an
     * active (open/in_progress) work order, with their REAL open-order load. A member with no assignment and no
     * open order isn't listed (they aren't a technician). Load is a real count — never a fabricated figure.
     *
     * @return list<array{id: string, name: string, load: int, categories: list<string>}>
     */
    public function technicianPanel(): array
    {
        $settings = $this->forOrg();

        // The members configured as a category assignee, mapped to the categories they cover.
        $assignedCategories = [];
        foreach (self::CATEGORIES as $category) {
            $id = $settings->{'assignee_'.$category.'_id'};
            if (is_string($id) && $id !== '') {
                $assignedCategories[$id][] = $category;
            }
        }

        // Real open-order load per assignee.
        $load = MaintenanceTicket::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->whereNotNull('assigned_to')
            ->selectRaw('assigned_to, COUNT(*) AS cnt')
            ->groupBy('assigned_to')
            ->pluck('cnt', 'assigned_to');

        $ids = collect(array_keys($assignedCategories))->merge($load->keys())->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return User::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get()
            ->map(fn (User $u): array => [
                'id' => (string) $u->getKey(),
                'name' => (string) $u->name,
                'load' => (int) ($load[$u->getKey()] ?? 0),
                'categories' => $assignedCategories[(string) $u->getKey()] ?? [],
            ])
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
