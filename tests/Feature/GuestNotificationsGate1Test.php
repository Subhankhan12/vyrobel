<?php

use App\Jobs\SendBookingConfirmationEmail;
use App\Models\Guest;
use App\Notifications\Guest\GuestBookingConfirmedNotification;
use App\Services\NotificationPreferences;
use App\Support\Email\EmailTemplateResolver;
use App\Support\Guest\GuestName;
use App\Support\Notifications\NotificationFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// GUEST NOTIFICATIONS · GATE 1 — the foundation, proven on ONE type (G1: booking confirmed).
//
// The founder ruled all eight types; the discipline is to prove the pattern and de-risk the two flagged
// risks on one type before replicating. The two risks:
//   1. `NotificationFeed` was `User`-typed → WIDENED to `User|Guest` (not forked, §N.8f). The sealed-realm
//      side of that is pinned separately in NotificationFeedRealmTest; the guest side is pinned here.
//   2. ⚠️ `NotificationController` resolves its actor with `$request->user()`, which the scope pass claimed
//      was NULL for a guest. ⚠️ THAT CLAIM WAS WRONG, and a probe corrected it: Laravel's `Authenticate`
//      middleware calls `shouldUse($guard)` for the guard that succeeded, so inside an `auth:guest` route the
//      DEFAULT guard is `guest` and `$request->user()` returns the Guest. A forked guest controller was
//      written, then DELETED once the probe disproved its reason to exist — the shared controller is reused
//      (§N.8f), and `GuestNotificationsRouteGuardTest` pins the `auth:guest` middleware the reuse depends on.
//
// ⚠️ G1 IS ADDITIVE. `SendGuestTransactionalEmails` still sends BookingConfirmationMail on the same event;
// this notification is the IN-APP surface of that message, never a replacement, and never a second email.

/** The scenario guest, made reachable + claimable so it can authenticate on the guest guard. */
function g1Guest(array $s, string $email = 'ada@example.test'): Guest
{
    $s['guest']->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $g = $s['guest']->fresh();
    $g->password = 'guest-password-1234';
    $g->email_verified_at = now();
    $g->save();

    return $g->fresh();
}

// ── 1. THE DISPATCH — the guest gets the in-app row on the real event ──────────────────────────────
it('notifies the guest in-app when their own booking is confirmed', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    $s = seedScenario(1);
    $guest = g1Guest($s);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $rows = $guest->fresh()->notifications()->get();
    expect($rows)->toHaveCount(1);
    expect($rows[0]->type)->toBe(GuestBookingConfirmedNotification::class);
    expect($rows[0]->data['category'])->toBe('trips');
    // ⚠️ The url must be a page THIS guest can open — a notification is a pointer, and one aimed at a 403 is
    // a dead affordance. It is their own reservation, resolved from the reservation itself.
    expect($rows[0]->data['url'])->toBe('/guest/reservation/'.$reservation->getKey());
});

// ── 2. ADDITIVE — the email STILL fires; the notification did not replace it ────────────────────────
it('still sends the booking-confirmation email — the in-app row is additive, never a replacement', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    $s = seedScenario(1);
    $guest = g1Guest($s);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    // The email path is untouched: the same job, with the same payload, as before this feature existed.
    Bus::assertDispatched(
        SendBookingConfirmationEmail::class,
        fn (SendBookingConfirmationEmail $job): bool => $job->reservationId === (string) $reservation->getKey(),
    );
    // ...AND the in-app row exists. Both, not either.
    expect($guest->fresh()->notifications()->count())->toBe(1);
});

// ── 3. NOT A SECOND EMAIL — the load-bearing `email_default => false` ───────────────────────────────
it('never adds a second email for the guest row (in-app channel only)', function () {
    $s = seedScenario(1);
    $guest = g1Guest($s);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $channels = (new GuestBookingConfirmedNotification($reservation))->via($guest->fresh());

    expect($channels)->toContain('database');
    // ⚠️ If this ever contains 'mail', guests are being emailed TWICE for one event — BookingConfirmationMail
    // already covers the email channel. `allows()` falls back to the catalog default for a non-User
    // notifiable, so this asserts the catalog entry, not the notification class.
    expect($channels)->not->toContain('mail');
    expect(NotificationPreferences::CATALOG['guest_booking_confirmed']['email_default'])->toBeFalse();
});

// ── 4. THE WIDENED FEED SERVES THE GUEST (risk 1, guest side) ──────────────────────────────────────
it('serves the guest their own feed and unread count through the widened shared builder', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    $s = seedScenario(1);
    $guest = g1Guest($s);
    book($s, ['unit_id' => $s['units'][0]->id]);

    $feed = NotificationFeed::forUser($guest->fresh());
    expect($feed['items'])->toHaveCount(1);
    expect($feed['unread'])->toBe(1);
    expect($feed['categories'])->toBe(['trips']);
    // Same row shape as the operator/owner realms — it is literally the same builder.
    expect(array_keys($feed['items'][0]))->toBe(['id', 'title', 'context', 'url', 'category', 'read', 'when']);

    $panel = NotificationFeed::recentForUser($guest->fresh());
    expect($panel['items'])->toHaveCount(1);
    expect($panel['unread'])->toBe(1);
});

// ── 5. #28 — a guest NEVER sees another guest's notification ───────────────────────────────────────
it('never shows one guest another guest’s notification', function () {
    $s = seedScenario(1);
    $mine = g1Guest($s, 'ada@example.test');
    $theirs = Guest::create(['first_name' => 'Other', 'last_name' => 'Person', 'email' => 'other@example.test']);

    $theirs->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => GuestBookingConfirmedNotification::class,
        'data' => ['title' => 'Not yours', 'context' => 'c', 'url' => '/guest/trips', 'category' => 'trips'],
        'read_at' => null,
    ]);

    expect(NotificationFeed::forUser($mine->fresh())['items'])->toBe([]);
    expect(NotificationFeed::forUser($mine->fresh())['unread'])->toBe(0);
    expect(NotificationFeed::recentForUser($mine->fresh())['items'])->toBe([]);
});

// ── 5b. #28 — and cannot MARK another guest's notification either (the write side) ──────────────────
it('never lets one guest mark or dismiss another guest’s notification', function () {
    $s = seedScenario(1);
    $mine = g1Guest($s, 'ada@example.test');
    $theirs = Guest::create(['first_name' => 'Other', 'last_name' => 'Person', 'email' => 'other@example.test']);

    $foreign = $theirs->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => GuestBookingConfirmedNotification::class,
        'data' => ['title' => 'Not yours', 'context' => 'c', 'url' => '/guest/trips', 'category' => 'trips'],
        'read_at' => null,
    ]);

    // Naming another guest's id does not resolve — it is not in this guest's relation.
    $this->actingAs($mine, 'guest')->post('/guest/notifications/'.$foreign->id.'/read')->assertNotFound();
    $this->actingAs($mine, 'guest')->post('/guest/notifications/'.$foreign->id.'/dismiss')->assertNotFound();

    // ...and it is genuinely untouched, read back RAW rather than through the scope that just refused it
    // (a check must be independent of the thing it checks — §N.18.1).
    $raw = DB::table('notifications')->where('id', $foreign->id)->first();
    expect($raw->read_at)->toBeNull();
    expect($raw->dismissed_at)->toBeNull();
});

// ── 6. RISK 2 IS REAL — $request->user() is null for an authenticated guest ─────────────────────────
it('keeps the guest on its own guard — the web guard is empty for them', function () {
    $s = seedScenario(1);
    $guest = g1Guest($s);

    $this->actingAs($guest, 'guest');

    // The guest IS authenticated — on their own guard.
    expect(Auth::guard('guest')->user()?->getKey())->toBe($guest->getKey());
    // ...and the WEB guard is empty — the realms stay separate, which is the #26/#28 posture.
    // ⚠️ NOTE WHAT THIS DOES *NOT* SAY. It does not follow that `$request->user()` is null inside a guest
    // ROUTE: `auth:guest` rebinds the default guard for that request, so `$request->user()` there IS the
    // Guest. Reading this observation as "the shared controller cannot serve guests" is exactly the wrong
    // inference the scope pass drew, and it cost a controller that had to be deleted.
    expect(Auth::guard('web')->user())->toBeNull();
});

// ── 7. MARK-READ THROUGH THE GUEST GUARD (risk 2, served) ──────────────────────────────────────────
it('marks a guest notification read and dismissed through the guest guard', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    $s = seedScenario(1);
    $guest = g1Guest($s);
    book($s, ['unit_id' => $s['units'][0]->id]);

    $row = $guest->fresh()->notifications()->first();
    expect(NotificationFeed::forUser($guest->fresh())['unread'])->toBe(1);

    $this->actingAs($guest, 'guest')->post('/guest/notifications/'.$row->id.'/read')->assertRedirect();
    expect(NotificationFeed::forUser($guest->fresh())['unread'])->toBe(0);
    expect($guest->fresh()->notifications()->first()->read_at)->not->toBeNull();

    // Dismiss takes it off the feed entirely — a different act from read, as in the other realms.
    $this->actingAs($guest, 'guest')->post('/guest/notifications/'.$row->id.'/dismiss')->assertRedirect();
    expect(NotificationFeed::forUser($guest->fresh())['items'])->toBe([]);
});

// ── 7a. A REAL GUEST SESSION, not just actingAs() ──────────────────────────────────────────────────
// ⚠️ `actingAs($g, 'guest')` REBINDS THE DEFAULT GUARD for the rest of the test, so it cannot distinguish a
// correctly-guarded controller from one leaning on the default resolver. This logs in through the guard the
// way a real session does and leaves the default alone before the request, so the flow is exercised without
// the harness pre-arranging the answer. (§N.4p — a mutation that cannot reach the path proves nothing; this
// is the shape that was reached for when M6 stayed green.)
it('marks read for a real guest session established through the guard', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    $s = seedScenario(1);
    $guest = g1Guest($s);
    book($s, ['unit_id' => $s['units'][0]->id]);
    $row = $guest->fresh()->notifications()->first();

    Auth::guard('guest')->login($guest);
    expect(Auth::guard('web')->user())->toBeNull();   // the default guard is empty, as in production

    $this->post('/guest/notifications/'.$row->id.'/read')->assertRedirect();

    expect(NotificationFeed::forUser($guest->fresh())['unread'])->toBe(0);
});

// ── 7b. mark-ALL-read through the guest guard ──────────────────────────────────────────────────────
it('marks all of a guest’s notifications read through the guest guard', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    $s = seedScenario(1);
    $guest = g1Guest($s);
    book($s, ['unit_id' => $s['units'][0]->id]);

    $this->actingAs($guest, 'guest')->post('/guest/notifications/read-all')->assertRedirect();

    expect(NotificationFeed::forUser($guest->fresh())['unread'])->toBe(0);
});

// ── 8. COPY IS SINGLE-SOURCED FROM THE EMAIL TEMPLATE (it cannot drift) ────────────────────────────
it('takes the row title from the SAME email template the Mailable renders', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    $s = seedScenario(1);
    $guest = g1Guest($s);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $row = $guest->fresh()->notifications()->first();

    // Resolve the template independently, exactly as BookingConfirmationMail::envelope() does.
    $expected = EmailTemplateResolver::for((string) $reservation->organization_id, 'booking-confirmation', [
        'guest_name' => GuestName::display($reservation->leadGuest),
        'first_name' => 'Ada',
        'unit' => (string) ($reservation->property->name ?? 'your stay'),
        'nights' => '2',
        'reference' => (string) $reservation->reference,
    ])->subject();

    expect($row->data['title'])->toBe($expected);
    // Not a vacuous match against a generic default — the resolved subject really does carry this booking's
    // own reference, so a drift in either surface would move this string.
    expect($row->data['title'])->toContain((string) $reservation->reference);
});

// ── 9. audience ADD, not flip — the other 25 catalog entries are untouched ──────────────────────────
it('adds a guest catalog entry without flipping any operator or owner one', function () {
    expect(NotificationPreferences::CATALOG['guest_booking_confirmed']['audience'])->toBe('guest');

    // The keys the other realms rely on still belong to them.
    expect(NotificationPreferences::CATALOG['new_booking']['audience'])->toBe('operator');
    expect(NotificationPreferences::CATALOG['owner_new_booking']['audience'])->toBe('owner');

    // ...and the guest entry never appears on either realm's preferences screen.
    expect(NotificationPreferences::catalogFor('operator'))->not->toHaveKey('guest_booking_confirmed');
    expect(NotificationPreferences::catalogFor('owner'))->not->toHaveKey('guest_booking_confirmed');
});
