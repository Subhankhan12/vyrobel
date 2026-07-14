<?php

use App\Models\Guest;
use App\Models\Message;
use App\Models\MessageThread;
use App\Services\InboxService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Unified Inbox · Step 2 — reservation/guest linking + threading + the GDPR redaction hook. Guest
// matching REUSES the shared GuestMatcher (no parallel matcher); threading groups by the anchor /
// participant hash (never a thread-per-message); anonymize() scrubs the guest's conversation content.

function inboxSvc(): InboxService
{
    return app(InboxService::class);
}

// ── 1. Inbound from a KNOWN guest's address links to the existing guest (no duplicate) ───────────
it('links an inbound message to an existing guest by channel identity, without duplicating', function () {
    newDemoOrg();

    $first = inboxSvc()->postInbound(['channel' => 'email', 'body' => 'Hi', 'external_message_id' => 'e1', 'participant_identity' => 'jo@guest.test', 'sender_label' => 'Jo Traveller']);
    $guest = $first->thread->guest;
    expect($guest)->not->toBeNull()->and($guest->first_name)->toBe('Jo');

    // A second message from the SAME address resolves to the SAME guest (reused matcher, no duplicate).
    $second = inboxSvc()->postInbound(['channel' => 'email', 'body' => 'Following up', 'external_message_id' => 'e2', 'participant_identity' => 'jo@guest.test']);

    expect($second->thread->guest_id)->toBe($guest->id)
        ->and(Guest::query()->count())->toBe(1);   // exactly one guest — matched, not re-created
});

// ── 2. Inbound from an UNKNOWN address creates + links a guest and still threads ──────────────────
it('creates and links a guest for an unknown address and threads the message', function () {
    newDemoOrg();

    $msg = inboxSvc()->postInbound(['channel' => 'whatsapp', 'body' => 'First contact', 'external_message_id' => 'w1', 'participant_identity' => '+441234567', 'sender_label' => 'Sam Newcomer']);

    expect($msg->thread->guest_id)->not->toBeNull()
        ->and($msg->thread->guest->first_name)->toBe('Sam')
        ->and($msg->thread->guest->last_name)->toBe('Newcomer')
        ->and(Guest::query()->count())->toBe(1);
});

// ── 3. Messages GROUP by the provider thread ref — no duplicate-thread-per-message ───────────────
it('groups messages with the same external_thread_ref into one thread', function () {
    newDemoOrg();

    $a = inboxSvc()->postInbound(['channel' => 'email', 'external_thread_ref' => 'thr-1', 'external_message_id' => 'm1', 'body' => 'One', 'participant_identity' => 'p@x.test']);
    $b = inboxSvc()->postInbound(['channel' => 'email', 'external_thread_ref' => 'thr-1', 'external_message_id' => 'm2', 'body' => 'Two', 'participant_identity' => 'p@x.test']);

    expect($b->thread_id)->toBe($a->thread_id)
        ->and(MessageThread::query()->count())->toBe(1)
        ->and($a->thread->refresh()->messages()->count())->toBe(2)
        ->and($a->thread->unread_count)->toBe(2);
});

// ── 4. An UNLINKED enquiry (no thread ref) still threads on the participant identity ──────────────
it('threads a stranger by participant identity when there is no provider thread ref', function () {
    newDemoOrg();

    $a = inboxSvc()->postInbound(['channel' => 'whatsapp', 'external_message_id' => 's1', 'body' => 'One', 'participant_identity' => '+555000']);
    $b = inboxSvc()->postInbound(['channel' => 'whatsapp', 'external_message_id' => 's2', 'body' => 'Two', 'participant_identity' => '+555000']);

    expect($b->thread_id)->toBe($a->thread_id)          // grouped by the non-PII participant hash
        ->and(MessageThread::query()->count())->toBe(1);
});

// ── 5. A thread links to a reservation (booking context beside the conversation) ─────────────────
it('links a thread to a reservation', function () {
    $s = seedScenario(1);
    $reservation = book($s);
    $thread = inboxSvc()->startThread(['channel' => 'manual', 'guest_id' => $s['guest']->id]);

    inboxSvc()->linkReservation($thread, $reservation->id);

    expect($thread->refresh()->reservation_id)->toBe($reservation->id)
        ->and($thread->reservation->reference)->not->toBeNull();
});

// ── 6. A reply threads correctly and sends via the thread's own channel ──────────────────────────
it('threads a reply into the same thread via the thread channel', function () {
    newDemoOrg();
    $in = inboxSvc()->postInbound(['channel' => 'manual', 'external_thread_ref' => 't9', 'external_message_id' => 'i9', 'body' => 'Question?', 'participant_identity' => 'g@x.test']);

    $reply = inboxSvc()->sendOutbound($in->thread, 'Here is the answer.');

    expect($reply->thread_id)->toBe($in->thread_id)     // same thread
        ->and($reply->channel)->toBe('manual')          // via the thread's channel
        ->and($reply->direction)->toBe('outbound')
        ->and($reply->delivery_status)->toBe('delivered');
});

// ── 7. GDPR — anonymize() redacts the guest's message bodies + participant identity ──────────────
it('redacts the guest inbox conversation on anonymize', function () {
    newDemoOrg();
    $in = inboxSvc()->postInbound(['channel' => 'manual', 'external_message_id' => 'g1', 'body' => 'My passport number is 12345', 'participant_identity' => 'secret@guest.test', 'sender_label' => 'Secret Guest']);
    $thread = $in->thread;
    $reply = inboxSvc()->sendOutbound($thread, 'Thanks, noted.');
    $guest = $thread->guest;

    $guest->anonymize();

    expect($in->refresh()->body)->toBe('')                         // guest message body scrubbed
        ->and($in->sender_label)->toBeNull()
        ->and($reply->refresh()->body)->toBe('')                   // whole-thread content scrubbed
        ->and($thread->refresh()->participant_identity)->toBeNull() // encrypted routing address scrubbed
        ->and($thread->subject)->toBeNull()
        ->and($guest->refresh()->email)->toBeNull()                // guest PII gone (existing discipline)
        ->and($guest->anonymized_at)->not->toBeNull();

    // The skeleton survives for audit — the rows are NOT soft-deleted-with-PII.
    expect(Message::query()->where('thread_id', $thread->id)->count())->toBe(2);
});

// ── 8. Tenant isolation — the matcher + threads never cross orgs ─────────────────────────────────
it('isolates guest matching and threads across orgs', function () {
    newDemoOrg();
    $inA = inboxSvc()->postInbound(['channel' => 'email', 'external_message_id' => 'a1', 'body' => 'From A', 'participant_identity' => 'shared@x.test']);
    $guestA = $inA->thread->guest;

    newDemoOrg();   // switch to org B
    // Same address, different org → a NEW guest in B (the matcher is tenant-scoped), never A's guest.
    $inB = inboxSvc()->postInbound(['channel' => 'email', 'external_message_id' => 'b1', 'body' => 'From B', 'participant_identity' => 'shared@x.test']);

    expect($inB->thread->guest_id)->not->toBe($guestA->id)
        ->and(MessageThread::query()->find($inA->thread->id))->toBeNull();   // B never sees A's thread
});

// ── 9. linkReservation is tenant-scoped — a cross-org reservation can't be linked ────────────────
it('rejects linking a cross-org reservation', function () {
    $s = seedScenario(1);
    $foreignReservation = book($s);

    newDemoOrg();   // switch to another org
    $thread = inboxSvc()->startThread(['channel' => 'manual']);

    expect(fn () => inboxSvc()->linkReservation($thread, $foreignReservation->id))
        ->toThrow(ModelNotFoundException::class);
    expect($thread->refresh()->reservation_id)->toBeNull();
});
