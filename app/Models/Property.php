<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasAmenities;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasNotes;
use App\Models\Concerns\HasTags;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A physical site (can be mixed-type). Reuses the shared polymorphic media/documents/notes/
 * tags/amenities via concern traits — never per-module tables (convention #6).
 *
 * @property string $currency
 * @property string $timezone
 */
class Property extends Model
{
    use BelongsToOrganization, HasAmenities, HasDocuments, HasFactory, HasMedia, HasNotes, HasTags, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'brand',
        'type',
        'address',
        'lat',
        'lng',
        'timezone',
        'star_rating',
        'currency',
        'check_in_time',
        'check_out_time',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'address' => 'array',
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'star_rating' => 'integer',
        ];
    }

    /**
     * @return HasMany<PropertyPolicy, $this>
     */
    public function policies(): HasMany
    {
        return $this->hasMany(PropertyPolicy::class);
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Staff scoped to this property (property-level roles), via property_user.
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'property_user')
            ->withPivot(['organization_id'])
            ->withTimestamps();
    }
}
