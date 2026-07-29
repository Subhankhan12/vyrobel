<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\InboxService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One emoji reaction by one {@see User} on one {@see Message} (tenant-scoped). The `unique(message_id,
 * user_id, emoji)` index makes it idempotent; the toggle in {@see InboxService} adds or
 * removes the single row. The emoji is validated against a fixed allow-list before it ever reaches here.
 *
 * @property string $id
 * @property string $message_id
 * @property string $user_id
 * @property string $emoji
 */
class MessageReaction extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = [
        'organization_id',
        'message_id',
        'user_id',
        'emoji',
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
