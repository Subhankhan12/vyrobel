<?php

namespace App\Models;

use App\Jobs\Sync\RetryableSyncJob;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Durable, business-visible record of one channel-sync operation (ARI push / booking import /
 * modify / cancel / mapping sync). Tenant-scoped business data — unlike `domain_events`/
 * `audit_events`, this is per-tenant operational state a mapping-health dashboard reads, so it
 * uses {@see BelongsToOrganization} rather than the "stamped, not scoped" event-log pattern.
 *
 * `idempotency_key` is unique — the single mechanism that keeps retried/redelivered sync work
 * from double-applying. Status/attempts are mutated only by {@see RetryableSyncJob}.
 */
class SyncJob extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = [
        'organization_id',
        'channel_id',
        'direction',
        'type',
        'idempotency_key',
        'status',
        'attempts',
        'max_attempts',
        'available_at',
        'payload_summary',
        'last_error',
        'processed_at',
        'dead_lettered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload_summary' => 'array',
            'available_at' => 'datetime',
            'processed_at' => 'datetime',
            'dead_lettered_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<SyncLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
