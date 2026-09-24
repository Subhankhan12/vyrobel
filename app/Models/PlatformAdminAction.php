<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\PlatformWriteRefused;
use App\Support\Platform\PlatformAuditLogger;
use App\Support\Platform\PlatformWriteGuard;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SUPER ADMIN · GATE 0 — an immutable record of a platform-admin action (see the migration). Written only by
 * {@see PlatformAuditLogger}. Deliberately NOT the tenant `audit_events` table — a platform action is a platform
 * event, kept in its own ledger.
 *
 * ⚠️⚠️ APPEND-ONLY, ENFORCED — NOT DESCRIBED (ADMIN 01 · Phase 1). "Never edited" used to be this docblock and
 * nothing else. It is now refused TWICE: here, where an update or delete of a model throws; and at the database
 * connection ({@see PlatformWriteGuard}), which also refuses the paths model events never see —
 * `query()->update()`, `DB::table()->delete()`, raw SQL. ⚠️ Never pruned: RetentionPolicy lists no window for it.
 *
 * @property string|null $reason the admin's written reason — present on every write since Phase 1
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
        'reason',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw PlatformWriteRefused::appendOnlyLedger('update');
        });

        static::deleting(static function (): never {
            throw PlatformWriteRefused::appendOnlyLedger('delete');
        });
    }

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
