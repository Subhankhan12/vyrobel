<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MaintenanceSetting;
use App\Models\MaintenanceTicket;
use App\Models\Membership;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Maintenance\MaintenanceSla;
use App\Support\Schedule\StaffRoster;

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
        private AuditEventRecorder $audit,
    ) {}

    /**
     * The acting org's settings row, created with defaults on first read.
     *
     * ⚠️ THE REFRESH IS LOAD-BEARING (wireframe-parity WAVE 2 · #16). `firstOrCreate()` returns the model it
     * just INSERTed, populated only with the attributes it wrote — the `sla_*_hours` DEFAULTS live in the
     * schema and are applied by MySQL, so they are absent from that in-memory instance until it is re-read.
     * Without this, the very first caller on a fresh org still saw null targets and reported "No SLA target
     * set" — the same unhydrated-defaults trap that caused the bug this fixed, one level down.
     */
    public function forOrg(): MaintenanceSetting
    {
        $setting = MaintenanceSetting::query()->firstOrCreate([]);

        return $setting->wasRecentlyCreated ? $setting->refresh() : $setting;
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
        // ⚠️ STAFF ONLY, via the ONE shared definition. This used to select by active Membership alone,
        // which offered External Owners in the category-assignee pickers — and this surface drives AUTO
        // ASSIGNMENT, so a new work order could have been routed to an owner (#28). See
        // {@see StaffRoster::staffUserIds()}.
        return app(StaffRoster::class)->assignableStaff();
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

        // Restricted to real staff for the same reason as technicians(): a stale assignee row pointing at an
        // owner (configured before the fix) must not resurface here as a "technician".
        $staffIds = app(StaffRoster::class)->staffUserIds();
        $ids = collect(array_keys($assignedCategories))->merge($load->keys())->unique()
            ->filter(fn ($id): bool => in_array((string) $id, $staffIds, true))->values();
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

        // ⚠️ Staff-only. This is the AUTO-ASSIGNMENT config: a membership-only check let an owner be saved
        // as a category assignee, and every new work order in that category would then be routed to them
        // (#28). Silently returns null for a non-staff id rather than throwing — the caller treats that as
        // "no assignee", which is the safe outcome for a settings save.
        return in_array((string) $userId, app(StaffRoster::class)->staffUserIds(), true) ? $userId : null;
    }
}
