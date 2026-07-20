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
