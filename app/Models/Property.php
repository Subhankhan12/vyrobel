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
use Illuminate\Support\Carbon;

/**
 * A physical site (can be mixed-type). Reuses the shared polymorphic media/documents/notes/
 * tags/amenities via concern traits — never per-module tables (convention #6).
 *
 * @property string $currency
 * @property string $timezone
 * @property array<string, mixed>|null $address
 * @property Carbon|null $bookings_paused_at
 * @property Carbon|null $removal_scheduled_at
 * @property Carbon|null $removal_effective_at
 * @property string|null $removal_requested_by
 * @property bool $brand_powered_by
 * @property string|null $brand_accent
 * @property string|null $brand_logo_path
 * @property string|null $brand_email_from_name
 * @property string|null $brand_reply_to
 */
class Property extends Model
{
    use BelongsToOrganization, HasAmenities, HasDocuments, HasFactory, HasMedia, HasNotes, HasTags, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'brand',
        'brand_accent',
        'brand_logo_path',
        'brand_email_from_name',
        'brand_reply_to',
        'brand_powered_by',
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
        'bookings_paused_at',
        'removal_scheduled_at',
        'removal_effective_at',
        'removal_requested_by',
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
            'brand_powered_by' => 'boolean',
            'bookings_paused_at' => 'datetime',
            'removal_scheduled_at' => 'datetime',
            'removal_effective_at' => 'datetime',
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

    /**
     * @return HasMany<RoomType, $this>
     */
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
