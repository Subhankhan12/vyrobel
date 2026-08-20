<?php

/*
 * D29-5 + DO6-4 — PER-NOTIFICATION DISMISS, BOTH REALMS (2026-08-15).
 *
 * ⚠️ THE LOAD-BEARING RISK IS CONFLATION, NOT THE FEATURE. Two tables now carry a `dismissed_at`:
 *   • `notifications.dismissed_at`            — this feature: take a row off MY feed.
 *   • `owner_attention_states.dismissed_at`   — a DIFFERENT ENTITY: the owner's "needs attention" SIGNAL,
 *                                                keyed by owner_id + attention_key, written by
 *                                                `/owner/attention/dismiss`.
 * Same column name, same word in the UI, unrelated meanings. Dismissing a notification must never clear an
 * attention signal and vice versa — asserted explicitly below, because this is the kind of thing that works
 * by accident until someone "tidies up" the two into one store.
 *
 * ⚠️ DISMISS IS NOT MARK-READ. Read = "I have seen this", the row STAYS. Dismiss = "take this off my list",
 * the row LEAVES. Separate columns, and they coexist: read-and-dismissed, or dismissed-while-unread. Had this
 * overloaded `read_at`, marking something read would have silently hidden it.
 *
 * ⚠️ ONE MECHANISM, BOTH REALMS. `notifications` is a single polymorphic table and `NotificationFeed` is
 * already the ONE builder behind the operator page, the owner page AND the top-bar bell panel — so the filter
 * lives there once and every surface inherits it. A dismiss that hid a row on one surface but not another
 * would be worse than no dismiss at all.
 *
 * ⚠️ SELF-ONLY BY CONSTRUCTION, like the mark-read it parallels: the write goes through
 * `$request->user()->notifications()`, so another user's id does not resolve. Verified by RAW QUERY, not
 * through the same scoping it is testing (§N.18.1 / D24-2's lesson).
 */

use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerAttentionState;
use App\Models\User;
use App\Support\Notifications\NotificationFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/** ⚠️ Prefixed — a top-level function in a Pest file is GLOBAL and has collided in this suite before. */
function d295Notify(User $user, string $title, ?string $readAt = null): string
{
    $id = (string) Str::uuid();

    DB::table('notifications')->insert([
        'id' => $id,
        'type' => 'App\\Notifications\\Operator\\TestNotification',
        // ⚠️ THE MORPH ALIAS, not the FQCN. The project enforces a morph map (`'user' => User::class`), so a
        // row written with the class name is invisible to `$user->notifications()` — it inserts cleanly and
        // then simply never appears, which looks exactly like the feature failing. Confirmed against the real
        // demo rows (238 of them, all `user`) rather than guessed.
        'notifiable_type' => 'user',
        'notifiable_id' => $user->getKey(),
        'data' => json_encode(['title' => $title, 'context' => 'A test notification', 'category' => 'general']),
        'read_at' => $readAt,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

// ── THE FEATURE, IN BOTH REALMS ───────────────────────────────────────────────────────────────────

it('⚠️ D29-5 — dismissing removes the notification from the OPERATOR feed; the others stay', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');

    $doomed = d295Notify($user, 'Dismiss me please');
    d295Notify($user, 'Keep me visible');

    // ⚠️ NON-VACUITY: both are on the feed BEFORE the dismiss, so the assertion after it means something.
    $this->actingAs($user)->get('/app/notifications')->assertOk()
        ->assertSee('Dismiss me please')->assertSee('Keep me visible');

    $this->actingAs($user)->post(route('notifications.dismiss', $doomed))->assertRedirect();

    $this->actingAs($user)->get('/app/notifications')->assertOk()
        ->assertDontSee('Dismiss me please')
        ->assertSee('Keep me visible');
});

it('⚠️ DO6-4 — dismissing removes the notification from the OWNER feed too (the SAME mechanism)', function () {
    $s = seedScenario(1);
    $user = User::factory()->create(['status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    Owner::factory()->create(['user_id' => $user->id]);
    grantBackOfficeRole($user, $s['org'], 'External Owner');

    $doomed = d295Notify($user, 'Owner dismiss me');
    d295Notify($user, 'Owner keep me');

    $this->actingAs($user)->get('/owner/notifications')->assertOk()
        ->assertSee('Owner dismiss me')->assertSee('Owner keep me');

    $this->actingAs($user)->post(route('notifications.dismiss', $doomed))->assertRedirect();

    $this->actingAs($user)->get('/owner/notifications')->assertOk()
        ->assertDontSee('Owner dismiss me')
        ->assertSee('Owner keep me');
});

// ── DISMISS ≠ MARK-READ ───────────────────────────────────────────────────────────────────────────

it('⚠️ dismiss ≠ mark-read, in BOTH directions', function () {
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Rosa'], 'Hotel Manager');

    // (a) A READ notification still SHOWS — marking read must not hide anything.
    $read = d295Notify($user, 'Read but visible', now()->toDateTimeString());
    $this->actingAs($user)->get('/app/notifications')->assertOk()->assertSee('Read but visible');
    expect(DB::table('notifications')->where('id', $read)->value('dismissed_at'))->toBeNull();

    // (b) A DISMISSED notification is gone — and is NOT merely marked read. ⚠️ This is the assertion that
    // fails if `read_at` were overloaded for dismiss: the row would come back as "read" instead of hidden.
    $dismissed = d295Notify($user, 'Dismissed while unread');
    $this->actingAs($user)->post(route('notifications.dismiss', $dismissed))->assertRedirect();

    $row = DB::table('notifications')->where('id', $dismissed)->first();
    expect($row->dismissed_at)->not->toBeNull()
        ->and($row->read_at)->toBeNull();          // dismissed WITHOUT being marked read — they are separate

    $this->actingAs($user)->get('/app/notifications')->assertOk()->assertDontSee('Dismissed while unread');
});

it('⚠️ a dismissed row stops counting toward the unread badge', function () {
    // A dismissed-but-unread row would otherwise keep the bell lit, pointing at something the user has
    // explicitly taken off their list.
    $s = seedScenario(1);
    $user = operatorUser($s, ['name' => 'Rosa'], 'Hotel Manager');

    $a = d295Notify($user, 'One');
    d295Notify($user, 'Two');

    expect(NotificationFeed::forUser($user->fresh())['unread'])->toBe(2);
    $this->actingAs($user)->post(route('notifications.dismiss', $a))->assertRedirect();
    expect(NotificationFeed::forUser($user->fresh())['unread'])->toBe(1);

    // And the bell PANEL agrees with the page — one builder, one rule.
    $panel = NotificationFeed::recentForUser($user->fresh());
    expect($panel['unread'])->toBe(1)
        ->and(collect($panel['items'])->pluck('title'))->not->toContain('One');
});

// ── ⚠️ THE ANTI-CONFLATION CHECK — THE LOAD-BEARING ONE ───────────────────────────────────────────

it('⚠️ THE ANTI-CONFLATION — dismissing a NOTIFICATION leaves the ATTENTION-STATE store untouched', function () {
    /*
     * ⚠️ TWO TABLES, ONE WORD. `owner_attention_states` is the owner's "needs attention" SIGNAL and carries
     * its own `dismissed_at`, written by `/owner/attention/dismiss`. This test proves the two never cross:
     * a notification dismiss must not silence an attention signal, which would quietly remove a real
     * to-do item from an owner's dashboard because they tidied their inbox.
     */
    $s = seedScenario(1);
    $user = User::factory()->create(['status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id]);
    grantBackOfficeRole($user, $s['org'], 'External Owner');

    // A LIVE attention signal — not dismissed.
    $state = OwnerAttentionState::withoutGlobalScopes()->create([
        'organization_id' => $s['org']->id,
        'owner_id' => $owner->getKey(),
        'attention_key' => 'expense:pending',
    ]);
    expect(DB::table('owner_attention_states')->where('id', $state->getKey())->value('dismissed_at'))->toBeNull();

    $notification = d295Notify($user, 'Unrelated notification');
    $this->actingAs($user)->post(route('notifications.dismiss', $notification))->assertRedirect();

    // ⚠️ THE NOTIFICATION IS DISMISSED…
    expect(DB::table('notifications')->where('id', $notification)->value('dismissed_at'))->not->toBeNull()
        // …AND THE ATTENTION SIGNAL IS UNTOUCHED. Raw query — a check through the same scoping would be weaker.
        ->and(DB::table('owner_attention_states')->where('id', $state->getKey())->value('dismissed_at'))->toBeNull()
        ->and((int) DB::table('owner_attention_states')->whereNotNull('dismissed_at')->count())->toBe(0);
});

it('⚠️ THE ANTI-CONFLATION, the other way — the two are separate ROUTES on separate CONTROLLERS', function () {
    // Pinned in source so a later "consolidate the dismisses" refactor has to argue with a test.
    $routes = (string) file_get_contents(base_path('routes/web.php'));

    expect($routes)->toContain("[NotificationController::class, 'dismiss']")
        ->toContain("[OwnerAttentionController::class, 'dismiss']");

    // The notification controller must never reach the attention store.
    // ⚠️ TOKENISED, not grepped. A first draft banned the word "attention" outright and failed on CORRECT
    // code — the controller's own docblock explains the anti-conflation, which is exactly the comment you
    // WANT there. Scanning executable tokens means the guard sees what runs, not what is written about it.
    $code = '';
    foreach (token_get_all((string) file_get_contents(app_path('Http/Controllers/NotificationController.php'))) as $token) {
        if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }
        $code .= is_array($token) ? $token[1] : $token;
    }

    expect($code)->toContain("'dismissed_at' => now()")
        ->and($code)->not->toContain('OwnerAttentionState')
        ->and($code)->not->toContain('owner_attention_states')
        ->and(strtolower($code))->not->toContain('attention');
});

// ── RBAC ──────────────────────────────────────────────────────────────────────────────────────────

it('⚠️ RBAC — a user CANNOT dismiss another user’s notification', function () {
    $s = seedScenario(1);
    $rosa = operatorUser($s, ['name' => 'Rosa', 'email' => 'rosa@example.test'], 'Hotel Manager');
    $frank = operatorUser($s, ['name' => 'Frank', 'email' => 'frank@example.test'], 'Front Desk');

    $franks = d295Notify($frank, "Frank's private notification");

    $this->actingAs($rosa)->post(route('notifications.dismiss', $franks))->assertNotFound();

    // ⚠️ RAW QUERY — verifying through the same per-user relation that enforces the rule would be vacuous.
    expect(DB::table('notifications')->where('id', $franks)->value('dismissed_at'))->toBeNull();

    // And Frank can still dismiss his own — the scope is not just "deny everything".
    $this->actingAs($frank)->post(route('notifications.dismiss', $franks))->assertRedirect();
    expect(DB::table('notifications')->where('id', $franks)->value('dismissed_at'))->not->toBeNull();
});

it('⚠️ a user never SEES another user’s notification, dismissed or not', function () {
    $s = seedScenario(1);
    $rosa = operatorUser($s, ['name' => 'Rosa', 'email' => 'rosa@example.test'], 'Hotel Manager');
    $frank = operatorUser($s, ['name' => 'Frank', 'email' => 'frank@example.test'], 'Front Desk');
    d295Notify($frank, "Frank's private notification");

    $this->actingAs($rosa)->get('/app/notifications')->assertOk()
        ->assertDontSee("Frank's private notification");
});

// ── THE SHARED MECHANISM ──────────────────────────────────────────────────────────────────────────

it('⚠️ ONE mechanism serves both realms — one route, one controller, one filter (§N.8f)', function () {
    $routes = (string) file_get_contents(base_path('routes/web.php'));
    $feed = (string) file_get_contents(app_path('Support/Notifications/NotificationFeed.php'));

    // ⚠️ ONE CONTROLLER ACTION, now exposed to THREE realms (guest notifications · GATE 1).
    //
    // This asserted exactly ONE route registration, which was the right proxy while both realms shared the
    // `web` guard: a second line then really would have meant a second mechanism. The guest realm reuses the
    // SAME `NotificationController::dismiss` — no forked controller, no second filter, no second builder —
    // but it MUST register its own route, because it sits behind a DIFFERENT guard (`auth:guest`) under a
    // different URI prefix, and one registration cannot carry two guards.
    //
    // ⚠️ So the RULE is unchanged and the PROXY was too narrow: what matters is one mechanism, not one line.
    // Asserted directly below — both registrations name the same action, and no guest controller exists.
    expect(substr_count($routes, "[NotificationController::class, 'dismiss']"))->toBe(2);
    expect(file_exists(app_path('Http/Controllers/Guest/GuestNotificationsController.php')))->toBeFalse();

    // ...and exactly one implementation of the dismiss write, in that one controller.
    $controller = (string) file_get_contents(app_path('Http/Controllers/NotificationController.php'));
    expect(substr_count($controller, "forceFill(['dismissed_at' => now()])"))->toBe(1);

    // Both feed reads filter dismissed, in the ONE shared builder.
    expect(substr_count($feed, "whereNull('dismissed_at')"))->toBeGreaterThanOrEqual(3);

    // Both realm views call that one route.
    foreach (['operator', 'owner'] as $realm) {
        $view = (string) file_get_contents(resource_path("views/livewire/{$realm}/notifications.blade.php"));
        expect($view)->toContain("route('notifications.dismiss', \$item['id'])");
    }
});

it('⚠️ mark-read is UNCHANGED — this pass added an act, it did not alter one', function () {
    $controller = (string) file_get_contents(app_path('Http/Controllers/NotificationController.php'));

    expect($controller)->toContain('$request->user()->notifications()->findOrFail($notification)->markAsRead();')
        ->and($controller)->toContain('$request->user()->unreadNotifications->markAsRead();');
});
