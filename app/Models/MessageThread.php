<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToOwner;
use App\Models\Scopes\OwnerScope;
use App\Services\InboxService;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A unified-inbox conversation (tenant-scoped). `channel` is the communication medium enum
 * (manual/email/whatsapp/booking_com/airbnb) — NOT the OTA-distribution `Channel` model. Lifecycle
 * open → snoozed/closed is guarded by {@see InboxService}.
 *
 * TWO KINDS OF CONVERSATION, discriminated by `guest_id` XOR `owner_id` (deliberately not a type enum —
 * the FKs already say which, and a third source of truth could disagree with them):
 *   • OPERATOR ↔ GUEST  — `guest_id` set (+ optional `reservation_id` for booking context). The original
 *     Unified Inbox; arrives over a real channel, routed via the encrypted `participant_identity`.
 *   • OPERATOR ↔ OWNER  — `owner_id` set (Wave 3). In-app over `manual` (recording IS the delivery); no
 *     participant_identity, because there is nothing external to route to.
 *
 * `participant_identity` is ENCRYPTED (the guest's channel address, used to route outbound) — never
 * rendered on the operator surface, and never a competing copy of a linked guest's PII.
 *
 * @property string $id
 * @property string $channel
 * @property string|null $external_thread_ref
 * @property string|null $subject
 * @property string|null $guest_id
 * @property string|null $owner_id
 * @property string|null $reservation_id
 * @property string|null $participant_identity
 * @property string $status
 * @property string|null $assigned_to
 * @property int $unread_count
 * @property Carbon|null $last_message_at
 * @property Carbon|null $last_inbound_at
 * @property Carbon|null $owner_last_read_at
 * @property Carbon|null $operator_last_read_at
 * @property string|null $pinned_message_id
 * @property Carbon|null $snoozed_until
 * @property array<string, mixed>|null $meta
 */
class MessageThread extends Model
{
    /**
     * ⚠️ `BelongsToOwner` IS A SECURITY BOUNDARY HERE, NOT A CONVENIENCE (Wave 3).
     *
     * It applies {@see OwnerScope}, and the entire two-inbox boundary falls out of it:
     *   • `/owner` (OwnerContext ACTIVE) → `owner_id IN (their ids)`. A guest thread has `owner_id = NULL`, so
     *     it is excluded **automatically** — the owner portal cannot see an operator↔guest conversation
     *     without anyone writing a predicate. A non-linked user gets `1=0` (fail-closed).
     *   • `/app` (OwnerContext INACTIVE) → no constraint. The operator sees every thread and can reply, so the
     *     loop closes without a second query path.
     *
     * REMOVING THIS TRAIT SILENTLY LEAKS EVERY OWNER'S THREADS TO EVERY OTHER OWNER — the scope does not warn,
     * it simply stops running. `OwnerInboxIsolationTest` mutation-proves that: delete the trait and it fails.
     */
    use BelongsToOrganization, BelongsToOwner, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'channel',
        'external_thread_ref',
        'subject',
        'guest_id',
        // Wave 3 — the owner↔operator counterparty. `guest_id` XOR `owner_id` IS the discriminator: the two
        // FKs already say which kind of conversation this is, so there is deliberately no `participant_type`
        // enum to drift out of step with them.
        'owner_id',
        'reservation_id',
        'participant_identity',
        'status',
        'assigned_to',
        'unread_count',
        'last_message_at',
        'last_inbound_at',
        // The OWNER's read position — distinct from `unread_count`, which is the OPERATOR's (markRead() zeroes
        // that one, so an owner-side read must never touch it).
        'owner_last_read_at',
        // The GUEST's read position (Phase 4c · SG3) — same reasoning as owner_last_read_at: a guest-portal read
        // must never zero the OPERATOR's `unread_count`.
        'guest_last_read_at',
        // The OPERATOR's read position (GATE 2 · read-receipts) — the mirror of the two above. It is what makes
        // the owner-facing "Read" tick REAL: the owner's message is Read once this is at/after it.
        'operator_last_read_at',
        // The one pinned message per thread (GATE 2). A soft pointer — no FK; cleared in app code if it vanishes.
        'pinned_message_id',
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
            'owner_last_read_at' => 'datetime',
            'guest_last_read_at' => 'datetime',
            'operator_last_read_at' => 'datetime',
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
     * The OWNER this conversation is with, on an owner↔operator thread — null on a guest thread.
     * `guest_id` XOR `owner_id`: the pair of FKs is the discriminator (see the class docblock).
     *
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /** @return BelongsTo<Guest, $this> */
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
