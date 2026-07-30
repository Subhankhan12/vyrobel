<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\AgreementAmendmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * An operator-published amendment to an owner's {@see ManagementAgreement} — the REAL, buildable version
 * of the wireframe's "Amendment ready to sign" (Owner Agreement exact-match #82). `new_terms` json holds
 * the changed agreement fields; on the owner's acknowledgment a superseding effective-dated agreement is
 * created ({@see AgreementAmendmentService}).
 *
 * ⚠️ NOT owner-scoped (like ManagementAgreement) — carries BelongsToOrganization only. Every read/write
 * scopes EXPLICITLY on the resolved owner ids; fail-closed.
 *
 * @property array<string, mixed>|null $new_terms
 * @property Carbon $effective_date
 * @property string $version
 * @property string $status
 */
class AgreementAmendment extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_APPLIED = 'applied';

    protected $fillable = [
        'organization_id',
        'owner_id',
        'version',
        'title',
        'summary',
        'effective_date',
        'new_terms',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'new_terms' => 'array',
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
     * @return HasOne<AgreementAmendmentAcknowledgment, $this>
     */
    public function acknowledgment(): HasOne
    {
        return $this->hasOne(AgreementAmendmentAcknowledgment::class, 'amendment_id');
    }

    /** An amendment the owner has not yet acknowledged (still awaiting consent). */
    public function needsAcknowledgment(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isAcknowledged(): bool
    {
        return $this->status === self::STATUS_APPLIED;
    }

    /**
     * Amendments visible to the owner: published (awaiting) or applied (acknowledged) — never drafts.
     *
     * @param  Builder<AgreementAmendment>  $query
     * @return Builder<AgreementAmendment>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PUBLISHED, self::STATUS_APPLIED]);
    }
}
