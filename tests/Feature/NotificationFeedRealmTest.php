<?php

use App\Models\User;
use App\Support\Notifications\NotificationFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;

uses(RefreshDatabase::class);

// NOTIFICATION FEED — THE SEALED-REALM REGRESSION PIN (guest notifications · GATE 1).
//
// ⚠️ WHY THIS FILE EXISTS. `NotificationFeed` is the ONE shared feed builder behind BOTH sealed realms (the
// operator feed screen, the owner feed screen, and the top-bar bell panel in both shells). GATE 1 WIDENED its
// two public signatures from `User` to `User|Guest` so the guest realm could reuse it instead of forking a
// parallel builder (§N.8f). That is exactly the shared-file change owner Fix-5 warned about: additive by
// construction, but depended on by surfaces that are already sealed.
//
// Nothing pinned the pre-widening behaviour tightly enough for a break to red, so this pins it. These tests
// describe the `User` path ONLY — the behaviour that must be BYTE-IDENTICAL before and after the widening.
// The guest path is proven separately in GuestNotificationsGate1Test; keeping them apart is the point, so a
// regression here can never be mistaken for a guest-feature failure.

/** A database notification on any notifiable (controlled category / read-state / recency). */
if (! function_exists('pinNotif')) {
    function pinNotif(object $notifiable, array $data, bool $read = false, int $minutesAgo = 0, ?string $dismissedAt = null): void
    {
        $when = now()->subMinutes($minutesAgo);
        $notifiable->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\Test\\Fake',
            'data' => $data,
            'read_at' => $read ? $when : null,
            'dismissed_at' => $dismissedAt,
            'created_at' => $when,
            'updated_at' => $when,
        ]);
    }
}

// ── The full feed page (forUser) still behaves exactly as before, for a User ────────────────────────
it('serves an operator User the same paged feed, counts and categories as before the widening', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Mara Manager'], 'Hotel Manager');

    pinNotif($user, ['title' => 'Newest', 'context' => 'c', 'url' => '/app', 'category' => 'front_desk'], read: false, minutesAgo: 0);
    pinNotif($user, ['title' => 'Middle', 'context' => 'c', 'url' => '/app', 'category' => 'finance'], read: true, minutesAgo: 5);
    pinNotif($user, ['title' => 'Oldest', 'context' => 'c', 'url' => '/app', 'category' => 'finance'], read: false, minutesAgo: 9);
    // Dismissed rows leave the feed AND the badge — the D29-5 rule the one builder owns.
    pinNotif($user, ['title' => 'Gone', 'context' => 'c', 'url' => '/app', 'category' => 'maintenance'], read: false, minutesAgo: 2, dismissedAt: (string) now());

    $feed = NotificationFeed::forUser($user->fresh());

    expect(array_column($feed['items'], 'title'))->toBe(['Newest', 'Middle', 'Oldest']);   // newest-first
    expect($feed['unread'])->toBe(2);                                                       // dismissed excluded
    expect($feed['categories'])->toBe(['finance', 'front_desk']);                            // sorted, dismissed excluded

    // The mapped row shape both surfaces render — pinned key-for-key.
    expect(array_keys($feed['items'][0]))->toBe(['id', 'title', 'context', 'url', 'category', 'read', 'when']);
    expect($feed['items'][0]['read'])->toBeFalse();
    expect($feed['items'][1]['read'])->toBeTrue();
});

// ── The category filter still narrows, for a User ───────────────────────────────────────────────────
it('still filters an operator User feed by category and by unread', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Mara Manager'], 'Hotel Manager');

    pinNotif($user, ['title' => 'A', 'context' => 'c', 'url' => '/app', 'category' => 'finance'], read: false, minutesAgo: 0);
    pinNotif($user, ['title' => 'B', 'context' => 'c', 'url' => '/app', 'category' => 'front_desk'], read: true, minutesAgo: 1);

    expect(array_column(NotificationFeed::forUser($user->fresh(), 'finance')['items'], 'title'))->toBe(['A']);
    expect(array_column(NotificationFeed::forUser($user->fresh(), 'unread')['items'], 'title'))->toBe(['A']);
    expect(array_column(NotificationFeed::forUser($user->fresh(), 'all')['items'], 'title'))->toBe(['A', 'B']);
});

// ── The bell panel (recentForUser) still behaves exactly as before, for a User ──────────────────────
it('serves an owner User the same capped bell panel and unread count as before the widening', function () {
    $s = seedScenario(1);
    // ⚠️ `inboxOwner`, not `ownUnit` — ownUnit() creates an Owner with NO User, and the owner feed reads
    // through the owner's USER. The assertion below is kept: a fixture whose user is null would make every
    // expectation after it vacuously true.
    ['user' => $user] = inboxOwner($s, 'Olive Owner', 0);
    Assert::assertInstanceOf(User::class, $user, 'the owner fixture must carry a User — otherwise this pin is vacuous');

    for ($i = 0; $i < 8; $i++) {
        pinNotif($user, ['title' => "Event {$i}", 'context' => 'c', 'url' => '/owner', 'category' => 'performance'], read: $i >= 5, minutesAgo: $i);
    }
    pinNotif($user, ['title' => 'Dismissed', 'context' => 'c', 'url' => '/owner', 'category' => 'performance'], read: false, minutesAgo: 1, dismissedAt: (string) now());

    $panel = NotificationFeed::recentForUser($user->fresh(), 6);

    expect($panel['items'])->toHaveCount(6);                                    // capped
    expect(array_column($panel['items'], 'title'))->toBe(['Event 0', 'Event 1', 'Event 2', 'Event 3', 'Event 4', 'Event 5']);
    expect($panel['unread'])->toBe(5);                                          // whole-set, dismissed excluded
});

// ── Self-only, for a User — the property the widening must not have loosened ────────────────────────
it('still shows one User nothing belonging to another User', function () {
    $s = seedScenario(1);
    $mine = operatorUser($s, ['name' => 'Mine'], 'Hotel Manager');
    $theirs = operatorUser($s, ['name' => 'Theirs'], 'Hotel Manager');

    pinNotif($theirs, ['title' => 'Not yours', 'context' => 'c', 'url' => '/app', 'category' => 'finance']);

    expect(NotificationFeed::forUser($mine->fresh())['items'])->toBe([]);
    expect(NotificationFeed::forUser($mine->fresh())['unread'])->toBe(0);
    expect(NotificationFeed::recentForUser($mine->fresh())['items'])->toBe([]);
});
