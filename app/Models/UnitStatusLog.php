<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only history of a unit's housekeeping/condition status changes. Write-once:
 * only `created_at`, no updates, no soft delete.
 */
class UnitStatusLog extends Model
{
    use BelongsToOrganization, HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'unit_id',
        'from_status',
        'to_status',
        'type',
        'changed_by',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
