<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Platform\PlatformAdminSessions;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * ADMIN 01 · PHASE 1 — one platform-admin session, tracked by the application (see the migration for why the
 * framework's `sessions` table cannot do this on production). Written only by {@see PlatformAdminSessions}.
 *
 * @property string $id
 * @property string $platform_admin_id
 * @property string $session_key_hash
 * @property string|null $ip
 * @property string|null $user_agent
 * @property Carbon|null $started_at
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $ended_at
 * @property string|null $ended_reason
 * @property string|null $ended_by
 */
class PlatformAdminSession extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'platform_admin_id', 'session_key_hash', 'ip', 'user_agent',
        'started_at', 'last_seen_at', 'ended_at', 'ended_reason', 'ended_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PlatformAdmin, $this> */
    public function platformAdmin(): BelongsTo
    {
        return $this->belongsTo(PlatformAdmin::class);
    }
}
