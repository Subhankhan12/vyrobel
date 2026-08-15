<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use App\Models\Concerns\HasAmenities;
use App\Models\Concerns\HasPhotos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An owner's REQUEST to add a unit (Phase 2 · Sub-gate 6; enriched by the Add-Unit WIZARD, My Units · GATE 3).
 * Owner-scoped via {@see BelongsToOwner} — an owner sees/creates only their OWN requests; the operator (owner
 * scope inactive) sees them tenant-scoped. This is an INTAKE record: it never creates a unit. The operator
 * reviews it and creates the real unit as their own action.
 *
 * The wizard fields (location · size/capacity · a PROPOSED rate/cleaning fee + min stay) are the owner's proposed
 * values on a still-pending request — NOT live inventory and NOT a rate write (#39). Amenities attach through the
 * shared {@see HasAmenities} pivot and photos through the shared {@see HasPhotos} media collection — reuse, never
 * a parallel structure. Money is integer minor units + currency (#17).
 *
 * `status` spans the full lifecycle enum **draft | pending | approved | declined** (DO9-2 added `draft` by an
 * ALTER, which the static analyser cannot read from the create-migration's narrower union — the same situation
 * {@see Payout} documents), so it is typed `string` here rather than that stale three-value union.
 * ⚠️ `requested_name` is nullable ONLY because a draft may be saved before it is typed; every FILED request has
 * one, enforced by the submit validation.
 *
 * @property string $status
 * @property string|null $requested_name
 */
class UnitRequest extends Model
{
    use BelongsToOrganization, BelongsToOwner, HasAmenities, HasFactory, HasPhotos, HasUlids, SoftDeletes;

    /**
     * DO9-2 — the statuses a request has once it has actually been FILED. ⚠️ `draft` is deliberately absent:
     * a draft is the owner's own unfinished work and is **owner-private until submitted**, so it must never
     * reach the operator's desk. ⚠️ THE ONE DEFINITION (§N.8f) — the operator review list and its KPI counts
     * both derive from {@see scopeFiled()}, so a new operator surface cannot quietly re-include drafts by
     * writing its own status list.
     *
     * ⚠️ DO9-3 — `withdrawn` IS filed, and that is deliberate. A draft was never sent; a withdrawn request
     * genuinely WAS, and the operator may already have read it. Hiding it on retraction would delete a row out
     * from under someone mid-review and leave "what happened to that request?" unanswerable. It stays visible
     * and labelled, while being non-actionable (every operator action is gated on `pending`) and absent from
     * the pending count.
     *
     * @var list<string>
     */
    public const FILED_STATUSES = ['pending', 'approved', 'declined', 'withdrawn'];

    /**
     * Requests that have genuinely been filed — everything the OPERATOR is entitled to see. Drafts are excluded
     * by construction rather than by a caller remembering to exclude them.
     *
     * @param  Builder<UnitRequest>  $query
     * @return Builder<UnitRequest>
     */
    public function scopeFiled(Builder $query): Builder
    {
        return $query->whereIn('status', self::FILED_STATUSES);
    }

    /** A still-private draft: saved by the owner, never filed, invisible to the operator. */
    public function isDraft(): bool
    {
        return (string) $this->status === 'draft';
    }

    protected $fillable = [
        'organization_id',
        'owner_id',
        'requested_name',
        'requested_kind',
        'location',
        'street_address',
        'bedrooms',
        'bathrooms',
        'sleeps',
        'requested_rate_minor',
        'cleaning_fee_minor',
        'min_nights',
        'currency',
        'notes',
        'status',
        'created_unit_id',
        'review_note',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'sleeps' => 'integer',
            'requested_rate_minor' => 'integer',
            'cleaning_fee_minor' => 'integer',
            'min_nights' => 'integer',
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

    /**
     * The REAL unit an approval created from this request (#104 GATE 1) — null until fulfilled. Clean provenance:
     * a fulfilled request points at what it became.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function createdUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'created_unit_id');
    }
}
