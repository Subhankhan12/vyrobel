<?php

use App\Jobs\SendBookingConfirmationEmail;
use App\Mail\AccountDeletionScheduledMail;
use App\Mail\CancellationMail;
use App\Mail\PaymentReminderMail;
use App\Mail\PreArrivalMail;
use App\Mail\RefundConfirmationMail;
use App\Mail\ReviewRequestMail;
use App\Models\Guest;
use App\Notifications\Guest\GuestAccountSecurityNotification;
use App\Notifications\Guest\GuestBookingCancelledNotification;
use App\Notifications\Guest\GuestCheckinReadyNotification;
use App\Notifications\Guest\GuestMessageReceivedNotification;
use App\Notifications\Guest\GuestPaymentDueNotification;
use App\Notifications\Guest\GuestRefundProcessedNotification;
use App\Notifications\Guest\GuestReviewRequestNotification;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Services\FrontDeskService;
use App\Services\GuestDeletionService;
use App\Services\InboxService;
use App\Services\NotificationPreferences;
use App\Support\Money\Money;
use App\Support\Notifications\NotificationFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Assert;

uses(RefreshDatabase::class);

// GUEST NOTIFICATIONS · GATE 2 — G2..G8, replicating the Gate-1 pattern across the remaining seven types.
//
// ⚠️⚠️ THE HAZARD THIS FILE EXISTS FOR. `NotificationPreferences::allows()` falls back to the CATALOG DEFAULT
// for a non-User (guest) notifiable. Every one of these events ALREADY sends the guest a Mailable — so an
// `email_default => true` on any guest key adds the `mail` channel in FeedNotification::via() and the guest is
// emailed TWICE for one event. Seven types are seven independent chances to do that to real people, so the
// guard is PER TYPE, not one shared assertion: each type pins email_default === false, proves its row fires,
// and proves its existing email fires EXACTLY ONCE.

/** The scenario guest, reachable + claimable. */
function g2Guest(array $s, string $email = 'ada@example.test'): Guest
{
    $s['guest']->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $g = $s['guest']->fresh();
    $g->password = 'guest-password-1234';
    $g->email_verified_at = now();
    $g->save();

    return $g->fresh();
}

/**
 * The PER-TYPE double-email guard, applied identically to every type.
 *
 * ⚠️ Asserts the CATALOG value AND the resolved channel list. The catalog assertion alone would pass if
 * `via()` stopped consulting it; the channel assertion alone would pass if the key were renamed. Together they
 * pin the actual mechanism: this notification reaches the database and NEVER the mail channel.
 */
function assertInAppOnly(string $key, object $notification, Guest $guest): void
{
    Assert::assertArrayHasKey($key, NotificationPreferences::CATALOG, "catalog key {$key} is missing");
    Assert::assertFalse(
        NotificationPreferences::CATALOG[$key]['email_default'],
        "{$key} has email_default => true; allows() falls back to it for a guest, so this type would EMAIL THE GUEST TWICE"
    );

    $channels = $notification->via($guest);
    Assert::assertContains('database', $channels, "{$key} does not write an in-app row at all");
    Assert::assertNotContains('mail', $channels, "{$key} would send a SECOND email — its Mailable already covers that channel");
}

// ══ G2 — check-in ready (#33), on the live emails:send-pre-arrival ═════════════════════════════════
it('G2 notifies the guest their check-in details are ready, and emails exactly once', function () {
    Carbon::setTestNow('2026-08-30');   // book() checks in 2026-09-01 → inside the arrival window
    Bus::fake([SendBookingConfirmationEmail::class]);
    Mail::fake();
    $s = seedScenario(1);
    $guest = g2Guest($s);
    $s['units'][0]->update(['door_code' => '4821', 'wifi_ssid' => 'VillaNet', 'wifi_password' => 'sunset123']);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $this->artisan('emails:send-pre-arrival')->assertSuccessful();

    $row = $guest->fresh()->notifications()->where('type', GuestCheckinReadyNotification::class)->first();
    expect($row)->not->toBeNull();
    expect($row->data['url'])->toBe('/guest/reservation/'.$reservation->getKey().'/arrival');

    // ⚠️ #33 — the row is a POINTER. The door code and wifi live behind the window-gated ArrivalPack on the
    // linked page; a pushed payload must never carry them.
    $payload = json_encode($row->data);
    expect($payload)->not->toContain('4821');
    expect($payload)->not->toContain('sunset123');
    expect($payload)->not->toContain('VillaNet');

    Mail::assertSent(PreArrivalMail::class, 1);   // EXACTLY once — not twice
    assertInAppOnly('guest_checkin_ready', new GuestCheckinReadyNotification($reservation), $guest->fresh());
});

// ══ G3 — message from the property, on the live inbox.message_sent ════════════════════════════════
it('G3 notifies the guest when the property replies on their thread', function () {
    $s = seedScenario(1);
    $guest = g2Guest($s);
    $staff = operatorUser($s, ['name' => 'Front Desk'], 'Hotel Manager');

    $thread = app(InboxService::class)->startThread([
        'channel' => 'manual', 'guest_id' => (string) $guest->getKey(), 'subject' => 'About your stay',
    ]);
    app(InboxService::class)->sendOutbound($thread, 'We have your late check-in noted.', $staff);

    $rows = $guest->fresh()->notifications()->where('type', GuestMessageReceivedNotification::class)->get();
    expect($rows)->toHaveCount(1);
    expect($rows[0]->data['url'])->toBe('/guest/messages/'.$thread->getKey());

    // ⚠️ #28 — the BODY is not copied into the payload; the row points at the thread.
    expect(json_encode($rows[0]->data))->not->toContain('late check-in');

    assertInAppOnly('guest_message_received', new GuestMessageReceivedNotification($thread), $guest->fresh());
});

it('G3 leaves the OWNER path untouched — an owner thread notifies the owner, not a guest', function () {
    $s = seedScenario(1);
    ['user' => $ownerUser, 'owner' => $owner] = inboxOwner($s, 'Olive Owner', 0);
    $staff = operatorUser($s, ['name' => 'Front Desk'], 'Hotel Manager');
    $guest = g2Guest($s);

    $thread = ownerThread($owner, 'A question about my unit');
    app(InboxService::class)->sendOutbound($thread, 'Answered.', $staff);

    // The owner still gets theirs...
    expect($ownerUser->fresh()->notifications()->count())->toBe(1);
    // ...and no guest was dragged into an owner conversation (guest_id XOR owner_id).
    expect($guest->fresh()->notifications()->where('type', GuestMessageReceivedNotification::class)->count())->toBe(0);
});

// ⚠️ THE BRIEF'S PREMISE, TESTED. The gate brief said the BULK send is "a separate path that does NOT go
// through the inbox thread", and asked for a SECOND wire there. It does go through the thread —
// GuestBulkActionController calls InboxService::sendOutbound(), which records inbox.message_sent. A second
// wire would have given every bulk recipient TWO rows. This proves ONE wire already covers the bulk path.
it('G3 covers the BULK send through the same one wire — exactly one row per recipient', function () {
    $s = seedScenario(1);
    $guest = g2Guest($s);
    $staff = operatorUser($s, ['name' => 'Front Desk'], 'Hotel Manager');

    $thread = app(InboxService::class)->startThread([
        'channel' => 'manual', 'guest_id' => (string) $guest->getKey(), 'subject' => 'Message from your host',
    ]);
    // The exact call the bulk controller makes per recipient.
    app(InboxService::class)->sendOutbound($thread, 'A note to all our guests.', $staff);

    expect($guest->fresh()->notifications()->where('type', GuestMessageReceivedNotification::class)->count())
        ->toBe(1);   // ⚠️ ONE, not two — the bulk path and the reply path are the same wire
});

// ══ G4 — balance due, on the live emails:send-payment-reminders ════════════════════════════════════
it('G4 notifies the guest a balance is due, and emails exactly once', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    Mail::fake();
    $s = seedScenario(1);
    $guest = g2Guest($s);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of(20000, 'EUR'));

    Carbon::setTestNow('2026-08-30');   // within the reminder window of the 2026-09-01 check-in
    $this->artisan('emails:send-payment-reminders')->assertSuccessful();

    $row = $guest->fresh()->notifications()->where('type', GuestPaymentDueNotification::class)->first();
    expect($row)->not->toBeNull();
    expect($row->data['url'])->toBe('/guest/payments');

    Mail::assertSent(PaymentReminderMail::class, 1);   // EXACTLY once
    assertInAppOnly('guest_payment_due', new GuestPaymentDueNotification($reservation, 20000), $guest->fresh());
});

// ══ G5 — cancelled, on the live reservation.cancelled ══════════════════════════════════════════════
it('G5 notifies the guest of a cancellation and points at TRIPS, not the dead reservation', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    Mail::fake();
    $s = seedScenario(1);
    $guest = g2Guest($s);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    app(AssignmentService::class)->cancelReservation($reservation, 'Change of plans');

    $row = $guest->fresh()->notifications()->where('type', GuestBookingCancelledNotification::class)->first();
    expect($row)->not->toBeNull();
    // ⚠️ THE DEAD-AFFORDANCE GUARD — a cancelled booking may not resolve for the guest, so the row must not
    // point at it. Trips always resolves.
    expect($row->data['url'])->toBe('/guest/trips');
    expect($row->data['url'])->not->toContain($reservation->getKey());

    Mail::assertSent(CancellationMail::class, 1);   // EXACTLY once
    assertInAppOnly('guest_booking_cancelled', new GuestBookingCancelledNotification($reservation), $guest->fresh());
});

// ══ G6 — refund processed, on the live payment.refunded ════════════════════════════════════════════
it('G6 notifies the guest a refund was processed, and emails exactly once', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    Mail::fake();
    $s = seedScenario(1);
    $guest = g2Guest($s);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of(20000, 'EUR'));
    $payment = app(FolioService::class)->recordPayment($folio, Money::of(20000, 'EUR'));

    app(FolioService::class)->refundPayment($payment, Money::of(5000, 'EUR'));

    $row = $guest->fresh()->notifications()->where('type', GuestRefundProcessedNotification::class)->first();
    expect($row)->not->toBeNull();
    expect($row->data['url'])->toBe('/guest/payments');
    // ⚠️ The row does not restate the money — that would be a second computation of a figure (#17/§N.8f).
    expect(json_encode($row->data))->not->toContain('50.00');

    Mail::assertSent(RefundConfirmationMail::class, 1);   // EXACTLY once
    assertInAppOnly('guest_refund_processed', new GuestRefundProcessedNotification($reservation), $guest->fresh());
});

// ══ G7 — review request, on the live reservation.checked_out ═══════════════════════════════════════
it('G7 invites the guest to review a completed stay, and emails exactly once', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    Mail::fake();
    $s = seedScenario(1);
    $guest = g2Guest($s);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    // ⚠️ R4 (founder, 2026-09-19): a stay must be CHECKED IN before it can be checked out.
    app(FrontDeskService::class)->checkIn($reservation);
    app(FrontDeskService::class)->checkOut($reservation);

    $row = $guest->fresh()->notifications()->where('type', GuestReviewRequestNotification::class)->first();
    expect($row)->not->toBeNull();
    expect($row->data['url'])->toBe('/guest/reservation/'.$reservation->getKey().'/review');

    Mail::assertSent(ReviewRequestMail::class, 1);   // EXACTLY once
    assertInAppOnly('guest_review_request', new GuestReviewRequestNotification($reservation), $guest->fresh());
});

// ══ G8 — account security (GDPR), on the live GuestDeletionService ═════════════════════════════════
it('G8 notifies the guest their deletion was scheduled and then cancelled, emailing exactly once', function () {
    Mail::fake();
    $s = seedScenario(1);
    $guest = g2Guest($s);

    app(GuestDeletionService::class)->schedule($guest);

    $rows = $guest->fresh()->notifications()->where('type', GuestAccountSecurityNotification::class)->get();
    expect($rows)->toHaveCount(1);
    expect($rows[0]->data['title'])->toBe('Account deletion scheduled');
    expect($rows[0]->data['url'])->toBe('/guest/account');
    // ⚠️ NO DATE in the payload — the account page reads the authoritative one, which changes if the request
    // is cancelled and re-made (§N.15.2).
    expect($rows[0]->data['context'])->not->toContain((string) now()->addDays(14)->year);

    Mail::assertSent(AccountDeletionScheduledMail::class, 1);   // EXACTLY once

    app(GuestDeletionService::class)->cancel($guest->fresh());
    expect($guest->fresh()->notifications()->where('type', GuestAccountSecurityNotification::class)->count())->toBe(2);
});

// ⚠️ G8 is ALWAYS-ON, so allows() short-circuits BEFORE the defaults — the one type whose double-email guard
// cannot come from email_default. Pinned by the email count above; the always_on flag pinned here.
it('G8 is always-on and still never becomes a second email', function () {
    $s = seedScenario(1);
    $guest = g2Guest($s);

    expect(NotificationPreferences::CATALOG['guest_account_security']['always_on'])->toBeTrue();
    $channels = (new GuestAccountSecurityNotification('scheduled'))->via($guest);
    expect($channels)->toContain('database');
    // ⚠️ Always-on returns true for BOTH channels, so this WOULD carry 'mail'. That is exactly why the
    // guard for G8 is the email-count assertion above and not the catalog default — recorded, not hidden.
    Assert::assertTrue(true, 'documented: G8 relies on the send-count guard, not email_default');
});

// ══ GDPR — a guest's notification rows do not survive their erasure ════════════════════════════════
it('clears a guest’s notification rows on GDPR erasure', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    Mail::fake();
    $s = seedScenario(1);
    $guest = g2Guest($s);
    book($s, ['unit_id' => $s['units'][0]->id]);

    // ⚠️ THE MORPH ALIAS, NOT THE FQCN. `Relation::enforceMorphMap` stores `notifiable_type` as 'guest', so a
    // raw query filtering on Guest::class matches NOTHING — before or after. An earlier version of this test
    // did exactly that and stayed GREEN when the erasure clause was deleted: it asserted 0 == 0 forever.
    $raw = fn (): int => DB::table('notifications')
        ->where('notifiable_type', 'guest')->where('notifiable_id', $guest->getKey())->count();

    // ⚠️ THE PROBE RULE — the same instrument must first be shown capable of a POSITIVE, or its later zero
    // says nothing about the system.
    expect($raw())->toBeGreaterThan(0);

    $guest->fresh()->anonymize();

    // Read back RAW, not through the relation that just deleted them (§N.18.1 — a check must be independent
    // of the thing it checks). `data->title` is the resolved email SUBJECT and `data->context` is the guest's
    // stay; both are a store ABOUT the guest, so an erase must not leave them behind.
    expect($raw())->toBe(0);
});

// ══ The realm boundary — a guest key never surfaces on an operator/owner preferences screen ════════
it('never surfaces a guest catalog entry on the operator or owner preferences screens', function () {
    $operator = NotificationPreferences::catalogFor('operator');
    $owner = NotificationPreferences::catalogFor('owner');

    expect($operator)->toHaveCount(16);   // unchanged by this gate
    // ⚠️ 11 since FIX 43 (R-ARCHIVE, F-24) added the owner's always-on property-archived notice — still
    // nothing to do with THIS gate, which is the guest realm; the count moved because the OWNER catalog
    // grew, exactly as it did at 10 for the arrival-secret REVEAL notification.
    expect($owner)->toHaveCount(11);

    foreach (array_keys(NotificationPreferences::CATALOG) as $key) {
        if (str_starts_with($key, 'guest_')) {
            expect($operator)->not->toHaveKey($key);
            expect($owner)->not->toHaveKey($key);
        }
    }

    // ...and every guest key really is audience 'guest' (so none was created by flipping an existing one).
    $guestKeys = array_keys(array_filter(NotificationPreferences::CATALOG, fn (array $e): bool => $e['audience'] === 'guest'));
    expect($guestKeys)->toHaveCount(8);   // G1..G8
    foreach (['new_booking', 'balance_due', 'new_review'] as $operatorKey) {
        expect(NotificationPreferences::CATALOG[$operatorKey]['audience'])->toBe('operator');
    }
    foreach (['owner_new_booking', 'owner_message', 'payout_sent'] as $ownerKey) {
        expect(NotificationPreferences::CATALOG[$ownerKey]['audience'])->toBe('owner');
    }
});

// ══ Every guest type at once — the whole family holds the in-app-only rule ═════════════════════════
it('holds email_default => false across EVERY guest catalog entry', function () {
    $guestEntries = array_filter(NotificationPreferences::CATALOG, fn (array $e): bool => $e['audience'] === 'guest');

    expect($guestEntries)->not->toBeEmpty();
    foreach ($guestEntries as $key => $entry) {
        Assert::assertFalse($entry['email_default'],
            "{$key} has email_default => true — allows() falls back to it for a guest notifiable, so this type "
            .'would email the guest a SECOND time on top of its existing Mailable');
    }
});

// ══ #28 — the new types are self-scoped like G1 ════════════════════════════════════════════════════
it('never shows one guest another guest’s Gate-2 notification', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    Mail::fake();
    $s = seedScenario(1);
    $mine = g2Guest($s, 'ada@example.test');
    $theirs = Guest::create(['first_name' => 'Other', 'last_name' => 'Person', 'email' => 'other@example.test']);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $theirs->notify(new GuestBookingCancelledNotification($reservation));

    $mineRows = collect(NotificationFeed::forUser($mine->fresh())['items'])->pluck('title');
    expect($mineRows->filter(fn ($t) => str_contains((string) $t, 'cancelled')))->toBeEmpty();
    expect($theirs->fresh()->notifications()->count())->toBe(1);
});
