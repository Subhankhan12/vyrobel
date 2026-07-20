<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An owner's REQUEST to add a unit (Phase 2 · Sub-gate 6). Owner-scoped via {@see BelongsToOwner} — an owner
 * sees/creates only their OWN requests; the operator (owner scope inactive) sees them tenant-scoped. This is an
 * INTAKE record: it never creates a unit. The operator reviews it and creates the real unit as their own action.
 */
class UnitRequest extends Model
{
    use BelongsToOrganization, BelongsToOwner, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'owner_id',
        'requested_name',
        'requested_kind',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }
}
