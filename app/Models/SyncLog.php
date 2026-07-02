<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only, per-attempt log line for a {@see SyncJob} (write-once — only `created_at`,
 * same pattern as `reservation_status_history`). The human-readable trail behind the sync-error
 * dashboard.
 */
class SyncLog extends Model
{
    use BelongsToOrganization, HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'sync_job_id',
        'attempt',
        'level',
        'message',
        'context',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SyncJob, $this>
     */
    public function syncJob(): BelongsTo
    {
        return $this->belongsTo(SyncJob::class);
    }
}
