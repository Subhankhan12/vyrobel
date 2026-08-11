<?php

use App\Models\Guest;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Owner;
use App\Services\InboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// GUEST MESSAGES (Phase 4c · Sub-gate 3) — the guest side of the EXISTING Unified Inbox (extended, not
// reimplemented). Proven: a guest sends a real message via InboxService (persists inbound, the operator's badge
// bumps); the operator's reply shows mirrored in the guest transcript; a guest sees ONLY their OWN thread (another
// guest's / an owner thread → 404, mutation-proven); a guest read never clears the operator's unread_count; and
// anonymize() redacts a guest-authored body (#25).

if (! function_exists('msgGuest')) {
    function msgGuest(Guest $g, string $email): Guest
    {
        $g->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $g->password = 'guest-password-1234';
        $g->email_verified_at = now();
        $g->save();

        return $g->fresh();
    }
}

if (! function_exists('msgInbox')) {
    function msgInbox(): InboxService
    {
        return app(InboxService::class);
    }
}

// ── A guest starts a thread + sends a real message the operator can see ────────────────────────────
it('lets a guest start a thread and send a real message via InboxService', function () {
    $s = seedScenario(1);
    $a = msgGuest($s['guest'], 'ada@example.test');

    $this->actingAs($a, 'guest')->post(route('guest.messages.start'), ['subject' => 'Parking', 'body' => 'Is there parking?'])
        ->assertRedirect();

    $thread = MessageThread::withoutGlobalScopes()->where('guest_id', $a->getKey())->first();
    expect($thread)->not->toBeNull();
    expect((string) $thread->subject)->toBe('Parking');

    $message = Message::withoutGlobalScopes()->where('thread_id', $thread->id)->first();
    expect((string) $message->direction)->toBe('inbound');   // the guest's own message = into the org
    expect((string) $message->body)->toBe('Is there parking?');
    expect((int) $thread->fresh()->unread_count)->toBe(1);   // the operator's inbox lights up
});

// ── The operator's reply shows mirrored in the guest transcript ───────────────────────────────────
it('shows the operator’s reply mirrored in the guest transcript', function () {
    $s = seedScenario(1);
    $a = msgGuest($s['guest'], 'ada@example.test');
    $thread = msgInbox()->startThread(['channel' => 'manual', 'guest_id' => $a->getKey(), 'subject' => 'Wifi']);
    msgInbox()->postGuestMessage($thread, 'What is the wifi password?');
    $staff = operatorUser($s, [], 'Hotel Manager');
    msgInbox()->sendOutbound($thread, 'It is seaside2026.', $staff);

    $this->actingAs($a, 'guest')->get(route('guest.messages.show', $thread->id))
        ->assertOk()
        ->assertSee('What is the wifi password?')   // the guest's own message
        ->assertSee('It is seaside2026.')           // the operator's reply
        ->assertSee('You');                         // the guest's own message is labelled "You"
});

// ── ISOLATION (mutation-proven): a guest can't open another guest's OR an owner's thread ──────────
it('never lets a guest open another guest’s or an owner’s thread', function () {
    $s = seedScenario(1);
    $a = msgGuest($s['guest'], 'ada@example.test');
    $b = Guest::create(['email' => 'bob@example.test', 'first_name' => 'Bob', 'last_name' => 'Vale']);

    $aThread = msgInbox()->startThread(['channel' => 'manual', 'guest_id' => $a->getKey(), 'subject' => 'Mine']);
    $bThread = msgInbox()->startThread(['channel' => 'manual', 'guest_id' => $b->getKey(), 'subject' => 'Bob’s']);
    $ownerThread = msgInbox()->startThread(['channel' => 'manual', 'owner_id' => Owner::factory()->create()->getKey(), 'subject' => 'Owner']);

    $this->actingAs($a, 'guest')->get(route('guest.messages.show', $aThread->id))->assertOk();          // own → ok
    $this->actingAs($a, 'guest')->get(route('guest.messages.show', $bThread->id))->assertNotFound();     // other guest → 404
    $this->actingAs($a, 'guest')->get(route('guest.messages.show', $ownerThread->id))->assertNotFound();  // owner thread → 404
    $this->actingAs($a, 'guest')->post(route('guest.messages.send', $bThread->id), ['body' => 'sneaky'])->assertNotFound();
});

// ── A guest read never clears the OPERATOR's unread badge ─────────────────────────────────────────
it('marks read for the guest without clearing the operator’s unread badge', function () {
    $s = seedScenario(1);
    $a = msgGuest($s['guest'], 'ada@example.test');
    $thread = msgInbox()->startThread(['channel' => 'manual', 'guest_id' => $a->getKey(), 'subject' => 'Hi']);
    msgInbox()->postGuestMessage($thread, 'Hello');
    expect((int) $thread->fresh()->unread_count)->toBe(1);

    $this->actingAs($a, 'guest')->get(route('guest.messages.show', $thread->id))->assertOk();

    expect((int) $thread->fresh()->unread_count)->toBe(1);              // the operator's counter is UNTOUCHED
    expect($thread->fresh()->guest_last_read_at)->not->toBeNull();      // the guest's read position moved
});

// ── GDPR (#25): anonymize() redacts a guest-authored message body ─────────────────────────────────
it('redacts a guest-authored message body on anonymize', function () {
    $s = seedScenario(1);
    $a = msgGuest($s['guest'], 'ada@example.test');
    $thread = msgInbox()->startThread(['channel' => 'manual', 'guest_id' => $a->getKey(), 'subject' => 'Secret']);
    $message = msgInbox()->postGuestMessage($thread, 'my private message');

    $a->anonymize();

    expect((string) Message::withoutGlobalScopes()->find($message->id)->body)->toBe('');   // scrubbed
});

// ── G4 pixel-diff HONESTY: name-only chat; NO fabricated presence / SLA / host phone (#39/#28) ──
it('renders a name-only chat and refuses the wireframe presence/SLA/phone fabrications', function () {
    $s = seedScenario(1);
    $a = msgGuest($s['guest'], 'ada@example.test');
    $this->actingAs($a, 'guest')->post(route('guest.messages.start'), ['subject' => 'Parking', 'body' => 'Is there parking?'])->assertRedirect();
    $thread = MessageThread::withoutGlobalScopes()->where('guest_id', $a->getKey())->first();

    // Messages list + thread render with the real subject; the property side is name-only.
    $this->actingAs($a, 'guest')->get(route('guest.messages'))->assertOk()->assertSee('Parking');
    $view = $this->actingAs($a, 'guest')->get(route('guest.messages.show', $thread->getKey()))->assertOk();

    $view->assertSee('With the property team')
        // #39: NO realtime presence / typing, NO reply-time SLA, NO named-host persona, NO contact phone (#28).
        ->assertDontSee('Online now')
        ->assertDontSee('is typing')
        ->assertDontSee('replies in')
        ->assertDontSee('Marco Silva')
        ->assertDontSee('+351 289');
});

// ── 🔒 THE CLASSIC LEAK: an operator's INTERNAL note on the guest's OWN thread ────────────────────
// A guest↔operator conversation is the classic place staff annotations bleed into a guest-visible view. Here
// the separation is STRUCTURAL, not a filter: staff notes are `notes` rows (polymorphic on `message_thread`),
// while the transcript reads `messages` — and `messages` has NO internal/visibility column at all, so an
// "internal message" cannot exist. A table the query never joins is stronger than a flag that can be forgotten
// (the same shape as the owner O27 boundary and G3's ArrivalPack delegation).
//
// ⚠️ The note is written through the REAL operator path (InboxService::addNote), not a hand-rolled row —
// otherwise the fixture proves nothing about what staff actually create (the systemic vacuous-fixture lesson
// from the #33 guards at G1/G2).
it('never shows an operator’s internal thread note in the guest transcript (#28)', function () {
    $s = seedScenario(1);
    $a = msgGuest($s['guest'], 'ada@example.test');
    $thread = msgInbox()->startThread(['channel' => 'manual', 'guest_id' => $a->getKey(), 'subject' => 'Early check-in?']);
    msgInbox()->postGuestMessage($thread, 'Can we arrive early?');

    // A REAL staff annotation on the guest's OWN thread — the kind that names other guests, rates, or plans.
    $secret = 'INTERNAL: comp the late checkout, she complained last stay — do not tell her the rate';
    msgInbox()->addNote($thread, $secret);
    expect($thread->notes()->count())->toBe(1);   // the note genuinely exists, so the guard can fail

    $html = $this->actingAs($a, 'guest')->get(route('guest.messages.show', $thread->getKey()))
        ->assertOk()
        ->assertSee('Can we arrive early?')       // the real transcript renders (never vacuous)
        ->getContent();

    expect($html)->not->toContain($secret)
        ->and($html)->not->toContain('INTERNAL')
        ->and($html)->not->toContain('do not tell her');
});

// ── The send is scoped, and the foreign thread gains NOTHING ──────────────────────────────────────
// G6's lesson: a write route can be tested for PRESENCE without ever being tested for ISOLATION. The 404 is
// already guarded above; this adds the half that matters — that the refusal left no trace on B's thread, and
// that A's own send still works, so it cannot pass merely because the route is broken.
it('leaves a foreign thread untouched when a guest tries to post into it', function () {
    $s = seedScenario(1);
    $a = msgGuest($s['guest'], 'ada@example.test');
    $b = Guest::create(['email' => 'bob@example.test', 'first_name' => 'Bob', 'last_name' => 'Vale']);
    $aThread = msgInbox()->startThread(['channel' => 'manual', 'guest_id' => $a->getKey(), 'subject' => 'Mine']);
    $bThread = msgInbox()->startThread(['channel' => 'manual', 'guest_id' => $b->getKey(), 'subject' => 'Bob’s']);

    $this->actingAs($a, 'guest')->post(route('guest.messages.send', $bThread->getKey()), ['body' => 'sneaky'])
        ->assertNotFound();

    expect(Message::query()->where('thread_id', $bThread->getKey())->count())->toBe(0);   // no trace on B's thread

    // …and A's own send still lands, so this cannot pass on a simply-broken route.
    $this->actingAs($a, 'guest')->post(route('guest.messages.send', $aThread->getKey()), ['body' => 'hello'])
        ->assertRedirect();
    expect(Message::query()->where('thread_id', $aThread->getKey())->count())->toBe(1);
});
