<?php

declare(strict_types=1);

use App\Models\Guest;
use App\Models\MessageThread;
use App\Models\Owner;
use App\Notifications\Operator\InboxMessageNotification;
use App\Services\InboxService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

uses(DatabaseTransactions::class);

// ⚠️⚠️ THE GUESTS SIDE, LIVE (2026-10-06 — his "notification icon … notification sound … Ajax based runtime";
// `docs/TEAM-CHAT-DESIGN.md` §6). 📖 Read first: when a guest or an owner wrote, NO ONE on the staff was notified
// (`inbox.message_received` had no handler), and the Inbox changed only on a reload. Each test failed first.

/** A workspace with a guest's Direct conversation, a manager and a front-desk agent (who may read it) and a housekeeper (who may not). */
function ilWorld(): array
{
    $s = seedScenario(1);
    $s['manager'] = operatorUser($s, ['name' => 'Mara Manager'], 'Hotel Manager');
    $s['desk'] = operatorUser($s, ['name' => 'Dan Desk'], 'Front Desk');
    $s['hk'] = operatorUser($s, ['name' => 'Hana Housekeeping'], 'Housekeeping');
    Guest::query()->whereKey($s['guest']->id)->update(['first_name' => 'Ines', 'last_name' => 'Ibarra']);
    $s['thread'] = app(InboxService::class)->startThread(['channel' => 'manual', 'guest_id' => (string) $s['guest']->id]);

    return $s;
}

it('⚠️⚠️ rings the bell for the staff who may read the conversation when a guest writes — never a housekeeper', function () {
    Mail::fake();
    Event::fake([NotificationSending::class]);   // ⚠️ the mail fake cannot see a notification's mail channel; this can
    $s = ilWorld();

    app(InboxService::class)->postGuestMessage($s['thread'], 'Is breakfast included? My door code is 5521.');

    foreach (['manager', 'desk'] as $who) {
        $rows = $s[$who]->unreadNotifications()->where('type', InboxMessageNotification::class)->get();
        expect($rows)->toHaveCount(1)
            ->and($rows[0]->data['title'])->toBe('New message from Ines Ibarra')
            ->and($rows[0]->data['url'])->toBe(route('app.inbox', ['c' => $s['thread']->id], false))
            ->and($rows[0]->data['category'])->toBe('messages')
            ->and(json_encode($rows[0]->data))->not->toContain('breakfast')->not->toContain('5521');   // names only (#28)
    }
    expect($s['hk']->unreadNotifications()->count())->toBe(0);
    Mail::assertNothingOutgoing();
    Event::assertDispatched(NotificationSending::class, fn (NotificationSending $e): bool => $e->channel === 'database');
    Event::assertNotDispatched(NotificationSending::class, fn (NotificationSending $e): bool => $e->channel === 'mail');
});

it('⚠️ rings only the assigned person when the conversation is assigned', function () {
    $s = ilWorld();
    app(InboxService::class)->assign($s['thread'], (string) $s['desk']->id);
    $s['desk']->notifications()->delete();   // the assignment's own notice is another story

    app(InboxService::class)->postGuestMessage($s['thread'], 'Hello?');

    expect($s['desk']->unreadNotifications()->where('type', InboxMessageNotification::class)->count())->toBe(1)
        ->and($s['manager']->unreadNotifications()->where('type', InboxMessageNotification::class)->count())->toBe(0);
});

it('⚠️ keeps one entry per conversation while it is unread — counting — and clears it for everyone when anyone opens it', function () {
    $s = ilWorld();
    app(InboxService::class)->postGuestMessage($s['thread'], 'One');
    app(InboxService::class)->postGuestMessage($s['thread'], 'Two');

    $rows = $s['manager']->unreadNotifications()->where('type', InboxMessageNotification::class)->get();
    expect($rows)->toHaveCount(1)->and($rows[0]->data['title'])->toBe('2 new messages from Ines Ibarra');

    // the conversation's unread counter is shared by the staff — so is its bell entry
    $this->actingAs($s['desk'])->get(route('app.inbox', ['c' => $s['thread']->id]))->assertOk();
    expect($s['manager']->fresh()->unreadNotifications()->where('type', InboxMessageNotification::class)->count())->toBe(0)
        ->and($s['desk']->fresh()->unreadNotifications()->where('type', InboxMessageNotification::class)->count())->toBe(0);
});

it('names the owner when a unit owner writes', function () {
    $s = ilWorld();
    $ownerUser = ownerRoleUser($s);
    $owner = Owner::query()->where('user_id', $ownerUser->id)->firstOrFail();
    $thread = app(InboxService::class)->startThread(['channel' => 'manual', 'owner_id' => (string) $owner->id]);

    app(InboxService::class)->postOwnerMessage($thread, 'Can I block next weekend?', $ownerUser);

    // the Inbox's own name for the conversation (ThreadName) — the owner record's name
    expect($s['manager']->unreadNotifications()->where('type', InboxMessageNotification::class)->first()->data['title'])
        ->toBe('New message from '.$owner->name);
});

it('⚠️⚠️ tells the open Inbox when something changed — a small stamp the page asks every few seconds', function () {
    $s = ilWorld();

    $before = $this->actingAs($s['desk'])->getJson(route('app.inbox.live', ['c' => $s['thread']->id]))->assertOk()->json('stamp');
    $same = $this->actingAs($s['desk'])->getJson(route('app.inbox.live', ['c' => $s['thread']->id]))->json('stamp');
    app(InboxService::class)->postGuestMessage($s['thread'], 'New!');
    $after = $this->actingAs($s['desk'])->getJson(route('app.inbox.live', ['c' => $s['thread']->id]))->json('stamp');

    expect($same)->toBe($before)->and($after)->not->toBe($before);
    $this->actingAs($s['hk'])->getJson(route('app.inbox.live'))->assertForbidden();   // the Inbox's own permission
});

it('⚠️ marks the parts of the Inbox the page refreshes in place — never the message being written', function () {
    $s = ilWorld();
    app(InboxService::class)->postGuestMessage($s['thread'], 'Hi');

    $html = (string) $this->actingAs($s['desk'])->get(route('app.inbox', ['c' => $s['thread']->id]))->assertOk()->getContent();
    expect($html)->toContain('data-ibx-live="stats"')->toContain('data-ibx-live="rail"')
        ->toContain('"live":"'.str_replace('/', '\\/', route('app.inbox.live')).'"')
        // the refreshed part of a conversation is its messages — the composer (a draft being written) is never replaced
        ->toMatch('~<div class="vy-thread-scroll"[^>]*data-ibx-live="thread"~')
        ->not->toMatch('~<form class="vy-composer-form"[^>]*data-ibx-live~');
});

// ── the independent review's findings (2026-10-06) — each failed first ──────────────────────────────────────────────

it('⚠️⚠️ never marks a conversation read by refreshing it — reading is the person\'s own act, posted (review 2)', function () {
    $s = ilWorld();
    app(InboxService::class)->postGuestMessage($s['thread'], 'Hello');

    // the page's own live refresh: the same conversation, flagged live
    $this->actingAs($s['desk'])->get(route('app.inbox', ['c' => $s['thread']->id, 'live' => 1]))->assertOk();
    expect((int) MessageThread::query()->whereKey($s['thread']->id)->value('unread_count'))->toBe(1)
        ->and($s['manager']->fresh()->unreadNotifications()->where('type', InboxMessageNotification::class)->count())->toBe(1);

    // reading it, on purpose
    $this->actingAs($s['desk'])->postJson(route('app.inbox.read', $s['thread']->id))->assertOk();
    expect((int) MessageThread::query()->whereKey($s['thread']->id)->value('unread_count'))->toBe(0)
        ->and($s['manager']->fresh()->unreadNotifications()->where('type', InboxMessageNotification::class)->count())->toBe(0);
    $this->actingAs($s['hk'])->postJson(route('app.inbox.read', $s['thread']->id))->assertForbidden();
});

it('⚠️⚠️ refreshes the conversation the page shows — never whichever became newest — and says which it is (review 3)', function () {
    $s = ilWorld();
    app(InboxService::class)->postGuestMessage($s['thread'], 'First guest');

    $html = (string) $this->actingAs($s['desk'])->get(route('app.inbox'))->assertOk()->getContent();   // no ?c= — the default
    expect($html)->toContain('"c":"'.$s['thread']->id.'"')->toContain('"opened":false')   // a preview is never read for you
        ->toMatch('~<div class="vy-thread-scroll" data-ibx-live="thread" data-thread-id="'.$s['thread']->id.'" data-unread="1"~')
        ->toMatch('~<header class="vy-thread-head" data-ibx-live="head"~');
    expect((string) $this->actingAs($s['desk'])->get(route('app.inbox', ['c' => $s['thread']->id]))->getContent())->toContain('"opened":true');
    $script = (string) file_get_contents(resource_path('views/livewire/operator/operator-inbox.blade.php'));
    expect($script)->toContain("if (cfg.c) page.searchParams.set('c', cfg.c);")->toContain("page.searchParams.set('live', '1');")
        ->toContain('there.dataset.threadId !== cfg.c');
});

it('⚠️⚠️ erases the staff\'s bell entries that name an erased guest (GDPR — review 12)', function () {
    $s = ilWorld();
    app(InboxService::class)->assign($s['thread'], (string) $s['desk']->id);
    app(InboxService::class)->postGuestMessage($s['thread'], 'Hi');
    expect(DatabaseNotification::query()->where('data', 'like', '%'.$s['thread']->id.'%')->count())->toBeGreaterThan(0);

    app(TenantContext::class)->set($s['org']->id);
    Guest::query()->whereKey($s['guest']->id)->sole()->anonymize();

    expect(DatabaseNotification::query()->where('data', 'like', '%'.$s['thread']->id.'%')->count())->toBe(0);
});
