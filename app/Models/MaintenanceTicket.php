<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasMedia;
use Database\Factories\MaintenanceTicketFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A maintenance ticket on a unit (tenant-scoped). Lifecycle open → in_progress → resolved / cancelled,
 * driven by MaintenanceService (guarded transitions). May take the unit out of service (Step 2) and,
 * when resolved with a cost on an OWNED unit, is the polymorphic `source` of an owner_expense (Step 3).
 * #89: `HasMedia` (proof photos) + a real labor/parts split (labor+parts === cost_minor, #17) + a real
 * awaiting-parts state (the board's Parts-on-order panel).
 *
 * @property Carbon $opened_at
 * @property Carbon|null $resolved_at
 * @property int|null $cost_minor
 * @property int|null $labor_minor
 * @property int|null $parts_minor
 * @property bool $takes_out_of_service
 * @property bool $awaiting_parts
 * @property string|null $part_name
 * @property Carbon|null $part_eta
 */
class MaintenanceTicket extends Model
{
    /** @use HasFactory<MaintenanceTicketFactory> */
    use BelongsToOrganization, HasFactory, HasMedia, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'unit_id',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'takes_out_of_service',
        'cost_minor',
        'labor_minor',
        'parts_minor',
        'awaiting_parts',
        'part_name',
        'part_eta',
        'currency',
        'reported_by',
        'assigned_to',
        'opened_at',
        'resolved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'takes_out_of_service' => 'boolean',
            'cost_minor' => 'integer',
            'labor_minor' => 'integer',
            'parts_minor' => 'integer',
            'awaiting_parts' => 'boolean',
            'part_eta' => 'date',
            'opened_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /** An active ticket is one that still governs the unit (open or in progress). */
    public function isActive(): bool
    {
        return in_array($this->status, ['open', 'in_progress'], true);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
