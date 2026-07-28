<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A TRUSTED DEVICE (Wave B · GATE 4) — a browser the user chose to trust after a 2FA challenge, so it may skip
 * the challenge until it expires or is revoked. Only the cookie token's SHA-256 hash is persisted. Not tenant-
 * scoped: it belongs to a USER (auth realm), resolved self-only by the authenticated user's id.
 *
 * @property ?Carbon $last_used_at
 * @property ?Carbon $expires_at
 * @property ?Carbon $revoked_at
 */
class TrustedDevice extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'token_hash',
        'label',
        'ip',
        'user_agent',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** Live = not revoked AND not expired — the only state that may skip a 2FA challenge. */
    public function isLive(): bool
    {
        return $this->revoked_at === null && $this->expires_at !== null && $this->expires_at->isFuture();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
