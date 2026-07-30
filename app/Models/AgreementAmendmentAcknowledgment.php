<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\AgreementAmendmentService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The owner's REAL record of consent to an {@see AgreementAmendment} (Owner Agreement exact-match #82):
 * WHO acknowledged WHICH version, WHEN. Created inside {@see AgreementAmendmentService} and
 * AUDITED (#20). One per amendment (unique) — a re-submit is a no-op.
 *
 * ⚠️ This is an IN-PORTAL ACKNOWLEDGMENT, not a legally-binding e-signature. Vyrobel provides no e-sig
 * infrastructure (identity assurance / signature certificate) — that is honest-deferred. The record is
 * real and audited; nothing here claims legal signature validity.
 *
 * @property Carbon|null $acknowledged_at
 */
class AgreementAmendmentAcknowledgment extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'amendment_id',
        'owner_id',
        'acknowledged_by',
        'acknowledged_version',
        'acknowledged_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AgreementAmendment, $this>
     */
    public function amendment(): BelongsTo
    {
        return $this->belongsTo(AgreementAmendment::class, 'amendment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}
