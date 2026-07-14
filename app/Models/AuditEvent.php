<?php

namespace App\Models;

use App\Support\Events\AuditEventRecorder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Security/compliance trail — write-once and immutable (only `created_at`, no updates, no
 * soft delete). Not tenant-scoped at the model level: the actor may be a user, agent, or
 * the system, and the log must survive tenant lifecycle. Written via {@see AuditEventRecorder}.
 *
 * @property string $id
 * @property string|null $organization_id
 * @property string|null $actor_type
 * @property string|null $actor_id
 * @property string $action
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property array<string, mixed>|null $before
 * @property array<string, mixed>|null $after
 * @property Carbon|null $created_at
 */
class AuditEvent extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'actor_type',
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'before',
        'after',
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
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
