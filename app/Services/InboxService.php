<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\Sync\ProcessInboundMessageJob;
use App\Messaging\Contracts\MessageChannel;
use App\Messaging\MessageChannelResolver;
use App\Models\Guest;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Reservation;
use App\Models\SyncJob;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
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
     * @param  array{channel: string, external_thread_ref?: string|null, subject?: string|null, guest_id?: string|null, reservation_id?: string|null, participant_identity?: string|null, assigned_to?: string|null, meta?: array<string, mixed>|null}  $data
     */
    public function startThread(array $data): MessageThread
    {
        $thread = MessageThread::create([
            'channel' => $data['channel'],
            'external_thread_ref' => $data['external_thread_ref'] ?? null,
            'subject' => $data['subject'] ?? null,
            'guest_id' => $data['guest_id'] ?? null,
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
    public function markRead(MessageThread $thread): MessageThread
    {
        if ($thread->unread_count === 0) {
            return $thread;
        }

        $thread->update(['unread_count' => 0]);

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
