<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasMedia;
use App\Support\TeamChat\TeamChatFiles;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One message in a team conversation (team chat, 2026-10-06). Its pictures, files and voice note are `media` rows on the
 * private disk ({@see TeamChatFiles}); its text may be empty when one of those came alone.
 *
 * @property string $id
 * @property string $conversation_id
 * @property string|null $user_id
 * @property string|null $body
 * @property Carbon $created_at
 */
class TeamMessage extends Model
{
    use BelongsToOrganization, HasMedia, HasUlids;

    protected $fillable = ['organization_id', 'conversation_id', 'user_id', 'body'];

    /** @return BelongsTo<TeamConversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(TeamConversation::class, 'conversation_id');
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
