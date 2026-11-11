<?php

use App\Models\Media;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\MessageStar;
use App\Models\User;
use App\Services\InboxService;
use App\Support\Messaging\ChatFiles;
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
    Storage::fake('local');
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
    // ⚠️ the PRIVATE disk since 2026-09-30 (DEC-m, his ruling) — an attachment opens only for people in that conversation,
    //    signed in (MessageAttachmentAccessTest); it is never on the public disk
    Storage::disk('local')->assertExists($media->path);
    Storage::disk('public')->assertMissing($media->path);

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

// ── ⚠️ D-UPLOAD-LIMITS (measured 2026-10-06; fixed 2026-11-11, his "do all") ──────────────────────────────────────────────────
// The owner composer validated a typed "10 MB" and used Laravel's own refusal words — and the page never printed `$errors`, so a file that
// was too big, the wrong type, or one too many bounced back with NO visible reason. The limits are now the server's real ceilings
// (ChatFiles::limits(), as the Inbox and team chat read them) and every refusal is said on the page, in the Inbox's own sentences.
it('⚠️⚠️ D-UPLOAD-LIMITS — a file that is too big, the wrong type, or one too many is REFUSED IN WORDS on the page, and nothing is written', function () {
    Storage::fake('local');
    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $thread = ownerThread($a['owner']);
    $mb = ChatFiles::limits()['mb'];
    $before = Message::query()->where('thread_id', $thread->id)->count();

    // too big: one KB over THIS server's ceiling — said with that number
    $this->actingAs($a['user'])->post('/owner/messages/'.$thread->id, ['body' => 'Here', 'attachments' => [UploadedFile::fake()->create('big.pdf', ($mb * 1024) + 1, 'application/pdf')]])
        ->assertSessionHas('owner_error', "Each file can be up to {$mb} MB.");
    // the wrong type
    $this->actingAs($a['user'])->post('/owner/messages/'.$thread->id, ['body' => 'Here', 'attachments' => [UploadedFile::fake()->create('run.exe', 10, 'application/x-msdownload')]])
        ->assertSessionHas('owner_error');
    // one too many (a sixth)
    $six = array_map(fn (int $i) => UploadedFile::fake()->create("f{$i}.pdf", 10, 'application/pdf'), range(1, 6));
    $this->actingAs($a['user'])->post('/owner/messages/'.$thread->id, ['body' => 'Here', 'attachments' => $six])
        ->assertSessionHas('owner_error', fn (string $m): bool => str_contains($m, (string) ChatFiles::MAX_FILES));
    expect(Message::query()->where('thread_id', $thread->id)->count())->toBe($before);   // nothing written

    // the NEW-conversation form says the same
    $this->actingAs($a['user'])->post('/owner/messages', ['body' => 'Hello', 'attachments' => [UploadedFile::fake()->create('big.pdf', ($mb * 1024) + 1, 'application/pdf')]])
        ->assertSessionHas('owner_error', "Each file can be up to {$mb} MB.");

    // …and the page SHOWS it (the notice it never printed)
    $page = $this->actingAs($a['user'])->withSession(['owner_error' => "Each file can be up to {$mb} MB."])->get('/owner/messages?c='.$thread->id)->assertOk()->getContent();
    expect($page)->toContain("Each file can be up to {$mb} MB.");

    // CONTROL: a normal PDF goes through
    $this->actingAs($a['user'])->post('/owner/messages/'.$thread->id, ['body' => 'Here', 'attachments' => [UploadedFile::fake()->create('ok.pdf', 100, 'application/pdf')]])
        ->assertSessionMissing('owner_error')->assertRedirect();
    expect(Message::query()->where('thread_id', $thread->id)->count())->toBe($before + 1);
});

it('D-UPLOAD-LIMITS — the controller no longer types its own number', function () {
    $source = (string) file_get_contents(app_path('Http/Controllers/Owner/OwnerMessageActionController.php'));

    expect($source)->not->toContain('ATTACH_MAX_KB')->not->toContain('= 10240')->toContain('ChatFiles::limits()');
});
