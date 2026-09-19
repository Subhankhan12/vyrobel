<?php

namespace App\Models;

use App\Jobs\Billing\SyncSubscriptionQuantityJob;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasAmenities;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasMedia;
use App\Models\Concerns\HasNotes;
use App\Models\Concerns\HasTags;
use App\Services\PropertyRemovalService;
use App\Support\Billing\BillableUnits;
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
 * @property Carbon|null $removal_effective_at ⚠️ WHEN THE ARCHIVE MAY RUN — "after the last stay", KEPT TRUE.
 *                                             ⚠️⚠️ IT IS NOT A HISTORICAL RECORD OF THE SCHEDULING MOMENT. {@see PropertyRemovalService::processDue()}
 *                                             RE-DERIVES it each run and WRITES IT FORWARD when a stay reaches beyond it (F-57 · FIX 44), so a
 *                                             booking taken after scheduling defers the archive instead of being archived over. ⚠️ THE ACCEPTED
 *                                             CONSEQUENCE: a property that keeps taking bookings never archives. ⚠️ An unlabelled date column is
 *                                             how F-57 was born — this label is the fix for that half.
 * @property string|null $removal_requested_by
 * @property bool $brand_powered_by
 * @property string|null $brand_accent
 * @property string|null $brand_logo_path
 * @property string|null $brand_email_from_name
 * @property string|null $brand_reply_to
 * @property string|null $support_phone
 * @property string|null $support_reply_norm
 * @property string|null $support_hours
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
        // Guest support contact — all three NULLABLE with no default; NULL means the channel does not exist and
        // the guest surface omits it entirely (see App\Support\Guest\PropertySupport).
        'support_phone',
        'support_reply_norm',
        'support_hours',
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

    /**
     * ⚠️⚠️ THE BILLING CHOKE POINT FOR AN ARCHIVE (2026-09-01) — the companion to {@see Unit::booted()}.
     *
     * {@see BillableUnits::count()} excludes units on a soft-deleted property, so
     * archiving one CHANGES the billable quantity. ⚠️ But archiving touches only THIS row — every unit is left
     * exactly as it was — so not one of `Unit`'s model events fires, and without this hook the count would move
     * while Stripe was never told: the F3 defect reappearing one table over.
     *
     * ⚠️ `restored` IS HOOKED TOO, though no in-product restore path exists today (removal is cancellable before
     * it runs, and irreversible after). It costs one line and makes the pair correct by construction rather than
     * by the current absence of a caller — which is precisely the assumption that produced the original gap.
     *
     * ⚠️ IT MUST WORK FROM THE NIGHTLY COMMAND. `PropertyRemovalService::processDue()` archives from
     * `properties:process-removals` with no request at all, inside `TenantContext::forOrganization()`. The job
     * carries a scalar org id and establishes its own context, so it does not depend on an ambient one — and a
     * nightly run archiving several properties for one org collapses to a single sync on a real queue, because
     * the job is unique-until-processing per organization.
     */
    protected static function booted(): void
    {
        $syncBilling = function (self $property): void {
            $organizationId = $property->organization_id;

            if ($organizationId !== '') {
                SyncSubscriptionQuantityJob::dispatch($organizationId);
            }
        };

        static::deleted($syncBilling);
        static::restored($syncBilling);
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
