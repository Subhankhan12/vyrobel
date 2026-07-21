<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An owner-TEAM invitation (Phase 7c · SG3) — the owner-realm parallel of {@see OwnerInvite}. The
 * `token_hash` is the sha256 of a high-entropy random token; the plaintext is only ever in the emailed
 * link, never persisted. Single-use (`status` → accepted), expiring (`expires_at`), revocable,
 * tenant-scoped. `owner_id` is the owner the invitee JOINS as an {@see OwnerUser} at `role`.
 *
 * @property string $owner_id
 * @property string $role
 * @property string $status
 * @property Carbon|null $expires_at
 * @property Carbon|null $accepted_at
 */
class OwnerTeamInvite extends Model
{
    use BelongsToOrganization, HasFactory, HasUlids;

    protected $fillable = [
        'organization_id',
        'owner_id',
        'invited_by',
        'email',
        'token_hash',
        'role',
        'status',
        'expires_at',
        'accepted_at',
    ];

    /** @var list<string> */
    protected $hidden = ['token_hash'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
