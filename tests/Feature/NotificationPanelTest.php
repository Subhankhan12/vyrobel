<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use App\Support\Notifications\NotificationFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;

uses(RefreshDatabase::class);

// NOTIFICATION PANEL (Phase-3 rebuild) — the top-bar bell now opens a PANEL (popover) instead of navigating; only
// "Open all notifications →" reaches the full page. Proves: the shared capped read (recentForUser) is self-only,
// newest-first, and bounded; the panel is server-rendered on ordinary pages (bell → panel, both shells) with the
// user's REAL recent notifications + honest empty state. Behaviour reuses the EXISTING self-only mark-read routes.

/** Create a database notification directly on a user (controlled count / recency / read-state / category). */
if (! function_exists('makeNotif')) {
    function makeNotif(User $user, array $data, bool $read = false, int $minutesAgo = 0): void
    {
        $when = now()->subMinutes($minutesAgo);
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\Test\\Fake',
            'data' => $data,
            'read_at' => $read ? $when : null,
            'created_at' => $when,
            'updated_at' => $when,
        ]);
    }
}

// ── 1. recentForUser — capped, newest-first, correct unread count ────────────────────────────────
it('returns the most recent notifications capped to the limit, newest first, with the unread count', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Mara Manager'], 'Hotel Manager');

    // 8 notifications, newest = smallest minutesAgo; 3 read, 5 unread.
    for ($i = 0; $i < 8; $i++) {
        makeNotif($user, ['title' => "Event {$i}", 'context' => 'x', 'url' => '/app', 'category' => 'front_desk'], read: $i >= 5, minutesAgo: $i);
    }

    $feed = NotificationFeed::recentForUser($user->fresh(), 6);

    expect($feed['items'])->toHaveCount(6)                       // capped at 6, not all 8
        ->and($feed['items'][0]['title'])->toBe('Event 0')      // newest first
        ->and($feed['items'][5]['title'])->toBe('Event 5')
        ->and($feed['unread'])->toBe(5);                        // real unread count across ALL, not just the page
});

// ── 2. recentForUser — SELF-ONLY (#28/#26): never another user's rows ─────────────────────────────
it('reads only the authenticated user’s own notifications, never another user’s', function () {
    $s = seedScenario(1);
    $a = operatorUser($s, ['name' => 'Ada'], 'Hotel Manager');
    $b = operatorUser($s, ['name' => 'Ben'], 'Hotel Manager');

    makeNotif($a, ['title' => 'For Ada', 'context' => '', 'url' => '/app', 'category' => 'front_desk']);
    makeNotif($b, ['title' => 'For Ben', 'context' => '', 'url' => '/app', 'category' => 'front_desk']);

    $feedA = NotificationFeed::recentForUser($a->fresh());

    expect($feedA['items'])->toHaveCount(1)
        ->and($feedA['items'][0]['title'])->toBe('For Ada')
        ->and(collect($feedA['items'])->pluck('title'))->not->toContain('For Ben');
});

// ── 3. The operator bell → PANEL: rendered on an ordinary page, real rows + the "Open all" link ───
it('renders the notification panel in the operator shell with the user’s real recent notifications', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Priya'], 'Property Owner');
    makeNotif($user, ['title' => 'New booking · R-ABC', 'context' => 'Direct', 'url' => route('app.reservations'), 'category' => 'front_desk']);

    $this->actingAs($user)->get('/app/dashboard')
        ->assertOk()
        ->assertSee('New booking · R-ABC')            // the panel is server-rendered with the real row
        ->assertSee('Open all notifications');        // the ONLY control that navigates to the full page
});

// ── 4. The owner bell → PANEL: self-only owner rows in the owner shell ────────────────────────────
it('renders the notification panel in the owner shell with the owner’s own notifications', function () {
    $s = seedScenario(1);
    $owner = Owner::factory()->create(['name' => 'Amara', 'status' => 'active']);
    $user = User::factory()->create(['name' => 'Amara']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner->update(['user_id' => $user->id]);

    makeNotif($user, ['title' => 'Your statement is ready', 'context' => 'net payable €885.60', 'url' => '/owner', 'category' => 'finance']);

    $this->actingAs($user)->get('/owner')
        ->assertOk()
        ->assertSee('Your statement is ready')
        ->assertSee('Open all notifications');
});

// ── 5. Honest empty state — no notifications → "All caught up" in the panel ───────────────────────
it('shows the honest empty state in the panel when there are no notifications', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Quiet'], 'Property Owner');

    $this->actingAs($user)->get('/app/dashboard')
        ->assertOk()
        ->assertSee('All caught up — no notifications.');
});

// ── 6. WCAG 1.4.1 — the UNREAD state is never carried by colour alone (operator GATE-29's still-open item) ──
// P29 fixed the operator notifications PAGE but logged the same defect as STILL OPEN on two surfaces: the owner
// feed page and the SHARED bell partial (which renders in BOTH shells). Both are closed now, and this pins them.
//
// ⚠️ What the guard asserts is the CLAIM, not the styling: an unread row must carry the WORD "Unread" in
// visually-hidden text, and must NOT fall back to a bare `title="Unread"` on a non-interactive span — a title
// there is not reliably announced, which is exactly what P29 found. Mutation-proven: restoring the colour-only
// dot turns this red.
it('never conveys the unread state by colour alone — owner feed page and the shared bell', function () {
    $s = seedScenario(1);
    $owner = Owner::factory()->create(['name' => 'Amara', 'status' => 'active']);
    $user = User::factory()->create(['name' => 'Amara']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner->update(['user_id' => $user->id]);

    // A real UNREAD notification — an empty fixture would make every assertion below vacuous.
    makeNotif($user, ['title' => 'Your statement is ready', 'context' => 'net payable €885.60', 'url' => '/owner', 'category' => 'finance']);

    $feed = $this->actingAs($user)->get('/owner/notifications')->assertOk();
    $feedHtml = $feed->getContent();

    // The owner FEED page: the state is in words, and the mark-read control names its notification.
    expect($feedHtml)->toContain('<span class="vy-sr-only">Unread</span>')
        ->and($feedHtml)->toContain('Mark “Your statement is ready” as read');

    // The SHARED bell renders on every owner page — same rule, one definition.
    $shellHtml = $this->actingAs($user)->get('/owner')->assertOk()->getContent();
    expect($shellHtml)->toContain('<span class="vy-sr-only">Unread</span>');

    // ⚠️ And the colour-only form must be GONE from both, not merely supplemented.
    Assert::assertStringNotContainsString('title="Unread"', $feedHtml, 'the owner feed still marks unread with a bare title attribute (colour-only, WCAG 1.4.1)');
    Assert::assertStringNotContainsString('title="Unread"', $shellHtml, 'the shared bell still marks unread with a bare title attribute (colour-only, WCAG 1.4.1)');

    // The owner shell must also DEFINE .vy-sr-only, or the shared partial's text would render VISIBLY here.
    // ⚠️ Assert the CSS RULE, not the string '.vy-sr-only' — the class ATTRIBUTE appears in the bell markup
    // regardless, so the loose form passed even with the rule deleted (caught by mutation, tightened here).
    expect($shellHtml)->toContain('.vy-sr-only { position: absolute;');
});
