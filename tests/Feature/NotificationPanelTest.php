<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use App\Support\Notifications\NotificationFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

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
