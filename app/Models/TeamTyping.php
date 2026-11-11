<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\TeamChatService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * When a teammate last typed in a conversation (team chat, 2026-11-11). One row per (conversation, person); written by
 * {@see TeamChatService::typing()}, cleared when they send, and read as "typing now" only while it is under
 * {@see TeamChatService::TYPING_SECONDS} old.
 *
 * @property string $conversation_id
 * @property string $user_id
 * @property Carbon $typed_at
 */
class TeamTyping extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $table = 'team_typing';

    protected $fillable = ['organization_id', 'conversation_id', 'user_id', 'typed_at'];

    protected function casts(): array
    {
        return ['typed_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
