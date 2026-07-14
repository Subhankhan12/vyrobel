<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Services\InboxService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A unified-inbox conversation (tenant-scoped). `channel` is the communication medium enum
 * (manual/email/whatsapp/booking_com/airbnb) — NOT the OTA-distribution `Channel` model. Threads
 * link optionally to a real {@see Guest} + {@see Reservation} for booking context; lifecycle
 * open → snoozed/closed is guarded by {@see InboxService}.
 *
 * `participant_identity` is ENCRYPTED (the guest's channel address, used to route outbound) — never
 * rendered on the operator surface, and never a competing copy of a linked guest's PII.
 *
 * @property string $id
 * @property string $channel
 * @property string|null $external_thread_ref
 * @property string|null $subject
 * @property string|null $guest_id
 * @property string|null $reservation_id
 * @property string|null $participant_identity
 * @property string $status
 * @property string|null $assigned_to
 * @property int $unread_count
 * @property Carbon|null $last_message_at
 * @property Carbon|null $last_inbound_at
 * @property Carbon|null $snoozed_until
 * @property array<string, mixed>|null $meta
 */
class MessageThread extends Model
{
    use BelongsToOrganization, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'channel',
        'external_thread_ref',
        'subject',
        'guest_id',
        'reservation_id',
        'participant_identity',
        'status',
        'assigned_to',
        'unread_count',
        'last_message_at',
        'last_inbound_at',
        'snoozed_until',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'participant_identity' => 'encrypted',   // guest PII — routing anchor, never rendered
            'meta' => 'array',
            'unread_count' => 'integer',
            'last_message_at' => 'datetime',
            'last_inbound_at' => 'datetime',
            'snoozed_until' => 'datetime',
        ];
    }

    /** A thread still in play (can receive/send) — open or snoozed, not closed. */
    public function isActive(): bool
    {
        return in_array($this->status, ['open', 'snoozed'], true);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'thread_id');
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
