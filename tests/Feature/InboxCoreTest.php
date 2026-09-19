<?php

use App\Jobs\Sync\ProcessInboundMessageJob;
use App\Messaging\MessageChannelResolver;
use App\Models\Membership;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use App\Services\InboxService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

// Unified Inbox · Step 1 — the internal core + the interface+fake+real channel seam. Threads/messages
// through InboxService (tenant-scoped, morph-mapped); INBOUND idempotency via the message unique index;
// outbound via the real ManualChannel; the deferred email/whatsapp/OTA adapters throw "not configured"
// (asserted, not faked); participant_identity encrypted; guarded thread transitions; inbound on the backbone.

function inbox(): InboxService
{
    return app(InboxService::class);
}

// ── 1. startThread — tenant-scoped + morph-mapped ────────────────────────────────────────────────
it('starts a tenant-scoped, morph-mapped thread', function () {
    $org = newDemoOrg();
    $thread = inbox()->startThread(['channel' => 'manual', 'subject' => 'Pre-arrival question']);

    expect($thread->organization_id)->toBe($org->id)
        ->and($thread->getMorphClass())->toBe('message_thread')
        ->and($thread->status)->toBe('open')
        ->and($thread->channel)->toBe('manual');
});

// ── 2. postInbound — creates an inbound message + bumps the thread ───────────────────────────────
it('posts an inbound message and bumps the thread', function () {
    newDemoOrg();
    $msg = inbox()->postInbound([
        'channel' => 'manual', 'body' => 'Is early check-in possible?',
        'external_thread_ref' => 't-1', 'external_message_id' => 'm-1',
        'participant_identity' => 'guest@example.test', 'sender_label' => 'Dana Guest',
    ]);

    expect($msg->direction)->toBe('inbound')
        ->and($msg->getMorphClass())->toBe('message')
        ->and($msg->delivery_status)->toBe('received');

    $thread = $msg->thread;
    expect($thread->unread_count)->toBe(1)
        ->and($thread->last_inbound_at)->not->toBeNull()
        ->and($thread->channel)->toBe('manual');
});

// ── 3. INBOUND IDEMPOTENCY — a duplicate external_message_id collapses to one message ─────────────
it('is idempotent on a duplicate inbound external_message_id (single message, no double-bump)', function () {
    newDemoOrg();
    $a = inbox()->postInbound(['channel' => 'manual', 'body' => 'Hello', 'external_thread_ref' => 't-2', 'external_message_id' => 'dup-1']);
    $b = inbox()->postInbound(['channel' => 'manual', 'body' => 'Hello (redelivered)', 'external_thread_ref' => 't-2', 'external_message_id' => 'dup-1']);

    expect($b->id)->toBe($a->id)                                   // same row returned
        ->and(Message::query()->where('external_message_id', 'dup-1')->count())->toBe(1)
        ->and($a->thread->refresh()->unread_count)->toBe(1);        // not double-counted
});

// ── 4. A message with NO external id is always distinct (a manual note isn't deduped) ─────────────
it('does not dedupe inbound messages that carry no external id', function () {
    newDemoOrg();
    $thread = inbox()->startThread(['channel' => 'manual']);
    inbox()->postInbound(['channel' => 'manual', 'external_thread_ref' => $thread->external_thread_ref, 'body' => 'Call note 1']);
    inbox()->postInbound(['channel' => 'manual', 'body' => 'Call note 2']);

    expect(Message::query()->where('direction', 'inbound')->count())->toBe(2);
});

// ── 5. Outbound via the REAL ManualChannel — recorded delivered with an external ref ─────────────
it('sends an outbound reply through the real ManualChannel', function () {
    newDemoOrg();
    $thread = inbox()->startThread(['channel' => 'manual', 'participant_identity' => 'guest@example.test']);
    $user = User::factory()->create(['name' => 'Aria Ops']);

    $out = inbox()->sendOutbound($thread, 'Yes — early check-in is fine.', $user);

    expect($out->direction)->toBe('outbound')
        ->and($out->delivery_status)->toBe('delivered')
        ->and($out->external_message_id)->toStartWith('manual:')
        ->and($out->author_user_id)->toBe($user->id)
        ->and($out->sender_label)->toBe('Aria Ops');
});

// ── 6. The DEFERRED real adapters throw "not configured — <need>" (asserted, not faked) ───────────
it('throws not-configured on every deferred channel adapter', function () {
    $resolver = app(MessageChannelResolver::class);

    $needs = [
        'email' => 'inbound-email provider',
        'whatsapp' => 'WhatsApp Business API',
        'booking_com' => 'OTA messaging API',
        'airbnb' => 'OTA messaging API',
    ];

    foreach ($needs as $channel => $need) {
        expect(fn () => $resolver->resolve($channel)->send(new Message, null))
            ->toThrow(RuntimeException::class, 'not configured');
        expect(fn () => $resolver->resolve($channel)->normalizeInbound([]))
            ->toThrow(RuntimeException::class, $need);
    }

    // The real channel does NOT throw.
    expect($resolver->resolve('manual')->name())->toBe('manual');
});

// ── 7. Sending on a deferred-channel thread throws AND records a failed outbound ──────────────────
it('records a failed outbound when the channel is not configured, then re-throws', function () {
    newDemoOrg();
    $thread = inbox()->startThread(['channel' => 'email', 'participant_identity' => 'guest@example.test']);

    expect(fn () => inbox()->sendOutbound($thread, 'Reply attempt'))
        ->toThrow(RuntimeException::class, 'not configured');

    $msg = Message::query()->where('thread_id', $thread->id)->where('direction', 'outbound')->first();
    expect($msg)->not->toBeNull()
        ->and($msg->delivery_status)->toBe('failed')
        ->and($msg->delivery_error)->toContain('not configured');
});

// ── 8. participant_identity is ENCRYPTED at rest, decrypts via the cast ──────────────────────────
it('stores participant_identity encrypted at rest', function () {
    newDemoOrg();
    $thread = inbox()->startThread(['channel' => 'manual', 'participant_identity' => 'secret@guest.test']);

    $raw = DB::table('message_threads')->where('id', $thread->id)->value('participant_identity');
    expect($raw)->not->toBeNull()
        ->and($raw)->not->toContain('secret@guest.test')   // ciphertext at rest
        ->and($thread->fresh()->participant_identity)->toBe('secret@guest.test');   // decrypts via cast
});

// ── 9. Guarded thread transitions ────────────────────────────────────────────────────────────────
it('guards thread lifecycle transitions', function () {
    newDemoOrg();
    $thread = inbox()->startThread(['channel' => 'manual']);

    inbox()->close($thread);
    expect($thread->refresh()->status)->toBe('closed');

    // Cannot snooze a closed thread — reopen first (guarded).
    expect(fn () => inbox()->snooze($thread))->toThrow(RuntimeException::class, 'closed');

    inbox()->reopen($thread);
    expect($thread->refresh()->status)->toBe('open');

    $u = User::factory()->create();
    inbox()->assign($thread, $u->id);
    expect($thread->refresh()->assigned_to)->toBe($u->id);
});

// ── 10. A reply reopens a closed thread ──────────────────────────────────────────────────────────
it('reopens a closed thread when a reply is sent', function () {
    newDemoOrg();
    $thread = inbox()->startThread(['channel' => 'manual']);
    inbox()->close($thread);

    inbox()->sendOutbound($thread, 'Following up.');
    expect($thread->refresh()->status)->toBe('open');
});

// ── 11. Tenant isolation — org B never sees org A's thread ───────────────────────────────────────
it('isolates threads by tenant', function () {
    newDemoOrg();
    $threadA = inbox()->startThread(['channel' => 'manual', 'subject' => 'Org A thread']);

    newDemoOrg();   // switches tenant context to org B
    expect(MessageThread::query()->find($threadA->id))->toBeNull();
});

// ── 12. Inbound rides the reliability backbone — idempotent at the job level too ──────────────────
it('processes inbound through the backbone job, idempotently', function () {
    Queue::fake();
    $org = newDemoOrg();

    $payload = ['body' => 'From the backbone', 'external_thread_ref' => 'bb-1', 'external_message_id' => 'bb-msg-1', 'participant_identity' => 'g@x.test'];
    $j1 = inbox()->receiveInbound('manual', $payload);
    $j2 = inbox()->receiveInbound('manual', $payload);   // duplicate delivery, same external id

    // One sync_jobs row (idempotency key collapses the duplicate).
    expect($j2->id)->toBe($j1->id)->and($j1->type)->toBe('inbox_inbound');
    Queue::assertPushed(ProcessInboundMessageJob::class, 1);

    // Run the job (the backbone) — one message. Re-running the SAME job is a no-op (succeeded → skip).
    (new ProcessInboundMessageJob($org->id, $j1->id))->handle(app(TenantContext::class));
    (new ProcessInboundMessageJob($org->id, $j1->id))->handle(app(TenantContext::class));

    expect(Message::query()->where('external_message_id', 'bb-msg-1')->count())->toBe(1);
});

/**
 * ⚠️⚠️ RULED BY THE FOUNDER, 2026-09-19 — R-SUPPORT: THREADS STAY ORGANISATION-WIDE.
 *
 * ⚠️ `message_threads.assigned_to` exists and is a HINT — who is looking after this conversation. It is
 * deliberately NOT an access filter: any back-office colleague in the organisation can open any thread, so a
 * small operator is never locked out of their own inbox by an assignment.
 *
 * ⚠️⚠️ A FUTURE PASS WILL SEE AN `assigned_to` COLUMN THAT NOTHING FILTERS ON AND "TIGHTEN" IT. That would
 * silently remove cover for every operator with fewer people than shifts. Tenancy is the boundary here
 * (#28 still applies); assignment is not.
 *
 * ⚠️ THE MUTATION: add an `assigned_to` condition to the thread lookup → the non-assignee below stops seeing
 * the thread → RED.
 */
it('⚠️ RULED 2026-09-19 (R-SUPPORT) — assigned_to is a HINT: a colleague who is not the assignee still sees the thread', function () {
    $org = newDemoOrg();
    $assignee = User::factory()->create(['name' => 'Ada Assignee']);
    Membership::create(['organization_id' => $org->id, 'user_id' => $assignee->id, 'status' => 'active']);

    $thread = inbox()->startThread(['channel' => 'manual', 'subject' => 'Late arrival']);
    $thread->forceFill(['assigned_to' => $assignee->getKey()])->save();

    // ⚠️ A DIFFERENT colleague in the SAME organisation — the actor the ruling is about.
    $colleague = User::factory()->create(['name' => 'Cal Colleague']);
    Membership::create(['organization_id' => $org->id, 'user_id' => $colleague->id, 'status' => 'active']);

    $seen = MessageThread::query()->find($thread->getKey());

    expect($seen)->not->toBeNull('R-SUPPORT: a thread is org-wide, not assignee-only');
    expect((string) $seen->assigned_to)
        ->toBe((string) $assignee->getKey(), 'the assignment is still RECORDED — it is a hint, not a gate');
});
