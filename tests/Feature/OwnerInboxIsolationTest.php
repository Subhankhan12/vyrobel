<?php

use App\Models\Guest;
use App\Models\MessageThread;
use App\Models\Owner;
use App\Models\User;
use App\Services\InboxService;
use App\Support\Owner\OwnerContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OWNER INBOX ISOLATION (Wave 3) — the test that had to exist before the screen.
//
// ⚠️ WHY THIS FILE IS THE POINT OF THE WHOLE GATE. `OwnerScope` keys on `owner_id`. Before Wave 3 that column
// did not exist, so `BelongsToOwner` could not be applied to `MessageThread` — meaning the scope did not
// "fail closed", **it never ran at all**. An owner inbox built over the model as it stood would have been
// tenant-scoped ONLY: every guest conversation and every OTHER owner's threads, visible to any owner. That is
// invariant #26's exact shape, and it is why the column landed before a single line of the screen.
//
// The two-inbox boundary is not a predicate anyone writes — it FALLS OUT of the scope:
//   • /owner (OwnerContext ACTIVE) → `owner_id IN (their ids)`; a guest thread has owner_id NULL → excluded.
//   • /app   (OwnerContext INACTIVE) → no constraint → the operator sees everything and can reply.
//   • a non-linked user on the portal → `1 = 0` → nothing (fail-closed, never falls open).
//
// Asserted SYMMETRICALLY (both owners, both directions) because a one-way test would pass on a scope that
// simply returned everything to everyone.

// ── 1. ⚠️ THE HEADLINE — two owners, asserted BOTH WAYS ──────────────────────────────────────────
it('shows each owner only their own threads, in both directions', function () {
    $s = seedScenario(2);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $b = inboxOwner($s, 'Bruno Keller', 1);

    $threadA = ownerThread($a['owner'], 'Amara asks about her payout');
    $threadB = ownerThread($b['owner'], 'Bruno asks about his rates');

    // Owner A sees exactly their own…
    app(OwnerContext::class)->activate([(string) $a['owner']->id]);
    $seenByA = MessageThread::query()->pluck('id')->all();
    expect($seenByA)->toBe([(string) $threadA->id]);

    // …and owner B sees exactly theirs. Symmetric: a scope that returned everything would pass one of these
    // assertions and fail the other, so both are stated.
    app(OwnerContext::class)->activate([(string) $b['owner']->id]);
    $seenByB = MessageThread::query()->pluck('id')->all();
    expect($seenByB)->toBe([(string) $threadB->id]);
});

// ── 2. AN OWNER NEVER SEES AN OPERATOR↔GUEST THREAD — the leak the missing column would have caused ──
it('never shows an owner a guest conversation', function () {
    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);

    $guest = Guest::create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.test']);
    $guestThread = app(InboxService::class)->startThread([
        'channel' => 'manual',
        'subject' => 'Guest asks about late check-in',
        'guest_id' => $guest->id,
    ]);
    $ownerThread = ownerThread($a['owner']);

    app(OwnerContext::class)->activate([(string) $a['owner']->id]);

    // The guest thread's owner_id is NULL, so `owner_id IN (…)` excludes it — nobody wrote that predicate.
    $seen = MessageThread::query()->pluck('id')->all();
    expect($seen)->toBe([(string) $ownerThread->id])
        ->and($seen)->not->toContain((string) $guestThread->id);

    // And it is genuinely a 404 through the portal, not merely absent from a list.
    $this->actingAs($a['user'])->get('/owner/messages/'.$guestThread->id)->assertNotFound();
});

// ── 3. CROSS-OWNER THREAD → 404 (not a render, not a 403 that leaks its existence) ───────────────
it('404s a cross-owner thread', function () {
    $s = seedScenario(2);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $b = inboxOwner($s, 'Bruno Keller', 1);

    $threadB = ownerThread($b['owner'], 'Bruno private matter');

    $response = $this->actingAs($a['user'])->get('/owner/messages/'.$threadB->id);

    $response->assertNotFound();
    $response->assertDontSee('Bruno private matter');
});

// ── 4. FAIL-CLOSED — a portal user linked to NO owner sees nothing, never everything ─────────────
it('shows nothing to a portal user who is linked to no owner', function () {
    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    ownerThread($a['owner']);

    // OwnerContext ACTIVE but EMPTY → `1 = 0`. The scope must never fall open to all owners.
    app(OwnerContext::class)->activate([]);

    expect(MessageThread::query()->count())->toBe(0);
});

// ── 5. THE OPERATOR STILL SEES EVERYTHING — the reply loop must close ────────────────────────────
it('leaves the operator able to see and reply to every thread', function () {
    $s = seedScenario(2);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    $b = inboxOwner($s, 'Bruno Keller', 1);
    $guest = Guest::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

    ownerThread($a['owner']);
    ownerThread($b['owner']);
    app(InboxService::class)->startThread(['channel' => 'manual', 'guest_id' => $guest->id]);

    // OwnerContext INACTIVE (the operator app, CLI, queue) → no constraint at all. If this ever narrowed,
    // owners could message into a void: nobody on the operator side would see it.
    expect(app(OwnerContext::class)->active())->toBeFalse()
        ->and(MessageThread::query()->count())->toBe(3);
});

// ── 6. TENANT ISOLATION STILL COMPOSES — owner scope is a SECOND boundary, not a replacement ─────
it('never shows another organisation’s owner thread, even to a same-named owner', function () {
    $foreign = seedScenario(1);
    $foreignOwner = inboxOwner($foreign, 'Amara Okafor', 0);
    $foreignThread = ownerThread($foreignOwner['owner'], 'FOREIGN ORG SECRET');

    $s = seedScenario(1);
    $a = inboxOwner($s, 'Amara Okafor', 0);
    ownerThread($a['owner']);

    // The tenant scope constrains organization_id; the owner scope constrains owner_id. Both apply.
    $this->actingAs($a['user'])->get('/owner/messages')
        ->assertOk()
        ->assertDontSee('FOREIGN ORG SECRET');

    $this->actingAs($a['user'])->get('/owner/messages/'.$foreignThread->id)->assertNotFound();
});
