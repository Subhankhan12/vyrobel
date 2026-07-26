<?php

declare(strict_types=1);

namespace App\Models;

use App\Listeners\RecordAuthActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * SUPER ADMIN · BUILD 2 · GATE 1 — a single auth security-signal row (see the migration for the privacy posture:
 * security-monitoring purpose, 90-day retention, /admin-only access). Written ONLY by
 * {@see RecordAuthActivity} (added onto the framework auth events; the tenant login flow is not
 * modified). Immutable.
 */
class LoginActivity extends Model
{
    use HasUlids;

    protected $table = 'login_activity';

    public $timestamps = false;   // occurred_at is the only timestamp, set by the listener

    /** How long an auth-activity row is retained before the scheduled prune purges it (privacy — GDPR). */
    public const RETENTION_DAYS = 90;

    protected $fillable = [
        'event',
        'guard',
        'subject_type',
        'subject_id',
        'identifier',
        'ip',
        'user_agent',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }
}
