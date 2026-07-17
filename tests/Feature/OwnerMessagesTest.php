<?php

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use App\Services\InboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OWNER PORTAL · MESSAGES (Wave 3) — the screen, over the existing inbox backend.
//
// The isolation proof lives in OwnerInboxIsolationTest (symmetric, mutation-proven). This file defends what
// the SCREEN claims:
//   • The transcript is MIRRORED. `messages.direction` is ORG-relative, so the owner's own message is
//     `inbound`. Getting this backwards would show every owner their own words as if staff had said them —
//     which is why it is asserted rather than eyeballed.
//   • The reply is a REAL message (postOwnerMessage), not a fake send: it persists, it lands in the
//     OPERATOR's inbox, and it bumps the operator's unread.
//   • The owner's unread is `owner_last_read_at`, NEVER the operator's `unread_count`.
//   • The fabrications are absent: no "✓✓ Read" (an invented enum value), no response-time promise, no
//     presence, no vendor desks.

/** A staff member who can reply — a real user with a real back-office role. */
function inboxStaff(array $s, string $name = 'Marco Reyes'): User
{
    return operatorUser($s, ['name' => $name], 'Hotel Manager');
}

// ── 1. THE POINT OF THE PAGE — the owner's real threads, with a real staff correspondent ─────────
it('lists the owner’s real conversations with the staff member who replied', function () {
    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $marco = inboxStaff($s);

    $thread = ownerThread($a['owner'], 'About my October payout');
    $inbox = app(InboxService::class);
    $inbox->postOwnerMessage($thread, 'When is my next payout?', $a['user']);
    $inbox->sendOutbound($thread, 'Scheduled for 3 Nov — it is in your statement.', $marco);

    $response = $this->actingAs($a['user'])->get('/owner/messages');

    $response->assertOk()
        ->assertSee('Marco Reyes')                    // the REAL staff user who replied…
        ->assertSee('Hotel Manager')                  // …and their REAL back-office role
        ->assertSee('Scheduled for 3 Nov')            // the real last-message preview
        ->assertSee('1 conversation');

    assertNoRawBlade($response);
});

// ── 2. ⚠️ THE MIRROR — the owner's own message must render as THEIRS, not as staff's ─────────────
it('mirrors the transcript: the owner’s own message is inbound to the org but "You" on their screen', function () {
    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $marco = inboxStaff($s);

    $thread = ownerThread($a['owner']);
    $inbox = app(InboxService::class);
    $mine = $inbox->postOwnerMessage($thread, 'Is the AC fixed?', $a['user']);
    $theirs = $inbox->sendOutbound($thread, 'All fixed — regassed and tested.', $marco);

    // The MODEL is org-relative: the owner's message went INTO the org.
    expect((string) $mine->direction)->toBe('inbound')
        ->and((string) $theirs->direction)->toBe('outbound');

    // The SCREEN mirrors it: the owner sees their own words as "You", and Marco's as Marco's.
    $response = $this->actingAs($a['user'])->get('/owner/messages/'.$thread->id);

    $response->assertOk()
        ->assertSeeInOrder(['Is the AC fixed?', 'You'])
        ->assertSee('All fixed — regassed and tested.')
        ->assertSee('Marco Reyes');
});

// ── 3. THE REPLY IS REAL — it persists, and it reaches the OPERATOR ──────────────────────────────
it('sends a real message that persists and lands in the operator’s inbox', function () {
    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $marco = inboxStaff($s);
    $thread = ownerThread($a['owner'], 'Rate question');

    $unreadBefore = (int) $thread->fresh()->unread_count;

    $response = $this->actingAs($a['user'])
        ->post('/owner/messages/'.$thread->id, ['body' => 'Could we raise the weekend rate?']);

    $response->assertRedirect(route('owner.messages.show', $thread->id))->assertSessionHas('owner_notice');

    // It is a REAL row, inbound (into the org), authored by the owner.
    $message = Message::query()->where('thread_id', $thread->id)->latest('created_at')->firstOrFail();
    expect((string) $message->body)->toBe('Could we raise the weekend rate?')
        ->and((string) $message->direction)->toBe('inbound')
        ->and((string) $message->author_user_id)->toBe((string) $a['user']->id)
        // Never "Operator" — sendOutbound() hard-codes that for the other side.
        ->and((string) $message->sender_label)->toBe('Amara Okafor');

    // ⚠️ THE LOOP CLOSES: it bumped the OPERATOR's unread, and the operator's inbox shows the thread NAMED
    // (it used to say "Unknown sender" for a guest-less thread — an owner would have messaged into a void).
    expect((int) $thread->fresh()->unread_count)->toBe($unreadBefore + 1);

    $this->actingAs($marco)->get('/app/inbox')
        ->assertOk()
        ->assertSee('Amara Okafor')
        ->assertDontSee('Unknown sender');
});

// ── 4. THE OWNER'S UNREAD IS THEIRS — never the operator's counter ───────────────────────────────
it('derives the owner’s unread from their own read position, and never touches the operator’s', function () {
    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $marco = inboxStaff($s);
    $thread = ownerThread($a['owner']);
    $inbox = app(InboxService::class);

    $inbox->postOwnerMessage($thread, 'A question', $a['user']);   // bumps the OPERATOR's unread to 1
    $inbox->sendOutbound($thread, 'An answer', $marco);            // a staff reply → unread FOR THE OWNER

    expect((int) $thread->fresh()->unread_count)->toBe(1)          // the operator has not read it
        ->and($thread->fresh()->owner_last_read_at)->toBeNull();

    $this->actingAs($a['user'])->get('/owner/messages')->assertOk()->assertSee('1 unread');

    // Opening it marks it read FOR THE OWNER…
    $this->actingAs($a['user'])->get('/owner/messages/'.$thread->id)->assertOk();

    $fresh = $thread->fresh();
    expect($fresh->owner_last_read_at)->not->toBeNull()
        // …and CRUCIALLY leaves the operator's counter alone. Clearing it would tell the operator they had
        // read a message they have not seen.
        ->and((int) $fresh->unread_count)->toBe(1);

    $this->actingAs($a['user'])->get('/owner/messages')->assertOk()->assertDontSee('1 unread');
});

// ── 5. FILTERS + SEARCH over real rows ───────────────────────────────────────────────────────────
it('filters unread and searches real message bodies', function () {
    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $marco = inboxStaff($s);
    $inbox = app(InboxService::class);

    $answered = ownerThread($a['owner'], 'Payout question');
    $inbox->sendOutbound($answered, 'Your payout is scheduled for the third.', $marco);

    $quiet = ownerThread($a['owner'], 'Linen question');
    $inbox->postOwnerMessage($quiet, 'Just checking about towels.', $a['user']);

    // Unread = a STAFF reply the owner has not read. Their own message is not "unread" to them.
    $this->actingAs($a['user'])->get('/owner/messages?filter=unread')
        ->assertOk()
        ->assertSee('Your payout is scheduled')
        ->assertDontSee('Just checking about towels');

    // Search hits real bodies — `messages.body` is PLAINTEXT, so unlike guest email/phone it is searchable.
    $this->actingAs($a['user'])->get('/owner/messages?q=towels')
        ->assertOk()
        ->assertSee('Just checking about towels')
        ->assertDontSee('Your payout is scheduled');
});

// ── 6. COMPOSE — a real thread, owned by the owner the CONTEXT says, not the request ─────────────
it('starts a real thread for the authenticated owner', function () {
    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    inboxStaff($s);

    $this->actingAs($a['user'])->post('/owner/messages', [
        'subject' => 'Blocking dates',
        'body' => 'Can I block the first week of December for personal use?',
    ])->assertRedirect();

    $thread = MessageThread::query()->withoutGlobalScopes()->where('subject', 'Blocking dates')->firstOrFail();

    // Owned by the owner the OwnerContext says this user is — never a body-supplied id.
    expect((string) $thread->owner_id)->toBe((string) $a['owner']->id)
        ->and($thread->guest_id)->toBeNull()          // guest_id XOR owner_id — the discriminator
        ->and((string) $thread->channel)->toBe('manual');

    expect(Message::query()->where('thread_id', $thread->id)->count())->toBe(1);
});

// ── 7. THE DESIGN'S FABRICATIONS ARE ABSENT ──────────────────────────────────────────────────────
it('renders none of the design’s invented status, metric, presence or vendor desks', function () {
    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $marco = inboxStaff($s);
    $thread = ownerThread($a['owner']);
    $inbox = app(InboxService::class);
    $inbox->postOwnerMessage($thread, 'A question', $a['user']);
    $inbox->sendOutbound($thread, 'An answer', $marco);

    $response = $this->actingAs($a['user'])->get('/owner/messages/'.$thread->id);

    $response->assertOk()
        // "read" is NOT in `messages.delivery_status` — the design invents it and defaults every message
        // to it, asserting staff have read everything the owner ever sent.
        ->assertDontSee('✓✓')
        ->assertDontSee('Read', false)
        // A response-time promise the app cannot measure (no first-response metric, no SLA, no presence).
        ->assertDontSee('usually replies within an hour')
        ->assertDontSee('replies within a few hours')
        // The SaaS vendor is not the operator; a cross-tenant helpdesk cannot live in an org-scoped table.
        ->assertDontSee('Vyrobel Support')
        ->assertDontSee('Vyrobel Billing')
        // A team is not a user — there is nobody to address or attribute.
        ->assertDontSee('Maintenance team');

    assertNoRawBlade($response);
});
