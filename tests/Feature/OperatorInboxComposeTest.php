<?php

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Note;
use App\Models\Owner;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\Operator\ThreadAssignedNotification;
use App\Notifications\Owner\OwnerMessageReceivedNotification;
use App\Services\InboxService;
use App\Support\Inbox\MessageTemplate;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
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

// ── GATE 3.2 fix — the composer + the link reference the SAME (current/upcoming) reservation, not the oldest ──
it('picks the current/upcoming reservation over a recent checkout for the messaging context', function () {
    $today = CarbonImmutable::parse('2026-08-02');
    $mk = fn (string $in, string $out, string $status) => (new Reservation)->forceFill(['check_in' => $in, 'check_out' => $out, 'status' => $status]);

    $past = $mk('2026-07-20', '2026-07-22', 'checked_out');      // recent checkout (within 14d)
    $upcoming = $mk('2026-08-10', '2026-08-13', 'confirmed');    // upcoming stay
    $inHouse = $mk('2026-08-01', '2026-08-05', 'checked_in');    // currently in-house
    $older = $mk('2026-06-01', '2026-06-03', 'checked_out');     // older checkout

    // an upcoming stay beats a recent checkout (the review-caught bug: the composer used to pick the past one)
    expect(MessageTemplate::mostRelevant([$past, $upcoming], $today))->toBe($upcoming)
        ->and(MessageTemplate::mostRelevant([$upcoming, $past], $today))->toBe($upcoming)
        // in-house (active, earliest check-in) beats an upcoming stay
        ->and(MessageTemplate::mostRelevant([$upcoming, $inHouse], $today))->toBe($inHouse)
        // among past-only, the MOST-recent checkout wins
        ->and(MessageTemplate::mostRelevant([$older, $past], $today))->toBe($past);
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
        ->assertSee('Guests staying now, arriving soon, or who left in the last 14 days.');   // what it lists, said once (2026-10-05)
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

// ══════════════════════════════════════════════════════════════════════════════════════════════════════════════════
// D-TC-LATER (2026-11-11, his go) — START A CONVERSATION WITH A UNIT OWNER FROM THE BACK OFFICE. The New message modal could
// only open a guest (`guest_id` was required); an owner could write to the operator, never the other way. An owner thread is
// the `manual` channel with an `owner_id`; the existing handler rings the owner's portal — but only if the owner HAS a portal
// sign-in, so one without is refused in words rather than "sent" to nobody.
// ══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════

/** The Owner row behind an owner-role user (ownerRoleUser() makes both). */
function icOwnerOf(User $user): Owner
{
    return Owner::query()->where('user_id', $user->id)->firstOrFail();
}

it('⚠️⚠️ starts a conversation with a unit owner — manual channel, delivered, assigned to the author, and the owner\'s portal is rung', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    $ownerUser = ownerRoleUser($s);
    $owner = icOwnerOf($ownerUser);

    $this->actingAs($manager)->post(route('app.inbox.start'), ['owner_id' => $owner->id, 'body' => 'Your terrace is being repaired on Monday.'])
        ->assertRedirect()->assertSessionHas('inbox_notice');

    $thread = MessageThread::query()->where('owner_id', $owner->id)->sole();
    expect($thread->channel)->toBe('manual')->and($thread->guest_id)->toBeNull()->and((string) $thread->assigned_to)->toBe((string) $manager->id);
    $message = Message::query()->where('thread_id', $thread->id)->sole();
    expect($message->direction)->toBe('outbound')->and($message->delivery_status)->toBe('delivered')->and((string) $message->body)->toBe('Your terrace is being repaired on Monday.');
    // the owner's portal is rung — once, for this message, by the handler that already exists
    expect($ownerUser->notifications()->where('type', OwnerMessageReceivedNotification::class)->count())->toBe(1);

    // a second start with the same owner continues the SAME conversation — never a second thread
    $this->actingAs($manager)->post(route('app.inbox.start'), ['owner_id' => $owner->id, 'body' => 'And the water will be off for an hour.'])->assertRedirect(route('app.inbox', ['c' => $thread->id]));
    expect(MessageThread::query()->where('owner_id', $owner->id)->count())->toBe(1)
        ->and(Message::query()->where('thread_id', $thread->id)->count())->toBe(2);
});

it('⚠️⚠️ refuses an owner with no portal sign-in, in words — nothing is created, nobody is told it was sent', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    $owner = Owner::factory()->create(['name' => 'Paul Paperless', 'user_id' => null]);

    $this->actingAs($manager)->post(route('app.inbox.start'), ['owner_id' => $owner->id, 'body' => 'Hello'])
        ->assertRedirect()
        ->assertSessionHas('inbox_error', 'No portal sign-in yet — invite Paul Paperless to the owner portal first.')
        ->assertSessionMissing('inbox_notice');

    expect(MessageThread::query()->where('owner_id', $owner->id)->count())->toBe(0)
        ->and(Message::query()->count())->toBe(0);
});

it('⚠️⚠️ keeps the owner start inside its workspace and its permission — another workspace\'s owner is not found, and a role without Guests cannot start one', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    $ownerUser = ownerRoleUser($s);
    $owner = icOwnerOf($ownerUser);
    $other = seedScenario(1);
    $foreignOwner = icOwnerOf(ownerRoleUser($other));
    $housekeeper = operatorUser($s, [], 'Housekeeping');
    app(TenantContext::class)->set($s['org']->id);

    // PERMITTED actor beside the refused ones: the manager can start it with their own owner (above in the first test)
    $this->actingAs($manager)->post(route('app.inbox.start'), ['owner_id' => $foreignOwner->id, 'body' => 'Hi'])->assertNotFound();
    $this->actingAs($housekeeper)->post(route('app.inbox.start'), ['owner_id' => $owner->id, 'body' => 'Hi'])->assertForbidden();
    // one recipient or the other — never both, never neither
    $this->actingAs($manager)->from(route('app.inbox'))->post(route('app.inbox.start'), ['owner_id' => $owner->id, 'guest_id' => $s['guest']->id])->assertSessionHasErrors();
    $this->actingAs($manager)->from(route('app.inbox'))->post(route('app.inbox.start'), ['body' => 'Hi'])->assertSessionHasErrors();

    expect(MessageThread::query()->count())->toBe(0);
});

it('⚠️ keeps a paused workspace read-only for the owner start too', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    $owner = icOwnerOf(ownerRoleUser($s));
    $s['org']->forceFill(['paused_until' => now()->addDays(10)])->save();

    $this->actingAs($manager)->post(route('app.inbox.start'), ['owner_id' => $owner->id, 'body' => 'Hi'])->assertSessionHas('inbox_error');
    expect(MessageThread::query()->where('owner_id', $owner->id)->count())->toBe(0);
});

it('⚠️ lists owners in the New message picker by NAME and units only — no contact value — and shows a portal-less owner disabled', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s);
    $ownerUser = ownerRoleUser($s);   // owns the scenario's unit 0, with a portal sign-in
    $owner = icOwnerOf($ownerUser);
    $owner->forceFill(['name' => 'Olive Ownerson', 'email' => 'olive.private@example.test', 'phone' => '+351 910 555 777'])->save();
    Owner::factory()->create(['name' => 'Paul Paperless', 'user_id' => null]);

    $page = (string) $this->actingAs($manager)->get(route('app.inbox'))->assertOk()->getContent();
    expect($page)->toContain('Find an owner')->toContain('Olive Ownerson')->toContain('Paul Paperless')
        ->toContain((string) $s['units'][0]->name)                       // the unit, as the meta line
        ->toContain('No portal sign-in yet')                            // the portal-less owner says why
        ->not->toContain('olive.private@example.test')->not->toContain('910 555 777');   // #28: name-only, no contact value anywhere
    // Start stays off until the picked kind of recipient has one — and says "owner" for an owner
    expect($page)->toContain(":disabled=\"composeKind === 'guest' ? ! composeGuest : ! composeOwner\"");
    // the portal-less owner's radio is disabled — it cannot be picked
    expect($page)->toMatch('~value="'.preg_quote((string) Owner::query()->where('name', 'Paul Paperless')->value('id'), '~').'"[^>]*disabled~');
});
