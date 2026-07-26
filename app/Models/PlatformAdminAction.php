<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Platform\PlatformAuditLogger;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SUPER ADMIN · GATE 0 — an immutable record of a platform-admin action (see the migration). Written only by
 * {@see PlatformAuditLogger}; never edited. Deliberately NOT the tenant `audit_events`
 * table — a platform action is a platform event, kept in its own ledger.
 */
class PlatformAdminAction extends Model
{
    use HasUlids;

    public $timestamps = false;   // write-once; only created_at is stamped (by the logger)

    protected $fillable = [
        'platform_admin_id',
        'action',
        'target_type',
        'target_id',
        'meta',
        'ip',
        'user_agent',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function platformAdmin(): BelongsTo
    {
        return $this->belongsTo(PlatformAdmin::class);
    }
}
