<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasNotes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A cleaning job. Photo proof + notes reuse the shared polymorphic media/notes (convention #6).
 */
class HousekeepingTask extends Model
{
    use BelongsToOrganization, HasFactory, HasMedia, HasNotes, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'property_id',
        'unit_id',
        'date',
        'type',
        'status',
        'priority',
        'assigned_to',
        'checklist',
        'inspected_by',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'checklist' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function inspectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }
}
