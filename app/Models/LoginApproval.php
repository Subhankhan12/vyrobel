<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A LOGIN APPROVAL request (Wave B · GATE 4) — one pending "approve this new device" challenge. Only the token's
 * SHA-256 hash is stored; the plaintext rides the emailed link once. Short-lived, single-use, self-only.
 *
 * @property ?Carbon $approved_at
 * @property ?Carbon $consumed_at
 * @property ?Carbon $expires_at
 */
class LoginApproval extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'token_hash',
        'ip',
        'user_agent',
        'approved_at',
        'consumed_at',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'consumed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** Still actionable — not consumed AND not expired. */
    public function isPending(): bool
    {
        return $this->consumed_at === null && $this->expires_at !== null && $this->expires_at->isFuture();
    }

    /** Approved (link clicked), not yet consumed, not expired → the pending login may now complete. */
    public function isApproved(): bool
    {
        return $this->approved_at !== null && $this->isPending();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
