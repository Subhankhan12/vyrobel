<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A per-user star (bookmark) on a {@see Message} (tenant-scoped). Private view-state — the current user's
 * own flag, invisible to the other party — so a starred panel shows only the viewer's stars. The
 * `unique(message_id, user_id)` index makes the toggle idempotent.
 *
 * @property string $id
 * @property string $message_id
 * @property string $user_id
 */
class MessageStar extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = [
        'organization_id',
        'message_id',
        'user_id',
    ];

    /**
     * @return BelongsTo<Message, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
