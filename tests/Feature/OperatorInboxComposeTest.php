<?php

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Note;
use App\Notifications\Operator\ThreadAssignedNotification;
use App\Services\InboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #90 GATE 1 — the operator MESSAGES builds on top of the certified inbox: NEW MESSAGE (start a thread with a
// real guest, optionally seeded from a #83 template), ASSIGN → a REAL in-app notification to the assignee
// (honest "notified on their device", never a fake push), and the read-only guard on inbox writes.

// ── New message: start a thread with a real guest, seeded from a template ─────────────────────────
it('starts a thread with a real guest seeded from a template', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    book($s);   // gives $s['guest'] a real reservation → they qualify for the compose picker

    $this->actingAs($manager)
        ->post(route('app.inbox.start'), ['guest_id' => $s['guest']->id, 'template' => 'pre-arrival'])
        ->assertRedirect();

    $thread = MessageThread::query()->where('guest_id', $s['guest']->id)->where('channel', 'manual')->firstOrFail();
    // The template's message was posted as a real outbound (delivered on the manual channel).
    $msg = Message::query()->where('thread_id', $thread->id)->where('direction', 'outbound')->first();
    expect($msg)->not->toBeNull()
        ->and($msg->body)->not->toBe('')
        ->and($msg->delivery_status)->toBe('delivered');
});

// ── GATE 3.2 — the operator's EDITED body is what sends (the in-modal editor), not the raw template ──
it('sends the operator-edited body verbatim, overriding the template', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    book($s);
    $edited = 'Reviewed & edited by the operator before sending — marker Q22Z.';

    $this->actingAs($manager)
        ->post(route('app.inbox.start'), ['guest_id' => $s['guest']->id, 'template' => 'pre-arrival', 'body' => $edited])
        ->assertRedirect();

    $thread = MessageThread::query()->where('guest_id', $s['guest']->id)->where('channel', 'manual')->firstOrFail();
    $msg = Message::query()->where('thread_id', $thread->id)->where('direction', 'outbound')->firstOrFail();
    // The EDITED body sent verbatim — the template was only the starting point, never overriding the operator.
    expect((string) $msg->body)->toBe($edited);
});

// ── The compose picker only offers guests with a current/upcoming/recent stay ─────────────────────
it('lists guests with a current or upcoming stay in the new-message picker', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    book($s);

    $this->actingAs($manager)->get(route('app.inbox'))
        ->assertOk()
        ->assertSee('Guests with a current or upcoming stay');
});

// ── GATE 3.3 — assign carries a REAL internal note (staff-only), and GDPR erase redacts it ───────
it('records an internal note on assign, shows it staff-only, and erases it on anonymize', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    $thread = app(InboxService::class)->postInbound(['channel' => 'manual', 'external_message_id' => 'nt1', 'body' => 'Hi', 'guest_id' => $s['guest']->id])->thread;

    $this->actingAs($manager)
        ->post(route('app.inbox.assign', $thread->id), ['user_id' => '', 'note' => 'Late checkout requested — SECRETNOTE7'])
        ->assertRedirect();

    // A REAL internal Note landed on the thread (reuses the shared polymorphic notes; staff-only visibility).
    $note = Note::query()->where('notable_type', 'message_thread')->where('notable_id', $thread->id)->firstOrFail();
    expect((string) $note->visibility)->toBe('internal')
        ->and((string) $note->body)->toContain('SECRETNOTE7');

    // The operator inbox shows the note (staff surface).
    $this->actingAs($manager)->get(route('app.inbox', ['c' => $thread->id]))->assertOk()->assertSee('SECRETNOTE7');

    // GDPR erase (#28) redacts the note body — the invariant that a free-text store ABOUT a guest is cleared.
    $s['guest']->anonymize();
    expect((string) $note->fresh()->body)->toBe('');
    $this->actingAs($manager)->get(route('app.inbox', ['c' => $thread->id]))->assertOk()->assertDontSee('SECRETNOTE7');
});

// ── GATE 3.3 — the link-reservation picker renders and links the thread via the real route ───────
it('renders the link-reservation picker and links a reservation via the UI route', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    $res = book($s);   // a reservation for $s['guest']
    $thread = app(InboxService::class)->postInbound(['channel' => 'manual', 'external_message_id' => 'lk1', 'body' => 'Hi', 'guest_id' => $s['guest']->id])->thread;

    // The picker offers the guest's reservation (the UI control the audit said was missing).
    $this->actingAs($manager)->get(route('app.inbox', ['c' => $thread->id]))->assertOk()
        ->assertSee('Link a reservation')
        ->assertSee((string) $res->reference);

    // Linking through the route attaches it (tenant-scoped, certified InboxService path).
    $this->actingAs($manager)->post(route('app.inbox.link-reservation', $thread->id), ['reservation_id' => $res->id])->assertRedirect();
    expect((string) $thread->fresh()->reservation_id)->toBe((string) $res->id);
});

// ── GATE 3.3 — Retry re-sends a FAILED outbound for real (never a fake flip-to-delivered) ────────
it('retries a failed outbound with a real re-send', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    $thread = app(InboxService::class)->postInbound(['channel' => 'manual', 'external_message_id' => 're1', 'body' => 'Hi', 'guest_id' => $s['guest']->id])->thread;
    $failed = Message::create(['organization_id' => $s['org']->id, 'thread_id' => $thread->id, 'direction' => 'outbound', 'channel' => 'manual', 'body' => 'Please retry me', 'delivery_status' => 'failed', 'sender_label' => 'Operator']);

    $this->actingAs($manager)->post(route('app.inbox.retry', [$thread->id, $failed->id]))
        ->assertRedirect()->assertSessionHas('inbox_notice');

    // A REAL new outbound (same body) was sent and DELIVERED on the manual channel — an actual attempt.
    expect(Message::query()->where('thread_id', $thread->id)->where('direction', 'outbound')->where('body', 'Please retry me')->where('delivery_status', 'delivered')->exists())->toBeTrue();
});

// ── Assign → a REAL in-app notification lands for the assignee (honest "notified on their device") ─
it('notifies the assignee in-app when a conversation is assigned', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    $colleague = operatorUser($s, ['name' => 'Assignee Person']);
    $thread = app(InboxService::class)->postInbound(['channel' => 'manual', 'external_message_id' => 'a1', 'body' => 'Hi', 'guest_id' => $s['guest']->id])->thread;

    $this->actingAs($manager)->post(route('app.inbox.assign', $thread->id), ['user_id' => $colleague->id])->assertRedirect();

    // A real database (in-app) notification landed for the assignee — never an email/push (in-app only).
    $note = $colleague->fresh()->notifications()->where('type', ThreadAssignedNotification::class)->first();
    expect($note)->not->toBeNull()
        ->and($note->data['title'])->toBe('Conversation assigned to you');
});

// ── Assign is IN-APP only — never a mail channel (no fake push/email) ─────────────────────────────
it('sends the assignment notification on the database channel only', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    $colleague = operatorUser($s, ['name' => 'Mail Check']);
    $thread = app(InboxService::class)->postInbound(['channel' => 'manual', 'external_message_id' => 'a2', 'body' => 'Hi', 'guest_id' => $s['guest']->id])->thread;

    $note = new ThreadAssignedNotification($thread, 'A Guest');
    expect($note->via($colleague))->toBe(['database']);
});

// ── Read-only refuses inbox writes (reply / assign / start) ───────────────────────────────────────
it('refuses inbox writes while the workspace is paused', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    $s['org']->forceFill(['paused_until' => now()->addMonth()])->save();
    $thread = app(InboxService::class)->postInbound(['channel' => 'manual', 'external_message_id' => 'ro1', 'body' => 'Hi', 'guest_id' => $s['guest']->id])->thread;

    $this->actingAs($manager)->post(route('app.inbox.reply', $thread->id), ['body' => 'Nope'])->assertRedirect();
    expect(Message::query()->where('thread_id', $thread->id)->where('body', 'Nope')->exists())->toBeFalse();

    $this->actingAs($manager)->post(route('app.inbox.start'), ['guest_id' => $s['guest']->id, 'template' => 'pre-arrival'])
        ->assertRedirect()->assertSessionHas('inbox_error');
});
