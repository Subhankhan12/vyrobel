<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\TeamChatService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One emoji by one teammate on one {@see TeamMessage} (team chat, 2026-11-11). The `unique(message_id, user_id, emoji)` index
 * makes it idempotent; {@see TeamChatService::react()} adds or removes the single row, and only for an emoji on the fixed
 * allow-list.
 *
 * @property string $id
 * @property string $message_id
 * @property string $user_id
 * @property string $emoji
 */
class TeamMessageReaction extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = ['organization_id', 'message_id', 'user_id', 'emoji'];

    /** @return BelongsTo<TeamMessage, $this> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(TeamMessage::class, 'message_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
