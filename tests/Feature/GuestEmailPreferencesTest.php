<?php

use App\Jobs\SendBookingConfirmationEmail;
use App\Mail\PaymentReminderMail;
use App\Mail\ReviewRequestMail;
use App\Models\Guest;
use App\Models\GuestEmailPreference;
use App\Services\FolioService;
use App\Services\FrontDeskService;
use App\Services\NotificationPreferences;
use App\Support\Email\GuestEmailPreferences;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Assert;

uses(RefreshDatabase::class);

// GUEST EMAIL PREFERENCES — the guest's own opt-out from the OPTIONAL transactional emails.
//
// ⚠️ §N.29 — THESE GUARD THE PROTECTION RUNNING, NOT THE SWITCH RENDERING. Every test below asserts what the
// MAILER did: sent, or not sent. None asserts that a toggle appears, that a column exists, or that a route is
// declared — the label-not-protection failure §N.29 catalogues. The screen render was confirmed separately in
// the browser; a rendered switch that suppresses nothing would pass a render check and fail these.
//
// ⚠️ THIS IS A SEND-SITE GATE, NOT `allows()`. The guest transactional emails never consult
// NotificationPreferences::allows() — it gates the in-app feed only — so nothing here touches it, and the
// operator/owner path is untouched by construction.

/** The scenario guest, reachable by email and claimable. */
function gepGuest(array $s, string $email = 'ada@example.test'): Guest
{
    $s['guest']->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $g = $s['guest']->fresh();
    $g->password = 'guest-password-1234';
    $g->email_verified_at = now();
    $g->save();

    return $g->fresh();
}

/** Drive the real payment-reminder command against a booking with an outstanding balance. */
function gepRunPaymentReminder(array $s): void
{
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of(20000, 'EUR'));

    Carbon::setTestNow('2026-08-30');   // inside the reminder window of the 2026-09-01 check-in
    test()->artisan('emails:send-payment-reminders')->assertSuccessful();
}

// ══ PAYMENT REMINDERS — the gate, both ways ═══════════════════════════════════════════════════════
it('still emails a payment reminder when the guest has NOT opted out (default = send)', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    Mail::fake();
    $s = seedScenario(1);
    gepGuest($s);

    gepRunPaymentReminder($s);

    // ⚠️ The DEFAULT is the absence of a row. A guest who never opened the screen is unaffected.
    Mail::assertSent(PaymentReminderMail::class, 1);
});

it('does NOT email a payment reminder when the guest has opted out', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    Mail::fake();
    $s = seedScenario(1);
    $guest = gepGuest($s);

    GuestEmailPreferences::set($guest, 'payment', receiving: false);

    gepRunPaymentReminder($s);

    // ⚠️ THE WHOLE POINT — the mailer was never asked to send. Not "the toggle rendered off".
    Mail::assertNotSent(PaymentReminderMail::class);
});

// ══ REVIEW REQUESTS — the gate, both ways ═════════════════════════════════════════════════════════
it('still emails a review request when the guest has NOT opted out (default = send)', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    Mail::fake();
    $s = seedScenario(1);
    gepGuest($s);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    // ⚠️ R4 (founder, 2026-09-19): a stay must be CHECKED IN before it can be checked out.
    app(FrontDeskService::class)->checkIn($reservation);
    app(FrontDeskService::class)->checkOut($reservation);

    Mail::assertSent(ReviewRequestMail::class, 1);
});

it('does NOT email a review request when the guest has opted out', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    Mail::fake();
    $s = seedScenario(1);
    $guest = gepGuest($s);
    GuestEmailPreferences::set($guest, 'reviews', receiving: false);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    // ⚠️ R4 (founder, 2026-09-19): a stay must be CHECKED IN before it can be checked out.
    app(FrontDeskService::class)->checkIn($reservation);
    app(FrontDeskService::class)->checkOut($reservation);

    Mail::assertNotSent(ReviewRequestMail::class);
});

// ══ THE TWO CATEGORIES ARE INDEPENDENT ════════════════════════════════════════════════════════════
it('suppresses only the category the guest opted out of', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    Mail::fake();
    $s = seedScenario(1);
    $guest = gepGuest($s);
    GuestEmailPreferences::set($guest, 'payment', receiving: false);   // payment OFF, reviews untouched
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    // ⚠️ R4 (founder, 2026-09-19): a stay must be CHECKED IN before it can be checked out.
    app(FrontDeskService::class)->checkIn($reservation);
    app(FrontDeskService::class)->checkOut($reservation);

    Mail::assertSent(ReviewRequestMail::class, 1);      // the OTHER category still sends
    Mail::assertNotSent(PaymentReminderMail::class);
});

// ══ #28 — one guest's choice never suppresses another's email ═════════════════════════════════════
it('never lets one guest’s opt-out suppress another guest’s email', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    Mail::fake();
    $s = seedScenario(1);
    $mine = gepGuest($s, 'ada@example.test');

    // A DIFFERENT guest opts out of payment reminders.
    $theirs = Guest::create(['first_name' => 'Other', 'last_name' => 'Person', 'email' => 'other@example.test']);
    GuestEmailPreferences::set($theirs, 'payment', receiving: false);
    Assert::assertSame(1, GuestEmailPreference::query()->count(), 'the other guest must really hold an opt-out, or this proves nothing');

    gepRunPaymentReminder($s);   // the reservation belongs to $mine

    // ⚠️ Mine still sends — a foreign row must not reach my gate.
    Mail::assertSent(PaymentReminderMail::class, fn (PaymentReminderMail $m): bool => $m->hasTo('ada@example.test'));
});

// ══ ALWAYS-ON — the locked categories are not storable, so they cannot be switched off ═════════════
it('refuses to store an opt-out for an always-on category', function () {
    $s = seedScenario(1);
    $guest = gepGuest($s);

    // ⚠️ Posting the key of a LOCKED row must not create an opt-out. `txn` is a transactional record and
    // `pre_arrival` carries the door code — a guest who could switch it off could arrive unable to get in.
    foreach (['txn', 'pre_arrival', 'offers', 'winback', 'push', 'guest_account_security'] as $locked) {
        GuestEmailPreferences::set($guest, $locked, receiving: false);
    }

    expect(GuestEmailPreference::query()->count())->toBe(0);
    // ...and the gate answers "yes, send" for them regardless.
    foreach (['txn', 'pre_arrival'] as $locked) {
        expect(GuestEmailPreferences::mayReceive($guest, $locked))->toBeTrue();
    }
});

it('refuses an always-on category posted through the account surface', function () {
    $s = seedScenario(1);
    $guest = gepGuest($s);

    $this->actingAs($guest, 'guest')
        ->post('/guest/account/preferences', ['email_pref' => 'pre_arrival', 'receiving' => '0'])
        ->assertRedirect();

    // ⚠️ RAW read, independent of the helper that just refused it (§N.18.1).
    expect(DB::table('guest_email_preferences')->count())->toBe(0);
});

// ══ THE ACCOUNT SURFACE WRITES IT, SELF-SCOPED ════════════════════════════════════════════════════
it('stores the guest’s own opt-out through the account surface and undoes it', function () {
    $s = seedScenario(1);
    $guest = gepGuest($s);

    $this->actingAs($guest, 'guest')
        ->post('/guest/account/preferences', ['email_pref' => 'payment', 'receiving' => '0'])
        ->assertRedirect();

    $row = DB::table('guest_email_preferences')->first();
    expect($row)->not->toBeNull();
    expect($row->guest_id)->toBe((string) $guest->getKey());   // ⚠️ their OWN id, from the guard
    expect($row->category)->toBe('payment');
    expect(GuestEmailPreferences::mayReceive($guest->fresh(), 'payment'))->toBeFalse();

    // Switching it back on removes the row (sparse — deviations only).
    $this->actingAs($guest, 'guest')
        ->post('/guest/account/preferences', ['email_pref' => 'payment', 'receiving' => '1'])
        ->assertRedirect();

    expect(DB::table('guest_email_preferences')->count())->toBe(0);
    expect(GuestEmailPreferences::mayReceive($guest->fresh(), 'payment'))->toBeTrue();
});

// ══ THE MARKETING CONSENT IS A SEPARATE CONTROL — not collapsed, not re-routed ════════════════════
it('leaves marketing_consent untouched when an email preference changes', function () {
    $s = seedScenario(1);
    $guest = gepGuest($s);
    $guest->update(['marketing_consent' => true]);

    $this->actingAs($guest, 'guest')
        ->post('/guest/account/preferences', ['email_pref' => 'payment', 'receiving' => '0'])
        ->assertRedirect();

    // ⚠️ Different legal basis, different store. A transactional opt-out must never revoke marketing consent.
    expect((bool) $guest->fresh()->marketing_consent)->toBeTrue();
    expect(DB::table('guest_email_preferences')->count())->toBe(1);
});

it('leaves the email preferences untouched when marketing_consent changes', function () {
    $s = seedScenario(1);
    $guest = gepGuest($s);
    GuestEmailPreferences::set($guest, 'reviews', receiving: false);

    $this->actingAs($guest, 'guest')
        ->post('/guest/account/preferences', ['marketing_consent' => '0'])
        ->assertRedirect();

    // ...and the reverse: revoking marketing must not silently re-enable or clear a transactional choice.
    expect(GuestEmailPreferences::mayReceive($guest->fresh(), 'reviews'))->toBeFalse();
});

// ══ THE IN-APP CHANNEL IS UNAFFECTED — allows() was not touched ═══════════════════════════════════
it('still records the in-app notification when the guest has muted the EMAIL', function () {
    Bus::fake([SendBookingConfirmationEmail::class]);
    Mail::fake();
    $s = seedScenario(1);
    $guest = gepGuest($s);
    GuestEmailPreferences::set($guest, 'payment', receiving: false);

    gepRunPaymentReminder($s);

    Mail::assertNotSent(PaymentReminderMail::class);
    // ⚠️ The two channels are independent: muting the email must not blind the guest inside the portal.
    expect($guest->fresh()->notifications()->where('type', 'App\\Notifications\\Guest\\GuestPaymentDueNotification')->count())
        ->toBe(1);

    // ...and the preference service the in-app path uses is untouched by this feature.
    expect(NotificationPreferences::CATALOG['guest_payment_due']['audience'])->toBe('guest');
    // ⚠️ 17 since ADMIN 01 · PHASE 3: an operator is told, ALWAYS-ON, when a Vyrobel break-glass session looked at their
    //    workspace (`support_access`) — a notice they could switch off would let support look without anyone knowing.
    expect(NotificationPreferences::catalogFor('operator'))->toHaveCount(17);
    // ⚠️ 11 since FIX 43 (R-ARCHIVE, F-24): an owner is now told when a property holding their units is
    // ARCHIVED — always-on, because nothing is ever un-archived and a notice they could switch off would
    // reinstate the silence that finding names. It was 10 from the arrival-secret REVEAL notification
    // (cluster E follow-up), itself always-on for the same class of reason.
    // ⚠️ THE PIN MOVES ONLY WITH A REASON, and moving it IS the visible record of the decision — the
    //    operator count was untouched at 16 by it, because that entry is owner-audience.
    expect(NotificationPreferences::catalogFor('owner'))->toHaveCount(11);
});
