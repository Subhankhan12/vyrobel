<?php

use App\Models\Membership;
use App\Models\User;
use App\Services\InboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Design pass · Inbox list (InboxList, /app/inbox). Locks its PRESENTATION guarantees: real threads with
// the real channel badge + last-message preview + unread state; the linked-reservation tag ONLY when truly
// linked; guest NAME shown but the encrypted participant identity NEVER rendered; tenant-scoped.

function listMember(array $scenario, string $name = 'Marco Reyes'): User
{
    $user = User::factory()->create(['name' => $name, 'status' => 'active']);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return $user;
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
        ->assertSee('Inbox')
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
