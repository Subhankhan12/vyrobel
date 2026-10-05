<?php

use App\Models\Membership;
use App\Models\User;
use App\Services\InboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #90 · Operator MESSAGES conversation pane (OperatorInbox 3-pane, /app/inbox?c={thread}). Locks its
// PRESENTATION guarantees: real transcript + delivery states; the reply sends over the thread's real channel
// (manual delivered); a DEFERRED channel shows an honest "not connected" composer (invariant #24); lifecycle
// actions persist + are guarded; the "N stays" stat is derived-real or omitted; guest NAME shown, encrypted
// identity not. (The legacy /app/inbox/{thread} deep-link now redirects into the 3-pane via ?c=.)

function convoMember(array $scenario, string $name = 'Marco Reyes'): User
{
    $user = User::factory()->create(['name' => $name, 'status' => 'active']);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    // A Membership alone is NOT operator access — /app is an allow-list of back-office roles
    // (EnsureBackOfficeAccess). A real staff member holds one, as OwnerInviteService assigns.
    return grantBackOfficeRole($user, $scenario['org']);
}

// ── 1. Real transcript + delivery status (manual outbound → Delivered) ────────────────────────────
it('renders the transcript with real delivery status', function () {
    $s = seedScenario(1);
    $inbox = app(InboxService::class);
    $in = $inbox->postInbound(['channel' => 'manual', 'external_message_id' => 'c1', 'body' => 'Any parking?', 'participant_identity' => 'g@guest.test', 'sender_label' => 'Gilda Guest']);
    $inbox->sendOutbound($in->thread, 'Yes, free parking on site.', convoMember($s));

    $this->actingAs(convoMember($s))->get(route('app.inbox', ['c' => $in->thread_id]))
        ->assertOk()
        ->assertSee('Any parking?')                 // inbound
        ->assertSee('Yes, free parking on site.')   // outbound
        ->assertSee('Saved')                        // real delivery status — Direct keeps it in Vyrobel, it is not "Delivered" (2026-10-05)
        ->assertDontSee('Delivered')
        ->assertSee('Write a reply…');              // the Direct composer (a booking channel keeps "Reply via …")
});

// ── 1b. The legacy deep-link redirects into the 3-pane ───────────────────────────────────────────
it('redirects the legacy thread deep-link into the 3-pane', function () {
    $s = seedScenario(1);
    $in = app(InboxService::class)->postInbound(['channel' => 'manual', 'external_message_id' => 'dl1', 'body' => 'Hi', 'participant_identity' => 'g@guest.test']);

    $this->actingAs(convoMember($s))->get("/app/inbox/{$in->thread_id}")
        ->assertRedirect(route('app.inbox', ['c' => $in->thread_id]));
});

// ── 2. A DEFERRED channel shows the honest "not connected" composer (no fake send) ───────────────
it('shows an honest not-connected composer on a deferred channel', function () {
    $s = seedScenario(1);
    $thread = app(InboxService::class)->startThread(['channel' => 'email', 'participant_identity' => 'g@guest.test']);

    $this->actingAs(convoMember($s))->get(route('app.inbox', ['c' => $thread->id]))
        ->assertOk()
        ->assertSee('isn’t connected yet')          // honest blocked state
        ->assertDontSee('Reply via');               // no active composer
});

// ── 3. A forced reply on a deferred channel is honestly failed, never faked ──────────────────────
it('records an honest failed reply on a deferred channel via the route', function () {
    $s = seedScenario(1);
    $thread = app(InboxService::class)->startThread(['channel' => 'whatsapp', 'participant_identity' => 'g@guest.test']);

    $this->actingAs(convoMember($s))
        ->post(route('app.inbox.reply', $thread->id), ['body' => 'Trying anyway'])
        ->assertRedirect(route('app.inbox', ['c' => $thread->id]))
        ->assertSessionHas('inbox_error');

    $this->assertDatabaseHas('messages', ['thread_id' => $thread->id, 'direction' => 'outbound', 'delivery_status' => 'failed']);
});

// ── 4. Lifecycle actions persist + are guarded ───────────────────────────────────────────────────
it('assigns, closes, reopens and snoozes through the real actions', function () {
    $s = seedScenario(1);
    $member = convoMember($s);
    $thread = app(InboxService::class)->postInbound(['channel' => 'manual', 'external_message_id' => 'lc1', 'body' => 'Hi', 'participant_identity' => 'g@guest.test'])->thread;

    $this->actingAs($member)->post(route('app.inbox.assign', $thread->id), ['user_id' => $member->id])->assertRedirect();
    $this->assertDatabaseHas('message_threads', ['id' => $thread->id, 'assigned_to' => $member->id]);

    $this->actingAs($member)->post(route('app.inbox.close', $thread->id))->assertRedirect();
    $this->assertDatabaseHas('message_threads', ['id' => $thread->id, 'status' => 'closed']);

    $this->actingAs($member)->post(route('app.inbox.reopen', $thread->id))->assertRedirect();
    $this->assertDatabaseHas('message_threads', ['id' => $thread->id, 'status' => 'open']);

    $this->actingAs($member)->post(route('app.inbox.snooze', $thread->id))->assertRedirect();
    $this->assertDatabaseHas('message_threads', ['id' => $thread->id, 'status' => 'snoozed']);
});

// ── 5. The "N stays · guest since {year}" stat is DERIVED from real reservations (or omitted) ─────
it('derives the guest stay stat from real reservations, or omits it', function () {
    $s = seedScenario(1);
    book($s);   // a real reservation for $s['guest']
    $inbox = app(InboxService::class);

    // A thread linked to the guest who has a reservation → the derived stat appears.
    $withStay = $inbox->postInbound(['channel' => 'manual', 'external_message_id' => 'st1', 'body' => 'Booked guest', 'guest_id' => $s['guest']->id]);
    $this->actingAs(convoMember($s))->get(route('app.inbox', ['c' => $withStay->thread_id]))
        ->assertOk()
        ->assertSee('guest since');   // "1 stay · guest since {year}"

    // A stranger with NO reservations → the stat is omitted (not hard-coded).
    $stranger = $inbox->postInbound(['channel' => 'manual', 'external_message_id' => 'st2', 'body' => 'New person', 'participant_identity' => 'stranger@guest.test', 'sender_label' => 'Stranger New']);
    $this->actingAs(convoMember($s))->get(route('app.inbox', ['c' => $stranger->thread_id]))
        ->assertOk()
        ->assertSee('Stranger New')
        ->assertDontSee('guest since');
});

// ── 6. Linking a reservation from the sidebar persists (tenant-scoped) ───────────────────────────
it('links a reservation from the conversation', function () {
    $s = seedScenario(1);
    $reservation = book($s);
    $thread = app(InboxService::class)->postInbound(['channel' => 'manual', 'external_message_id' => 'lk1', 'body' => 'Question', 'guest_id' => $s['guest']->id])->thread;

    $this->actingAs(convoMember($s))
        ->post(route('app.inbox.link-reservation', $thread->id), ['reservation_id' => $reservation->id])
        ->assertRedirect(route('app.inbox', ['c' => $thread->id]));

    $this->assertDatabaseHas('message_threads', ['id' => $thread->id, 'reservation_id' => $reservation->id]);
});
