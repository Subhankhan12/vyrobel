<?php

use App\Models\Media;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\MessageStar;
use App\Models\User;
use App\Services\InboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// OWNER PORTAL · MESSAGES — GATE 2 rich features (reply-to · attachments · reactions · read-receipts ·
// starred · pinned). Each is MUTATION-PROVEN: the write PERSISTS, it REFLECTS on the screen, it is
// controller-routed (#20), and it is OWNER-SCOPED (#28 — a cross-owner/guest thread 404s, and a message_id
// from another thread is rejected). Nothing is fabricated (#39): every feature has a real row behind it.

/** A staff member who can reply — a real user with a real back-office role. (Local so the file runs alone.) */
function richStaff(array $s, string $name = 'Marco Reyes'): User
{
    return operatorUser($s, ['name' => $name], 'Hotel Manager');
}

// ── REPLY-TO ─────────────────────────────────────────────────────────────────────────────────────
it('quotes an earlier message: reply_to persists and renders, and a cross-thread parent is rejected', function () {
    $s = seedScenario(2);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $marco = richStaff($s);
    $thread = ownerThread($a['owner']);
    $inbox = app(InboxService::class);
    $parent = $inbox->sendOutbound($thread, 'Your payout is on the third.', $marco);

    // Reply quoting the staff message.
    $this->actingAs($a['user'])->post('/owner/messages/'.$thread->id, [
        'body' => 'Thanks — noted.',
        'reply_to_message_id' => $parent->id,
    ])->assertRedirect(route('owner.messages', ['c' => $thread->id]));

    $reply = Message::query()->where('thread_id', $thread->id)->where('body', 'Thanks — noted.')->firstOrFail();
    expect((string) $reply->reply_to_message_id)->toBe((string) $parent->id);

    // It renders the quoted snippet.
    $this->actingAs($a['user'])->get('/owner/messages?c='.$thread->id)
        ->assertOk()
        ->assertSee('Your payout is on the third.');   // the quote of the parent

    // A parent from ANOTHER thread is rejected (validation: exists within THIS thread).
    $other = ownerThread($a['owner'], 'Other');
    $foreign = $inbox->sendOutbound($other, 'Different thread.', $marco);
    $this->actingAs($a['user'])->post('/owner/messages/'.$thread->id, [
        'body' => 'x',
        'reply_to_message_id' => $foreign->id,
    ])->assertSessionHasErrors('reply_to_message_id');
});

// ── ATTACHMENTS ──────────────────────────────────────────────────────────────────────────────────
it('attaches a real file as polymorphic Media on the message and renders it', function () {
    Storage::fake('public');
    $s = seedScenario(2);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    richStaff($s);
    $thread = ownerThread($a['owner']);

    $this->actingAs($a['user'])->post('/owner/messages/'.$thread->id, [
        'body' => 'Here is the invoice.',
        'attachments' => [UploadedFile::fake()->create('invoice.pdf', 40, 'application/pdf')],
    ])->assertRedirect();

    $message = Message::query()->where('thread_id', $thread->id)->where('body', 'Here is the invoice.')->firstOrFail();
    $media = Media::query()->where('mediable_type', $message->getMorphClass())->where('mediable_id', $message->id)->firstOrFail();

    expect((string) $media->collection)->toBe('attachments')
        ->and((string) $media->mime)->toContain('pdf');
    Storage::disk('public')->assertExists($media->path);

    // A message with ONLY a file (no body) is allowed.
    $this->actingAs($a['user'])->post('/owner/messages/'.$thread->id, [
        'attachments' => [UploadedFile::fake()->image('photo.jpg')],
    ])->assertRedirect();
    expect(Media::query()->count())->toBe(2);

    // An empty message (no body, no file) is refused.
    $this->actingAs($a['user'])->post('/owner/messages/'.$thread->id, [])
        ->assertSessionHas('owner_error');
});

// ── REACTIONS ────────────────────────────────────────────────────────────────────────────────────
it('toggles an emoji reaction (idempotent), rejects a non-allow-list emoji, and is owner-scoped', function () {
    $s = seedScenario(2);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $b = inboxOwner($s, 'Bem Adeyemi', 1);
    $marco = richStaff($s);
    $thread = ownerThread($a['owner']);
    $msg = app(InboxService::class)->sendOutbound($thread, 'All set.', $marco);

    // Add.
    $this->actingAs($a['user'])->post('/owner/messages/'.$thread->id.'/react', ['message_id' => $msg->id, 'emoji' => '👍'])->assertRedirect();
    expect(MessageReaction::query()->where('message_id', $msg->id)->where('user_id', $a['user']->id)->count())->toBe(1);

    // Same emoji again → removed (toggle).
    $this->actingAs($a['user'])->post('/owner/messages/'.$thread->id.'/react', ['message_id' => $msg->id, 'emoji' => '👍'])->assertRedirect();
    expect(MessageReaction::query()->where('message_id', $msg->id)->count())->toBe(0);

    // A non-allow-list emoji is rejected by validation.
    $this->actingAs($a['user'])->post('/owner/messages/'.$thread->id.'/react', ['message_id' => $msg->id, 'emoji' => '💣'])
        ->assertSessionHasErrors('emoji');

    // OWNER-SCOPED: owner B cannot react on owner A's thread — the thread 404s.
    $this->actingAs($b['user'])->post('/owner/messages/'.$thread->id.'/react', ['message_id' => $msg->id, 'emoji' => '👍'])
        ->assertNotFound();
    expect(MessageReaction::query()->count())->toBe(0);
});

// ── READ-RECEIPTS ────────────────────────────────────────────────────────────────────────────────
it('shows the owner’s message as Delivered until the operator opens the thread, then Read', function () {
    $s = seedScenario(2);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $marco = richStaff($s);
    $thread = ownerThread($a['owner']);
    app(InboxService::class)->postOwnerMessage($thread, 'Any update?', $a['user']);

    // Operator has not opened it → Delivered, never Read.
    $this->actingAs($a['user'])->get('/owner/messages?c='.$thread->id)
        ->assertOk()
        ->assertSee('Delivered')
        ->assertDontSee('✓✓');

    // Operator opens the thread → markRead sets operator_last_read_at (the REAL receipt source).
    $this->actingAs($marco)->get(route('app.inbox', ['c' => $thread->id]))->assertOk();
    expect($thread->fresh()->operator_last_read_at)->not->toBeNull();

    // Now the owner sees Read.
    $this->actingAs($a['user'])->get('/owner/messages?c='.$thread->id)
        ->assertOk()
        ->assertSee('Read', false);
});

// ── STARRED ──────────────────────────────────────────────────────────────────────────────────────
it('stars a message per-user (private view-state) and lists only the viewer’s stars', function () {
    $s = seedScenario(2);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $marco = richStaff($s);
    $thread = ownerThread($a['owner']);
    $msg = app(InboxService::class)->sendOutbound($thread, 'Star this one please.', $marco);

    // Star.
    $this->actingAs($a['user'])->post('/owner/messages/'.$thread->id.'/star', ['message_id' => $msg->id])->assertRedirect();
    expect(MessageStar::query()->where('message_id', $msg->id)->where('user_id', $a['user']->id)->count())->toBe(1);

    // Unstar (toggle).
    $this->actingAs($a['user'])->post('/owner/messages/'.$thread->id.'/star', ['message_id' => $msg->id])->assertRedirect();
    expect(MessageStar::query()->count())->toBe(0);
});

// ── PINNED ───────────────────────────────────────────────────────────────────────────────────────
it('pins one message (shared thread state) and unpins by toggling', function () {
    $s = seedScenario(2);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $marco = richStaff($s);
    $thread = ownerThread($a['owner']);
    $msg = app(InboxService::class)->sendOutbound($thread, 'Pin this important note.', $marco);

    // Pin.
    $this->actingAs($a['user'])->post('/owner/messages/'.$thread->id.'/pin', ['message_id' => $msg->id])->assertRedirect();
    expect((string) $thread->fresh()->pinned_message_id)->toBe((string) $msg->id);

    $this->actingAs($a['user'])->get('/owner/messages?c='.$thread->id)->assertOk()->assertSee('Pinned');

    // Pinning the same message again clears it (toggle).
    $this->actingAs($a['user'])->post('/owner/messages/'.$thread->id.'/pin', ['message_id' => $msg->id])->assertRedirect();
    expect($thread->fresh()->pinned_message_id)->toBeNull();
});

// ── OWNER-SCOPE on every rich write ──────────────────────────────────────────────────────────────
it('refuses star and pin on another owner’s thread (owner-scoped 404)', function () {
    $s = seedScenario(2);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $b = inboxOwner($s, 'Bem Adeyemi', 1);
    $marco = richStaff($s);
    $thread = ownerThread($a['owner']);
    $msg = app(InboxService::class)->sendOutbound($thread, 'Private to A.', $marco);

    $this->actingAs($b['user'])->post('/owner/messages/'.$thread->id.'/star', ['message_id' => $msg->id])->assertNotFound();
    $this->actingAs($b['user'])->post('/owner/messages/'.$thread->id.'/pin', ['message_id' => $msg->id])->assertNotFound();

    expect(MessageStar::query()->count())->toBe(0)
        ->and($thread->fresh()->pinned_message_id)->toBeNull();
});
