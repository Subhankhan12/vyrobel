<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasMedia;
use App\Services\InboxService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * One message in a {@see MessageThread} (tenant-scoped). `direction` = inbound|outbound. Inbound
 * exactly-once is anchored by `unique(organization_id, channel, external_message_id)` — a duplicate
 * provider delivery collapses to one row via `createOrFirst` in {@see InboxService}.
 *
 * `sender_label` is a NON-PII display label (guest name / "You"); the encrypted contact lives on the
 * thread's `participant_identity`, never here. Outbound lifecycle: queued → sent → delivered/failed.
 *
 * @property string $id
 * @property string $thread_id
 * @property string $direction
 * @property string $channel
 * @property string|null $external_message_id
 * @property string $body
 * @property string|null $author_user_id
 * @property string|null $sender_label
 * @property string $delivery_status
 * @property string|null $delivery_error
 * @property Carbon|null $sent_at
 * @property Carbon|null $received_at
 * @property Carbon|null $failed_at
 * @property string|null $reply_to_message_id
 */
class Message extends Model
{
    use BelongsToOrganization, HasMedia, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'thread_id',
        'direction',
        'channel',
        'external_message_id',
        'body',
        'reply_to_message_id',
        'author_user_id',
        'sender_label',
        'delivery_status',
        'delivery_error',
        'sent_at',
        'received_at',
        'failed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'received_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MessageThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'thread_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    /**
     * The earlier message this one QUOTES (reply-to), or null — same thread, tenant-scoped like every read.
     *
     * @return BelongsTo<Message, $this>
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    /**
     * @return HasMany<MessageReaction, $this>
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * @return HasMany<MessageStar, $this>
     */
    public function stars(): HasMany
    {
        return $this->hasMany(MessageStar::class);
    }
}
