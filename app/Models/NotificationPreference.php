<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\NotificationPreferences;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One per-user, per-event, per-channel notification toggle (Phase 3 · Sub-gate 2). Scoped to a User (not a
 * tenant) — a preference is personal. Read/written only via {@see NotificationPreferences}, which
 * validates the event_key against the fixed catalog (typed, not free-form KV).
 */
class NotificationPreference extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'event_key',
        'channel',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
