<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\TeamChatService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A conversation between people who work in a workspace (team chat, 2026-10-06 — `docs/TEAM-CHAT-DESIGN.md`). It belongs
 * to its MEMBERS: only they may read it, its messages or its files. Written only by {@see TeamChatService}.
 *
 * @property string $id
 * @property string $kind direct|group
 * @property string|null $title
 * @property string|null $direct_key
 * @property Carbon|null $last_message_at
 */
class TeamConversation extends Model
{
    use BelongsToOrganization, HasUlids;

    public const DIRECT = 'direct';

    public const GROUP = 'group';

    protected $fillable = ['organization_id', 'kind', 'title', 'direct_key', 'created_by', 'last_message_at'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    /** @return HasMany<TeamConversationMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(TeamConversationMember::class, 'conversation_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_conversation_members', 'conversation_id', 'user_id')
            ->withPivot('last_read_at');
    }

    /** @return HasMany<TeamMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(TeamMessage::class, 'conversation_id');
    }

    /** The two members' ids in a fixed order — one direct conversation per pair, whoever starts it. */
    public static function directKey(string $a, string $b): string
    {
        return strcmp($a, $b) < 0 ? $a.'|'.$b : $b.'|'.$a;
    }
}
