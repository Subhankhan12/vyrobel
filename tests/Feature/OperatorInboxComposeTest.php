<?php

use App\Models\Message;
use App\Models\MessageThread;
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

// ── The compose picker only offers guests with a current/upcoming/recent stay ─────────────────────
it('lists guests with a current or upcoming stay in the new-message picker', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    book($s);

    $this->actingAs($manager)->get(route('app.inbox'))
        ->assertOk()
        ->assertSee('Guests with a current or upcoming stay');
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
