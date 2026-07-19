<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\MaintenanceSettingsService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One org's maintenance configuration (Phase 1 · Sub-gate 4) — SLA targets, the category→technician map, the
 * out-of-order policy, and notification preferences. TYPED columns (not the generic KV `Setting` scaffold);
 * one row per org (unique organization_id). Written only through {@see MaintenanceSettingsService}.
 */
class MaintenanceSetting extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'sla_urgent_hours', 'sla_high_hours', 'sla_normal_hours', 'sla_low_hours',
        'assignee_plumbing_id', 'assignee_electrical_id', 'assignee_appliance_id', 'assignee_structural_id', 'assignee_other_id',
        'require_photo_on_resolve', 'block_room_from_sale', 'notify_front_desk',
        'notify_new_urgent', 'notify_out_of_service', 'notify_parts_arrive', 'notify_sla_breach', 'notify_daily_digest',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sla_urgent_hours' => 'integer',
            'sla_high_hours' => 'integer',
            'sla_normal_hours' => 'integer',
            'sla_low_hours' => 'integer',
            'require_photo_on_resolve' => 'boolean',
            'block_room_from_sale' => 'boolean',
            'notify_front_desk' => 'boolean',
            'notify_new_urgent' => 'boolean',
            'notify_out_of_service' => 'boolean',
            'notify_parts_arrive' => 'boolean',
            'notify_sla_breach' => 'boolean',
            'notify_daily_digest' => 'boolean',
        ];
    }
}
