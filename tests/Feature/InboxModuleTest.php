<?php

use App\Models\Membership;
use App\Models\Message;
use App\Models\User;
use App\Services\InboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Unified Inbox · Step 3 — full module integrity suite tying the pieces together through the REAL HTTP
// surface: the unstyled operator stub renders real tenant-scoped threads/messages (cross-org → 404); the
// reply box sends a REAL outbound via InboxService (manual channel); a deferred-channel reply surfaces an
// honest failed state (not faked); the guest NAME is shown but the encrypted participant identity is not.

function inboxMember(array $scenario, string $name = 'Operator Case'): User
{
    $user = User::factory()->create(['name' => $name, 'status' => 'active']);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return $user;
}

function svc(): InboxService
{
    return app(InboxService::class);
}

// ── 1. The thread LIST renders real threads, tenant-scoped ───────────────────────────────────────
it('renders the inbox list with real threads, tenant-scoped', function () {
    $s = seedScenario(1);
    svc()->postInbound(['channel' => 'manual', 'external_message_id' => 'l1', 'body' => 'My unit A question', 'participant_identity' => 'dana@guest.test', 'sender_label' => 'Dana Guest']);

    // Another org's thread must NOT appear.
    $other = seedScenario(1);
    svc()->postInbound(['channel' => 'manual', 'external_message_id' => 'x1', 'body' => 'FOREIGN thread body', 'participant_identity' => 'foreign@guest.test', 'sender_label' => 'Foreign Guest']);

    $this->actingAs(inboxMember($s))->get('/app/inbox')
        ->assertOk()
        ->assertSee('Dana Guest')            // guest NAME
        ->assertSee('My unit A question')    // last-message preview
        ->assertDontSee('Foreign Guest')     // tenant isolation
        ->assertDontSee('FOREIGN thread body');
});

// ── 2. The thread VIEW renders messages + guest name; encrypted identity is NOT rendered ─────────
it('renders a thread with the guest name but never the encrypted participant identity', function () {
    $s = seedScenario(1);
    $in = svc()->postInbound(['channel' => 'manual', 'external_message_id' => 'v1', 'body' => 'Can I get a late checkout?', 'participant_identity' => 'private-address@guest.test', 'sender_label' => 'Priya Traveller']);

    $this->actingAs(inboxMember($s))->get("/app/inbox/{$in->thread_id}")
        ->assertOk()
        ->assertSee('Priya Traveller')             // guest NAME shown
        ->assertSee('Can I get a late checkout?')  // the conversation
        ->assertDontSee('private-address@guest.test');   // encrypted participant identity NEVER rendered
});

// ── 3. A cross-org thread 404s (RMB rule) ────────────────────────────────────────────────────────
it('404s a cross-org thread', function () {
    $other = seedScenario(1);
    $foreign = svc()->postInbound(['channel' => 'manual', 'external_message_id' => 'f1', 'body' => 'Foreign', 'participant_identity' => 'f@x.test']);

    $s = seedScenario(1);
    $member = inboxMember($s);

    $this->actingAs($member)->get("/app/inbox/{$foreign->thread_id}")->assertNotFound();
    $this->actingAs($member)->post(route('app.inbox.reply', $foreign->thread_id), ['body' => 'hi'])->assertNotFound();
});

// ── 4. The reply box sends a REAL outbound via the manual channel ────────────────────────────────
it('sends a real outbound reply through the manual channel', function () {
    $s = seedScenario(1);
    $in = svc()->postInbound(['channel' => 'manual', 'external_message_id' => 'r1', 'body' => 'Question?', 'participant_identity' => 'g@guest.test', 'sender_label' => 'Gwen Guest']);
    $member = inboxMember($s, 'Reply Operator');

    $this->actingAs($member)
        ->post(route('app.inbox.reply', $in->thread_id), ['body' => 'Yes, of course!'])
        ->assertRedirect(route('app.inbox.show', $in->thread_id))
        ->assertSessionHas('inbox_notice');

    $this->assertDatabaseHas('messages', [
        'thread_id' => $in->thread_id,
        'direction' => 'outbound',
        'body' => 'Yes, of course!',
        'delivery_status' => 'delivered',
        'author_user_id' => $member->id,
    ]);

    // Replying cleared the unread counter (operator read the thread).
    $this->assertDatabaseHas('message_threads', ['id' => $in->thread_id, 'unread_count' => 0]);
});

// ── 5. A reply on a DEFERRED channel → honest failed record + flash error (never faked) ──────────
it('records an honest failed reply on a deferred channel, not a fake send', function () {
    $s = seedScenario(1);
    $thread = svc()->startThread(['channel' => 'email', 'participant_identity' => 'g@guest.test']);
    $member = inboxMember($s);

    $this->actingAs($member)
        ->post(route('app.inbox.reply', $thread->id), ['body' => 'Trying to reply'])
        ->assertRedirect(route('app.inbox.show', $thread->id))
        ->assertSessionHas('inbox_error');

    $msg = Message::query()->where('thread_id', $thread->id)->where('direction', 'outbound')->first();
    expect($msg)->not->toBeNull()
        ->and($msg->delivery_status)->toBe('failed')          // recorded failed, not delivered
        ->and($msg->delivery_error)->toContain('not configured');
});

// ── 6. End-to-end: inbound → guest matched → visible in the stub → operator replies → delivered ──
it('ties the module together: inbound linked to a guest, then a real reply, over the HTTP surface', function () {
    $s = seedScenario(1);
    $member = inboxMember($s, 'Frontdesk Operator');

    // Inbound from a guest's address → matched/created + linked (reused matcher), threaded.
    $in = svc()->postInbound(['channel' => 'manual', 'external_thread_ref' => 'conv-1', 'external_message_id' => 'e2e-1', 'body' => 'Is breakfast included?', 'participant_identity' => 'e2e@guest.test', 'sender_label' => 'Evan Endto']);
    expect($in->thread->guest_id)->not->toBeNull();

    // A second inbound on the same anchor JOINS the same thread (grouping, no duplicate).
    $in2 = svc()->postInbound(['channel' => 'manual', 'external_thread_ref' => 'conv-1', 'external_message_id' => 'e2e-2', 'body' => 'And parking?', 'participant_identity' => 'e2e@guest.test']);
    expect($in2->thread_id)->toBe($in->thread_id);

    // The operator sees the thread, opens it, and replies for real.
    $this->actingAs($member)->get('/app/inbox')->assertOk()->assertSee('Evan Endto');
    $this->actingAs($member)->get("/app/inbox/{$in->thread_id}")->assertOk()->assertSee('Is breakfast included?')->assertSee('And parking?');
    $this->actingAs($member)->post(route('app.inbox.reply', $in->thread_id), ['body' => 'Breakfast is included; parking is €10/night.'])
        ->assertRedirect();

    $this->assertDatabaseHas('messages', ['thread_id' => $in->thread_id, 'direction' => 'outbound', 'delivery_status' => 'delivered']);
    // Three messages total (2 inbound + 1 outbound) in ONE thread.
    expect(Message::query()->where('thread_id', $in->thread_id)->count())->toBe(3);
});

// ── 7. Morph map — the models are registered ─────────────────────────────────────────────────────
it('registers the inbox models in the morph map', function () {
    $s = seedScenario(1);
    $in = svc()->postInbound(['channel' => 'manual', 'external_message_id' => 'm1', 'body' => 'Hi', 'participant_identity' => 'g@x.test']);

    expect($in->getMorphClass())->toBe('message')
        ->and($in->thread->getMorphClass())->toBe('message_thread');
});
