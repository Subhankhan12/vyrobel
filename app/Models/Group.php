<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Group / block booking (rooming list).
 */
class Group extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'property_id',
        'name',
        'occasion',
        'block_code',
        'billing_mode',
        'pickup_deadline',
        'contact',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pickup_deadline' => 'date',
            'contact' => 'array',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
