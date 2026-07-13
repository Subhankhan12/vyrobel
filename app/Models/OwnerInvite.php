<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An owner invitation (Auth Phase B). The `token_hash` is the sha256 of a high-entropy random token —
 * the plaintext is only ever in the emailed link, never persisted. Single-use (`status` → accepted),
 * expiring (`expires_at`), revocable, and tenant-scoped.
 *
 * Larastan doesn't infer the array/datetime casts on these columns, so they carry explicit @property types.
 * `owner_id` is nullable — owner invites carry an owner link; STAFF invites (Hotel Manager / Accountant)
 * have no owner and leave it null (see the make-owner_id-nullable migration).
 *
 * @property string|null $owner_id
 * @property array<int, string>|null $assigned_unit_ids
 * @property Carbon|null $expires_at
 * @property Carbon|null $accepted_at
 */
class OwnerInvite extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'owner_id',
        'invited_by',
        'email',
        'token_hash',
        'role',
        'assigned_unit_ids',
        'commission_pct',
        'status',
        'expires_at',
        'accepted_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = ['token_hash'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_unit_ids' => 'array',
            'commission_pct' => 'decimal:2',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /** A usable invite: still pending and not past its expiry. */
    public function isRedeemable(): bool
    {
        return $this->status === 'pending'
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }
}
