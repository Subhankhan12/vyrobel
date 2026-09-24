<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Platform\BreakGlass;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * ADMIN 01 · PHASE 3 — one break-glass session: which admin, which organisation, whose access was borrowed, why,
 * and when it started, expires and ended. ⚠️ A PLATFORM record, not tenant data — deliberately NOT
 * BelongsToOrganization (see the migration). Written only by {@see BreakGlass}.
 *
 * @property string $id
 * @property string $platform_admin_id
 * @property string $target_organization_id
 * @property string $viewing_user_id
 * @property string $reason
 * @property string $session_key_hash
 * @property Carbon $started_at
 * @property Carbon $expires_at
 * @property Carbon|null $ended_at
 * @property string|null $ended_reason
 * @property int $pages_viewed
 * @property Carbon|null $tenant_notified_at
 */
class BreakGlassSession extends Model
{
    use HasUlids;

    protected $fillable = [
        'platform_admin_id', 'target_organization_id', 'viewing_user_id', 'reason', 'session_key_hash',
        'started_at', 'expires_at', 'ended_at', 'ended_reason', 'pages_viewed', 'tenant_notified_at',
    ];

    protected $hidden = ['session_key_hash'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'ended_at' => 'datetime',
            'tenant_notified_at' => 'datetime',
            'pages_viewed' => 'integer',
        ];
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null && $this->expires_at->isFuture();
    }

    public function platformAdmin(): BelongsTo
    {
        return $this->belongsTo(PlatformAdmin::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'target_organization_id');
    }

    public function viewingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewing_user_id');
    }
}
