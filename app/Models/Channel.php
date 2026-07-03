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
 * A connection from a property to a channel-manager provider (Channex at launch) — the OTA
 * credential vault. `credentials` is ENCRYPTED at rest (convention #5): the provider API key /
 * per-property token is never stored in plaintext, and `settings` must never carry secrets.
 */
class Channel extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'property_id',
        'provider',
        'name',
        'external_property_id',
        'credentials',
        'settings',
        'commission_pct',
        'status',
        'last_synced_at',
        'last_error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted',
            'settings' => 'array',
            'commission_pct' => 'decimal:2',
            'last_synced_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return HasMany<ChannelMapping, $this>
     */
    public function mappings(): HasMany
    {
        return $this->hasMany(ChannelMapping::class);
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
