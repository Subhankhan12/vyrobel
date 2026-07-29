<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\Sync\ProcessInboundMessageJob;
use App\Messaging\Contracts\MessageChannel;
use App\Messaging\MessageChannelResolver;
use App\Models\Guest;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\MessageStar;
use App\Models\MessageThread;
use App\Models\Reservation;
use App\Models\SyncJob;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use App\Support\Guest\GuestName;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Unified-inbox message + thread lifecycle (mirrors the HousekeepingService/MaintenanceService pattern).
 * The single write path for the inbox: threads + messages are created/updated ONLY here, so idempotency,
 * counters, guarded transitions and event discipline live in one place.
 *
 * INBOUND is idempotent: a duplicate provider delivery (same `external_message_id`) collapses to one
 * message via `createOrFirst` on the `unique(organization_id, channel, external_message_id)` index —
 * the same exactly-once-over-at-least-once discipline as `webhook_events`. OUTBOUND is sent through the
 * resolved {@see MessageChannel} (the real ManualChannel now; the deferred
 * email/WhatsApp/OTA skeletons throw "not configured"). Post-commit `message.*`/`thread.*` domain events
 * are ONE-DIRECTIONAL — no consumer re-triggers messaging.
 *
 * Tenant-scoped throughout (MessageThread + Message are BelongsToOrganization); PII: the guest's channel
 * address is the thread's ENCRYPTED `participant_identity` (routing only) — never copied into messages.
 */
class InboxService
{
    public function __construct(
        private DomainEventRecorder $domainEvents,
        private AuditEventRecorder $audit,
        private MessageChannelResolver $channels,
        private SyncJobDispatcher $dispatcher,
        private GuestMatcher $guests,
    ) {}

    /**
     * A message arrived on a channel — process it RELIABLY (queued, retried, backed-off, dead-lettered,
     * idempotent) via {@see ProcessInboundMessageJob} on the reliability backbone. The idempotency key
     * is the external message id (falls back to a random key when a channel has none), so a duplicate
     * delivery dedupes at the sync-job level too — belt-and-suspenders with the message createOrFirst.
     * This is how real inbound webhooks (deferred) will feed the inbox; the manual channel uses it now.
     *
     * @param  array<string, mixed>  $rawPayload
     */
    public function receiveInbound(string $channel, array $rawPayload): SyncJob
    {
        $externalId = isset($rawPayload['external_message_id'])
            ? (string) $rawPayload['external_message_id']
            : 'nomsgid:'.Str::uuid()->toString();

        return $this->dispatcher->dispatch(
            type: 'inbox_inbound',
            direction: 'inbound',
            idempotencyKey: "inbox:{$channel}:{$externalId}",
            jobClass: ProcessInboundMessageJob::class,
            payloadSummary: ['channel' => $channel, 'payload' => $rawPayload],
        );
    }

    /**
     * Open a new thread on a channel (optionally pre-linked to a guest/reservation).
     *
     * @param  array{channel: string, external_thread_ref?: string|null, subject?: string|null, guest_id?: string|null, owner_id?: string|null, reservation_id?: string|null, participant_identity?: string|null, assigned_to?: string|null, meta?: array<string, mixed>|null}  $data
     */
    public function startThread(array $data): MessageThread
    {
        $thread = MessageThread::create([
            'channel' => $data['channel'],
            'external_thread_ref' => $data['external_thread_ref'] ?? null,
            'subject' => $data['subject'] ?? null,
            'guest_id' => $data['guest_id'] ?? null,
            // Wave 3 — an owner↔operator thread. `guest_id` XOR `owner_id` is the discriminator.
            'owner_id' => $data['owner_id'] ?? null,
            'reservation_id' => $data['reservation_id'] ?? null,
            'participant_identity' => $data['participant_identity'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'meta' => $data['meta'] ?? null,
            'status' => 'open',
        ]);

        $this->domainEvents->record('inbox.thread_opened', $thread, ['channel' => $thread->channel]);
        $this->audit->record('inbox.thread_opened', $thread, null, ['status' => 'open']);

        return $thread;
    }

    /**
     * Post an INBOUND message — IDEMPOTENT. A duplicate `external_message_id` returns the existing
     * message and changes nothing (no counter bump, no duplicate). The thread is resolved by its
     * threading anchor (`channel` + `external_thread_ref`) — race-safe via createOrFirst on both the
     * thread and the message unique indexes.
     *
     * @param  array{channel: string, body: string, external_thread_ref?: string|null, external_message_id?: string|null, participant_identity?: string|null, sender_label?: string|null, subject?: string|null, guest_id?: string|null, reservation_id?: string|null}  $data
     */
    public function postInbound(array $data): Message
    {
        $channel = $data['channel'];
        $externalMessageId = $data['external_message_id'] ?? null;

        // NOT wrapped in an outer transaction: createOrFirst's catch-the-race depends on the losing
        // INSERT's re-SELECT seeing the COMMITTED winner — which a long REPEATABLE-READ snapshot would
        // hide. Running in autocommit (as the Channex webhook leg does) is what makes it race-safe. The
        // unique indexes are the correctness guarantee; the counter bump is an atomic increment.

        // Resolve/link the guest by channel identity via the REUSED matcher (unless a guest_id was
        // supplied). A returning address matches the existing guest; an unknown one creates + links.
        $guest = $this->resolveInboundGuest($data);
        if ($guest !== null) {
            $data['guest_id'] = $guest->id;
        }

        $thread = $this->resolveInboundThread($data);

        // Backfill the guest link on a thread that started anonymous (identity resolved later).
        if ($thread->guest_id === null && ($data['guest_id'] ?? null) !== null) {
            $thread->forceFill(['guest_id' => $data['guest_id']])->save();
        }

        // Idempotency anchor: with an external id, createOrFirst collapses a duplicate delivery to one
        // row (the losing INSERT becomes a fetch via the unique index). Without one (a manual note has no
        // provider id), every inbound is a distinct message.
        if ($externalMessageId !== null) {
            $message = Message::createOrFirst(
                ['channel' => $channel, 'external_message_id' => $externalMessageId],
                $this->inboundAttributes($thread, $data),
            );

            if (! $message->wasRecentlyCreated) {
                return $message;   // duplicate delivery — idempotent no-op (no counter bump)
            }
        } else {
            $message = Message::create($this->inboundAttributes($thread, $data));
        }

        // A genuinely new inbound message: bump the thread ATOMICALLY (concurrent distinct inbounds must
        // not lose an increment) + reopen if it was closed.
        $thread->increment('unread_count');
        $thread->forceFill([
            'last_message_at' => $message->received_at,
            'last_inbound_at' => $message->received_at,
            'status' => $thread->status === 'closed' ? 'open' : $thread->status,
        ])->save();

        $this->domainEvents->record('inbox.message_received', $message, [
            'thread_id' => (string) $thread->id,
            'channel' => $channel,
        ]);
        $this->audit->record('inbox.message_received', $message, null, ['direction' => 'inbound']);

        return $message;
    }

    /**
     * Send an OUTBOUND reply through the thread's channel. The real ManualChannel records it delivered;
     * a deferred channel's adapter throws "not configured" — surfaced cleanly (the queued record is
     * marked failed and the exception re-thrown). Replying reactivates a snoozed/closed thread.
     */
    public function sendOutbound(MessageThread $thread, string $body, ?User $author = null): Message
    {
        // NOT wrapped in a transaction: the queued record + its terminal status must SURVIVE a
        // channel throw, so a deferred channel leaves an honest `failed` message (not a rolled-back
        // ghost). Each write is a single row; no cross-row atomicity is needed.
        $message = Message::create([
            'thread_id' => $thread->id,
            'direction' => 'outbound',
            'channel' => $thread->channel,
            'body' => $body,
            'author_user_id' => $author?->id,
            'sender_label' => $author instanceof User ? (string) $author->name : 'Operator',
            'delivery_status' => 'queued',
        ]);

        $adapter = $this->channels->resolve($thread->channel);

        try {
            // participant_identity decrypts on access; the adapter never reads guest PII itself.
            $result = $adapter->send($message, $thread->participant_identity);
        } catch (\Throwable $e) {
            $message->forceFill([
                'delivery_status' => 'failed',
                'delivery_error' => $e->getMessage(),
                'failed_at' => now(),
            ])->save();

            throw $e;   // e.g. a deferred channel: "… not configured" — surfaced + recorded, never faked
        }

        $message->forceFill([
            'delivery_status' => $result->status,
            'external_message_id' => $result->externalMessageId,
            'delivery_error' => $result->failureReason,
            'sent_at' => now(),
        ])->save();

        $thread->forceFill([
            'last_message_at' => now(),
            'status' => $thread->status === 'closed' ? 'open' : $thread->status,
        ])->save();

        $this->domainEvents->record('inbox.message_sent', $message, [
            'thread_id' => (string) $thread->id,
            'channel' => $thread->channel,
        ]);
        $this->audit->record('inbox.message_sent', $message, null, ['direction' => 'outbound', 'delivery_status' => $result->status]);

        return $message;
    }

    /** Assign (or unassign) the thread to a staff user. */
    public function assign(MessageThread $thread, ?string $userId): MessageThread
    {
        if ((string) $thread->assigned_to === (string) $userId) {
            return $thread;   // idempotent
        }

        $from = $thread->assigned_to;
        $thread->update(['assigned_to' => $userId]);

        $this->domainEvents->record('inbox.thread_assigned', $thread, ['assigned_to' => $userId]);
        $this->audit->record('inbox.thread_assigned', $thread, ['assigned_to' => $from], ['assigned_to' => $userId]);

        return $thread;
    }

    /** Close a thread (guarded: only an active thread; closing a closed thread is a no-op). */
    public function close(MessageThread $thread): MessageThread
    {
        if ($thread->status === 'closed') {
            return $thread;
        }

        return $this->transition($thread, 'closed', 'inbox.thread_closed');
    }

    /** Snooze an active thread until a time (guarded: cannot snooze a closed thread). */
    public function snooze(MessageThread $thread, ?\DateTimeInterface $until = null): MessageThread
    {
        if ($thread->status === 'closed') {
            throw new RuntimeException('Cannot snooze a closed thread — reopen it first.');
        }

        $thread->forceFill(['snoozed_until' => $until])->save();

        return $this->transition($thread, 'snoozed', 'inbox.thread_snoozed');
    }

    /** Reopen a snoozed/closed thread. */
    public function reopen(MessageThread $thread): MessageThread
    {
        if ($thread->status === 'open') {
            return $thread;
        }

        $thread->forceFill(['snoozed_until' => null])->save();

        return $this->transition($thread, 'open', 'inbox.thread_reopened');
    }

    /** Mark a thread read (operator opened it) — clears the unread counter. */
    /**
     * An OWNER's message on an owner↔operator thread (Wave 3) — the owner portal's single write path.
     *
     * ⚠️ IT IS `inbound`, AND THAT IS NOT A QUIRK. `direction` is ORG-RELATIVE: a message from the owner is
     * coming INTO the organization, exactly like a guest's. So the owner portal renders the transcript
     * MIRRORED — their own messages are the `inbound` ones — while the operator inbox reads the same rows
     * unchanged. One column, two correct perspectives; no per-surface duplicate.
     *
     * WHY THIS EXISTS RATHER THAN REUSING {@see postInbound()}, which is the same shape on paper:
     *   1. postInbound() runs `resolveInboundGuest()` → the **GuestMatcher**. On an owner thread there is no
     *      guest, so it would try to match/CREATE one from the participant identity — **a spurious Guest
     *      record for the owner**, polluting the CRM and the guest count.
     *   2. postInbound() is WEBHOOK-shaped: it resolves-or-CREATES the thread from an `external_thread_ref`
     *      and carries the external-id idempotency machinery. An owner posting a form to a thread they are
     *      already reading needs neither, and a find-or-create keyed on a ref is a silent wrong-thread risk.
     * So the owner path is explicit: a known thread, a known author, no matching, no idempotency anchor.
     *
     * It DOES bump `unread_count` — correctly. That counter is the OPERATOR's ("inbound since they last
     * read"), and an owner's message is exactly the thing that should light up their inbox.
     *
     * `$replyToMessageId` (GATE 2 · reply-to) QUOTES an earlier message — validated by the caller to belong to
     * THIS thread, so the quote can never point across threads/owners. Null for a plain message.
     */
    public function postOwnerMessage(MessageThread $thread, string $body, ?User $author = null, ?string $replyToMessageId = null): Message
    {
        $message = Message::create([
            'thread_id' => $thread->id,
            'direction' => 'inbound',              // into the org — see the docblock
            'channel' => $thread->channel,
            'body' => $body,
            'reply_to_message_id' => $replyToMessageId,
            'author_user_id' => $author?->id,
            // The owner's own name, not "Operator" (which sendOutbound() hard-codes for the other side).
            'sender_label' => $author instanceof User ? (string) $author->name : 'Owner',
            'delivery_status' => 'received',       // internal: recording IS the delivery
            'received_at' => now(),
        ]);

        // Atomic — a concurrent operator markRead() must not lose the bump.
        $thread->increment('unread_count');
        $thread->forceFill([
            'last_message_at' => $message->received_at,
            'last_inbound_at' => $message->received_at,
            'status' => $thread->status === 'closed' ? 'open' : $thread->status,
        ])->save();

        $this->domainEvents->record('inbox.message_received', $message, [
            'thread_id' => (string) $thread->id,
            'channel' => $thread->channel,
            'from' => 'owner',
        ]);
        $this->audit->record('inbox.message_received', $message, null, ['direction' => 'inbound', 'from' => 'owner']);

        return $message;
    }

    /**
     * The OWNER's read position on a thread — deliberately NOT {@see markRead()}.
     *
     * ⚠️ `markRead()` zeroes `unread_count`, which is the OPERATOR's counter. An owner-portal read action that
     * called it would silently clear the operator's badge — making the operator's inbox lie about work they
     * have not seen. The owner's read state is its own timestamp; the two never touch.
     */
    public function markReadByOwner(MessageThread $thread): MessageThread
    {
        $thread->forceFill(['owner_last_read_at' => now()])->save();

        return $thread;
    }

    /** The emojis a reaction may be — a fixed allow-list, so no arbitrary payload can reach a rendered surface. */
    public const REACTIONS = ['👍', '❤️', '😂', '🎉', '🙏', '👀'];

    /**
     * Toggle one user's emoji reaction on a message (GATE 2). Idempotent by the `unique(message_id, user_id,
     * emoji)` index — a second tap of the same emoji REMOVES the row (returns false); a first tap adds it
     * (returns true). The emoji is validated against {@see self::REACTIONS} first; anything else is a no-op.
     *
     * A reaction is a lightweight, operator-visible annotation → it records an AUDIT row (who did what) but no
     * domain event: it is not an outbox-worthy business fact, and a domain_events row per emoji tap would be
     * noise. Same pragmatism as read-position (which records neither).
     */
    public function toggleReaction(Message $message, User $user, string $emoji): bool
    {
        if (! in_array($emoji, self::REACTIONS, true)) {
            return false;
        }

        $existing = MessageReaction::query()
            ->where('message_id', $message->getKey())
            ->where('user_id', $user->getKey())
            ->where('emoji', $emoji)
            ->first();

        if ($existing !== null) {
            $existing->delete();
            $this->audit->record('inbox.reaction_removed', $message, ['emoji' => $emoji], null);

            return false;
        }

        MessageReaction::create([
            'message_id' => $message->getKey(),
            'user_id' => $user->getKey(),
            'emoji' => $emoji,
        ]);
        $this->audit->record('inbox.reaction_added', $message, null, ['emoji' => $emoji]);

        return true;
    }

    /**
     * Toggle one user's star (bookmark) on a message (GATE 2). Idempotent by `unique(message_id, user_id)`.
     * A star is PRIVATE view-state (the viewer's own flag) — like a read position, it records no events.
     */
    public function toggleStar(Message $message, User $user): bool
    {
        $existing = MessageStar::query()
            ->where('message_id', $message->getKey())
            ->where('user_id', $user->getKey())
            ->first();

        if ($existing !== null) {
            $existing->delete();

            return false;
        }

        MessageStar::create([
            'message_id' => $message->getKey(),
            'user_id' => $user->getKey(),
        ]);

        return true;
    }

    /**
     * Pin (or unpin) the one message a thread highlights (GATE 2). Passing a message id that already IS the pin
     * clears it (toggle); passing null clears it; passing a new id repins. The caller validates the message
     * belongs to the thread, so a pin can never point across threads. A pin is SHARED thread state (both parties
     * see the banner) → it records both an audit and a domain event.
     */
    public function setPin(MessageThread $thread, ?string $messageId): MessageThread
    {
        $from = $thread->pinned_message_id;
        $to = ($messageId !== null && (string) $from === (string) $messageId) ? null : $messageId;

        if ((string) $from === (string) $to) {
            return $thread;   // no change
        }

        $thread->forceFill(['pinned_message_id' => $to])->save();

        $this->domainEvents->record('inbox.thread_pin_changed', $thread, ['pinned_message_id' => $to]);
        $this->audit->record('inbox.thread_pin_changed', $thread, ['pinned_message_id' => $from], ['pinned_message_id' => $to]);

        return $thread;
    }

    /**
     * A GUEST-authored message from the portal (Phase 4c · SG3) — the mirror of {@see postOwnerMessage()} for the
     * guest side. Posted as `inbound` (into the org), so the operator inbox reads it as a message TO them and the
     * guest's own transcript shows it as theirs. `author_user_id` is null (a guest is not a User) and the label is
     * the guest's NAME only (#28 — no contact PII). It DOES bump `unread_count` — that is the OPERATOR's counter,
     * and a guest message is exactly what should light up their inbox. Not {@see postInbound()}: that runs the
     * GuestMatcher (spurious guest) and is webhook/idempotency-shaped — here the thread + guest are already known.
     */
    public function postGuestMessage(MessageThread $thread, string $body): Message
    {
        $message = Message::create([
            'thread_id' => $thread->id,
            'direction' => 'inbound',              // into the org — the operator sees a message TO them
            'channel' => $thread->channel,
            'body' => $body,
            'author_user_id' => null,              // a guest is not an operator User
            'sender_label' => GuestName::display($thread->guest),   // name only (#28)
            'delivery_status' => 'received',       // internal: recording IS the delivery
            'received_at' => now(),
        ]);

        // Atomic — a concurrent operator markRead() must not lose the bump.
        $thread->increment('unread_count');
        $thread->forceFill([
            'last_message_at' => $message->received_at,
            'last_inbound_at' => $message->received_at,
            'status' => $thread->status === 'closed' ? 'open' : $thread->status,
        ])->save();

        $this->domainEvents->record('inbox.message_received', $message, [
            'thread_id' => (string) $thread->id,
            'channel' => $thread->channel,
            'from' => 'guest',
        ]);
        $this->audit->record('inbox.message_received', $message, null, ['direction' => 'inbound', 'from' => 'guest']);

        return $message;
    }

    /**
     * The GUEST's read position — deliberately NOT {@see markRead()}, exactly as {@see markReadByOwner()}: it
     * touches only `guest_last_read_at`, never `unread_count` (the OPERATOR's badge).
     */
    public function markReadByGuest(MessageThread $thread): MessageThread
    {
        $thread->forceFill(['guest_last_read_at' => now()])->save();

        return $thread;
    }

    public function markRead(MessageThread $thread): MessageThread
    {
        // Two things happen when an operator opens a thread, and they are distinct:
        //   • zero `unread_count` — the OPERATOR's badge;
        //   • advance `operator_last_read_at` — the operator's read POSITION, which drives the owner/guest-facing
        //     "Delivered → Read" receipt (there is no `read` delivery_status to fake). The owner's message is Read
        //     once this timestamp is at/after it.
        // Always set the position (even when the badge is already 0) so a re-open after the operator's own last
        // read still reflects "seen"; the write is one row.
        $thread->forceFill([
            'unread_count' => 0,
            'operator_last_read_at' => now(),
        ])->save();

        return $thread;
    }

    /**
     * Build the inbound message attributes (single source of truth so createOrFirst and create agree).
     *
     * @param  array{body: string, channel: string, external_message_id?: string|null, sender_label?: string|null}  $data
     * @return array<string, mixed>
     */
    private function inboundAttributes(MessageThread $thread, array $data): array
    {
        return [
            'thread_id' => $thread->id,
            'direction' => 'inbound',
            'channel' => $data['channel'],
            'external_message_id' => $data['external_message_id'] ?? null,
            'body' => $data['body'],
            'sender_label' => $data['sender_label'] ?? null,
            'delivery_status' => 'received',
            'received_at' => now(),
        ];
    }

    /**
     * Resolve the thread an inbound message belongs to — messages GROUP, never a thread-per-message:
     *   1. the provider thread ref (email/OTA conversation) when present;
     *   2. else, for an identified participant, a NON-PII hash anchor `p:{hash(channel|identity)}` so a
     *      stranger's messages still thread together (and a returning one rejoins) — race-safe via the
     *      same unique(org, channel, external_thread_ref) index, no PII in the ref;
     *   3. else a brand-new thread (truly anonymous, no identity).
     *
     * @param  array{channel: string, external_thread_ref?: string|null, subject?: string|null, participant_identity?: string|null, guest_id?: string|null, reservation_id?: string|null}  $data
     */
    private function resolveInboundThread(array $data): MessageThread
    {
        $ref = $data['external_thread_ref'] ?? null;
        $identity = $data['participant_identity'] ?? null;

        if ($ref === null && $identity !== null && trim($identity) !== '') {
            $ref = 'p:'.$this->identityHash($data['channel'], $identity);
        }

        $attributes = [
            'subject' => $data['subject'] ?? null,
            'participant_identity' => $identity,
            'guest_id' => $data['guest_id'] ?? null,
            'reservation_id' => $data['reservation_id'] ?? null,
            'status' => 'open',
        ];

        if ($ref !== null) {
            return MessageThread::createOrFirst(
                ['channel' => $data['channel'], 'external_thread_ref' => $ref],
                $attributes,
            );
        }

        return MessageThread::create(['channel' => $data['channel'], 'external_thread_ref' => null] + $attributes);
    }

    /**
     * Resolve/link the inbound guest by channel identity via the SHARED {@see GuestMatcher} (no parallel
     * matcher). Returns null when the caller supplied an explicit guest_id (already linked) or there is no
     * participant identity to match on. The external-ref VALUE is a HASH of the identity — never plaintext
     * PII in `external_refs` — while the real address lands in the guest's ENCRYPTED email/phone.
     *
     * @param  array{channel: string, participant_identity?: string|null, sender_label?: string|null, guest_id?: string|null}  $data
     */
    private function resolveInboundGuest(array $data): ?Guest
    {
        if (isset($data['guest_id'])) {
            return null;   // explicitly linked — matching not needed
        }

        $identity = $data['participant_identity'] ?? null;
        if ($identity === null || trim($identity) === '') {
            return null;   // anonymous (e.g. an internal note) — no guest to match
        }

        $channel = $data['channel'];
        [$first, $last] = $this->splitName($data['sender_label'] ?? null);

        $profile = ['first_name' => $first, 'last_name' => $last];
        if ($channel === 'email') {
            $profile['email'] = $identity;         // encrypted at rest on the guest
        } elseif ($channel === 'whatsapp') {
            $profile['phone'] = $identity;         // encrypted at rest on the guest
        }

        return $this->guests->matchOrCreate("inbox_{$channel}", $this->identityHash($channel, $identity), $profile);
    }

    /**
     * Link (or unlink) a thread to a reservation so the operator sees booking context beside the
     * conversation. The reservation is resolved TENANT-SCOPED — a cross-org id can't be linked.
     */
    public function linkReservation(MessageThread $thread, ?string $reservationId): MessageThread
    {
        if ($reservationId !== null) {
            Reservation::query()->findOrFail($reservationId);   // tenant-scoped → cross-org 404, never linked
        }

        if ((string) $thread->reservation_id === (string) $reservationId) {
            return $thread;
        }

        $from = $thread->reservation_id;
        $thread->update(['reservation_id' => $reservationId]);

        $this->domainEvents->record('inbox.thread_linked_reservation', $thread, ['reservation_id' => $reservationId]);
        $this->audit->record('inbox.thread_linked_reservation', $thread, ['reservation_id' => $from], ['reservation_id' => $reservationId]);

        return $thread;
    }

    /** A stable NON-PII hash of a channel identity, for matching/grouping without storing the address. */
    private function identityHash(string $channel, string $identity): string
    {
        return hash('sha256', $channel.'|'.mb_strtolower(trim($identity)));
    }

    /**
     * Split a display label into first/last name (best effort). Falls back to "Guest" so the required
     * first_name is always set.
     *
     * @return array{0: string, 1: string|null}
     */
    private function splitName(?string $label): array
    {
        $label = trim((string) $label);
        if ($label === '') {
            return ['Guest', null];
        }

        $parts = array_values(array_filter(preg_split('/\s+/', $label) ?: []));
        $first = $parts[0] ?? 'Guest';
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null;

        return [$first, $last];
    }

    private function transition(MessageThread $thread, string $status, string $event): MessageThread
    {
        $from = (string) $thread->status;
        $thread->update(['status' => $status]);

        $this->domainEvents->record($event, $thread, ['status' => $status]);
        $this->audit->record($event, $thread, ['status' => $from], ['status' => $status]);

        return $thread;
    }
}
