<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One person in a team conversation, and how far they have read (team chat, 2026-10-06).
 *
 * @property string $conversation_id
 * @property string $user_id
 * @property string|null $last_read_message_id
 * @property Carbon|null $last_read_at
 */
class TeamConversationMember extends Model
{
    use BelongsToOrganization, HasUlids;

    protected $fillable = ['organization_id', 'conversation_id', 'user_id', 'last_read_message_id', 'last_read_at'];

    protected function casts(): array
    {
        return ['last_read_at' => 'datetime'];
    }

    /** @return BelongsTo<TeamConversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(TeamConversation::class, 'conversation_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
