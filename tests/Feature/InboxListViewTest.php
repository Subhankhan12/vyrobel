<?php

use App\Models\Membership;
use App\Models\Message;
use App\Models\User;
use App\Services\InboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Extract a stat-card count by its label from the rendered inbox HTML (GATE 3.1). */
function statCount(string $html, string $label): ?int
{
    return preg_match('/vy-istat-num[^>]*>(\d+)<\/span><span class="vy-istat-label">'.preg_quote($label, '/').'/', $html, $m) === 1
        ? (int) $m[1]
        : null;
}

// #90 · Operator MESSAGES rail (OperatorInbox 3-pane, /app/inbox). Locks its PRESENTATION guarantees: real
// threads with the real channel badge + last-message preview + unread state; the linked-reservation tag ONLY
// when truly linked; guest NAME shown but the encrypted participant identity NEVER rendered; tenant-scoped.

function listMember(array $scenario, string $name = 'Marco Reyes'): User
{
    $user = User::factory()->create(['name' => $name, 'status' => 'active']);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    // A Membership alone is NOT operator access — /app is an allow-list of back-office roles
    // (EnsureBackOfficeAccess). A real staff member holds one, as OwnerInviteService assigns.
    return grantBackOfficeRole($user, $scenario['org']);
}

// ── 1. Real threads render with the channel label + preview; encrypted identity is NOT shown ─────
it('renders real threads with the channel label and never the encrypted identity', function () {
    $s = seedScenario(1);
    app(InboxService::class)->postInbound([
        'channel' => 'manual', 'external_message_id' => 'l1', 'body' => 'What time is check-in?',
        'participant_identity' => 'private@guest.test', 'sender_label' => 'Ada Okafor',
    ]);

    $this->actingAs(listMember($s))->get('/app/inbox')
        ->assertOk()
        ->assertSee('Messages')
        ->assertSee('Ada Okafor')                 // guest NAME
        ->assertSee('What time is check-in?')      // real preview
        ->assertSee('Direct')                      // real channel badge label (manual → Direct)
        ->assertDontSee('private@guest.test');     // encrypted participant identity NEVER rendered
});

// ── 2. The linked-reservation tag shows ONLY when the thread is genuinely linked ─────────────────
it('shows the reservation tag only for a linked thread', function () {
    $s = seedScenario(1);
    $s['units'][0]->update(['code' => 'TAGUNIT']);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);   // physically assigned → the unit shows in the tag
    $inbox = app(InboxService::class);

    // Linked thread → tag with the reservation reference (post the inbound INTO it via its anchor).
    $linked = $inbox->startThread(['channel' => 'manual', 'external_thread_ref' => 'linked-1', 'guest_id' => $s['guest']->id]);
    $inbox->linkReservation($linked, $reservation->id);
    $inbox->postInbound(['channel' => 'manual', 'external_thread_ref' => 'linked-1', 'external_message_id' => 'lk1', 'body' => 'Linked question', 'guest_id' => $s['guest']->id]);

    // Unlinked thread — no reservation.
    $inbox->postInbound(['channel' => 'manual', 'external_message_id' => 'ul1', 'body' => 'Unlinked question', 'participant_identity' => 'stranger@guest.test', 'sender_label' => 'Stranger']);

    $this->actingAs(listMember($s))->get('/app/inbox')
        ->assertOk()
        ->assertSee($reservation->reference)   // the linked reservation reference appears
        ->assertSee('TAGUNIT');                // the linked unit code appears in the tag
});

// ── 3. Tenant isolation — org B's threads never appear ───────────────────────────────────────────
it('isolates threads by tenant on the list', function () {
    $other = seedScenario(1);
    app(InboxService::class)->postInbound(['channel' => 'manual', 'external_message_id' => 'x1', 'body' => 'FOREIGN preview', 'participant_identity' => 'foreign@guest.test', 'sender_label' => 'Foreign Guest']);

    $s = seedScenario(1);
    app(InboxService::class)->postInbound(['channel' => 'manual', 'external_message_id' => 'o1', 'body' => 'Own preview', 'participant_identity' => 'own@guest.test', 'sender_label' => 'Own Guest']);

    $this->actingAs(listMember($s))->get('/app/inbox')
        ->assertOk()
        ->assertSee('Own Guest')
        ->assertDontSee('Foreign Guest')
        ->assertDontSee('FOREIGN preview');
});

// ── GATE 3.1 — the 4 stat cards are REAL derived counts over the thread states ───────────────────
it('derives the four stat cards (Unread / Open / Waiting on guest / Needs sending) from real states', function () {
    $s = seedScenario(1);
    $inbox = app(InboxService::class);
    $user = listMember($s);

    // A: an unread guest inbound → Unread + Open (last message inbound → neither waiting nor needs-sending).
    $inbox->postInbound(['channel' => 'manual', 'external_message_id' => 'a1', 'body' => 'Question A', 'participant_identity' => 'a@guest.test', 'sender_label' => 'Guest A']);

    // B: a delivered operator reply → Open + Waiting on guest (our last message, delivered).
    $tB = $inbox->postInbound(['channel' => 'manual', 'external_thread_ref' => 'B', 'external_message_id' => 'b1', 'body' => 'Question B', 'participant_identity' => 'b@guest.test', 'sender_label' => 'Guest B'])->thread;
    $inbox->sendOutbound($tB, 'On its way', $user);
    $inbox->markRead($tB);   // read → not Unread, but still Open + Waiting

    // C: a FAILED outbound → Open + Needs sending (a real failed delivery to retry).
    $tC = $inbox->startThread(['channel' => 'manual', 'external_thread_ref' => 'C', 'subject' => 'C']);
    Message::create(['organization_id' => $s['org']->id, 'thread_id' => $tC->id, 'direction' => 'outbound', 'channel' => 'manual', 'body' => 'Undeliverable', 'delivery_status' => 'failed', 'sender_label' => 'Operator']);
    $tC->update(['last_message_at' => now()]);

    // D: a CLOSED thread → counted in NONE of the four (they are all over active threads only).
    $tD = $inbox->postInbound(['channel' => 'manual', 'external_thread_ref' => 'D', 'external_message_id' => 'd1', 'body' => 'Old question', 'participant_identity' => 'd@guest.test', 'sender_label' => 'Guest D'])->thread;
    $inbox->close($tD);

    $html = $this->actingAs($user)->get('/app/inbox')->assertOk()->getContent();

    expect(statCount($html, 'Unread'))->toBe(1)              // only A
        ->and(statCount($html, 'Open'))->toBe(3)             // A, B, C (not the closed D)
        ->and(statCount($html, 'Waiting on guest'))->toBe(1) // only B
        ->and(statCount($html, 'Needs sending'))->toBe(1);   // only C
});

// ── GATE 3.1 — closed threads show ONLY under the Closed tab, never in the default view ──────────
it('hides closed threads from the default view and shows them under Closed', function () {
    $s = seedScenario(1);
    $inbox = app(InboxService::class);
    $inbox->postInbound(['channel' => 'manual', 'external_message_id' => 'op1', 'body' => 'Open thread', 'participant_identity' => 'o@guest.test', 'sender_label' => 'Open Guest']);
    $tC = $inbox->postInbound(['channel' => 'manual', 'external_thread_ref' => 'cl', 'external_message_id' => 'cl1', 'body' => 'Closed thread', 'participant_identity' => 'c@guest.test', 'sender_label' => 'Closed Guest'])->thread;
    $inbox->close($tC);
    $user = listMember($s);

    $this->actingAs($user)->get('/app/inbox')->assertOk()
        ->assertSee('Open Guest')->assertDontSee('Closed Guest');   // default excludes closed
    $this->actingAs($user)->get('/app/inbox?filter=closed')->assertOk()
        ->assertSee('Closed Guest')->assertDontSee('Open Guest');   // Closed tab shows only closed
});

// ── GATE 3.1 — rail search matches full message BODIES, not just the last-message preview ─────────
it('searches the full message history, not only the last preview', function () {
    $s = seedScenario(1);
    $inbox = app(InboxService::class);
    // An older message holds a unique word; a newer message becomes the preview (so the word is NOT in the preview).
    $t = $inbox->postInbound(['channel' => 'manual', 'external_thread_ref' => 'hist', 'external_message_id' => 'h1', 'body' => 'my passport number is XYLOPHONE99', 'participant_identity' => 'h@guest.test', 'sender_label' => 'History Guest'])->thread;
    $inbox->postInbound(['channel' => 'manual', 'external_thread_ref' => 'hist', 'external_message_id' => 'h2', 'body' => 'thanks, see you soon']);

    // A control thread that must NOT match.
    $inbox->postInbound(['channel' => 'manual', 'external_message_id' => 'ctrl', 'body' => 'unrelated', 'participant_identity' => 'z@guest.test', 'sender_label' => 'Control Guest']);

    $this->actingAs(listMember($s))->get('/app/inbox?q=XYLOPHONE99')->assertOk()
        ->assertSee('History Guest')        // matched via an OLDER body, not the preview
        ->assertDontSee('Control Guest');
});

// ── GATE 3.1 — the channel filter appears only with >1 channel present, and filters by channel ───
it('shows the channel filter only when more than one channel is present and filters by it', function () {
    $s = seedScenario(1);
    $inbox = app(InboxService::class);
    $inbox->postInbound(['channel' => 'manual', 'external_message_id' => 'd1', 'body' => 'Direct msg', 'participant_identity' => 'd@guest.test', 'sender_label' => 'Direct Guest']);

    // One channel only → no channel filter row.
    $this->actingAs(listMember($s))->get('/app/inbox')->assertOk()->assertDontSee('All channels');

    // Add an email-channel thread → the channel row appears with both, and ?channel=Email filters.
    $inbox->postInbound(['channel' => 'email', 'external_message_id' => 'e1', 'body' => 'Email msg', 'participant_identity' => 'e@guest.test', 'sender_label' => 'Email Guest']);
    $user = listMember($s, 'Nadia Fields');
    $this->actingAs($user)->get('/app/inbox')->assertOk()->assertSee('All channels');
    $this->actingAs($user)->get('/app/inbox?channel=Email')->assertOk()
        ->assertSee('Email Guest')->assertDontSee('Direct Guest');
});
