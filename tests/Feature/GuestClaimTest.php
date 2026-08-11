<?php

use App\Mail\VerifyEmailMail;
use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

// GUEST ACCOUNT CLAIM (Phase 4a · Sub-gate 2) — BOOKING-TIED sign-up + email verification. A guest claims portal
// access for an email that has a REAL booking; a guest with no booking cannot. NO ENUMERATION — the response is
// identical either way. The claim link is a native HMAC-signed, expiring URL; completing it sets the password +
// verifies the email + logs in on the GUEST guard. Wires the Phase-3 Verify Email Mailable's trigger.

/** Give the seeded guest an email + a real booking (so it is claimable). */
function bookedGuest(array $s, string $email = 'ada@example.test'): Guest
{
    $s['guest']->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    book($s, ['unit_id' => $s['units'][0]->id]);   // $s['guest'] now leads a reservation

    return $s['guest']->fresh();
}

// ── The happy path: a booked guest claims their account end-to-end ─────────────────────────────────
it('lets a guest with a real booking claim their account, set a password, and land signed in', function () {
    Mail::fake();
    $s = seedScenario(1);
    $guest = bookedGuest($s);

    // 1. Request a link → neutral "sent" page.
    $this->post('/guest/claim', ['email' => 'ada@example.test'])->assertRedirect(route('guest.claim.sent'));

    // 2. The Verify Email Mailable was sent to the guest's OWN address, carrying a signed claim link.
    $sentUrl = null;
    Mail::assertSent(VerifyEmailMail::class, function (VerifyEmailMail $mail) use (&$sentUrl): bool {
        $sentUrl = $mail->verifyUrl;

        return $mail->hasTo('ada@example.test')
            && str_contains($mail->verifyUrl, '/guest/claim/verify/')
            && str_contains($mail->verifyUrl, 'signature=');
    });

    // 3. Follow the ACTUAL emailed link → the set-password form.
    $this->get($sentUrl)->assertOk()->assertSee('Set a password');

    // 4. Complete the claim.
    $this->post($sentUrl, ['password' => 'super secret pw', 'password_confirmation' => 'super secret pw'])
        ->assertRedirect(route('guest.trips'));

    // 5. The account is claimed (hashed password + verified) and the guest is signed in on the GUEST guard.
    $fresh = $guest->fresh();
    expect($fresh->hasPortalAccount())->toBeTrue();
    expect(Hash::check('super secret pw', (string) $fresh->password))->toBeTrue();
    $this->assertAuthenticatedAs($fresh, 'guest');
});

// ── Booking-tied: no booking → no account, neutral response, NO link ───────────────────────────────
it('does not create an account for an email with no booking, and reveals nothing', function () {
    Mail::fake();
    seedScenario(1);   // exists, but this email has no booking

    $this->post('/guest/claim', ['email' => 'stranger@example.test'])->assertRedirect(route('guest.claim.sent'));

    Mail::assertNotSent(VerifyEmailMail::class);   // no booking → no link → no account
});

// ── Booking-tied at the row level: a guest row that exists but leads NO booking can't claim ───────
it('does not let a guest row with no booking claim an account', function () {
    Mail::fake();
    seedScenario(1);
    // A guest row exists with an email (e.g. an occupant added but never a lead), but leads no reservation.
    Guest::create(['email' => 'nobooking@example.test', 'first_name' => 'No', 'last_name' => 'Booking']);

    $this->post('/guest/claim', ['email' => 'nobooking@example.test'])->assertRedirect(route('guest.claim.sent'));

    Mail::assertNotSent(VerifyEmailMail::class);   // a guest WITHOUT a booking is not claimable (booking-tied)
});

// ── NO ENUMERATION: the response is IDENTICAL whether or not the email has a booking ──────────────
it('returns an identical response whether or not the email has a booking', function () {
    Mail::fake();
    $s = seedScenario(1);
    bookedGuest($s, 'ada@example.test');

    $withBooking = $this->post('/guest/claim', ['email' => 'ada@example.test']);
    $without = $this->post('/guest/claim', ['email' => 'nobody@example.test']);

    // Same status + same redirect target — nothing distinguishes the two.
    expect($withBooking->getStatusCode())->toBe($without->getStatusCode());
    $withBooking->assertRedirect(route('guest.claim.sent'));
    $without->assertRedirect(route('guest.claim.sent'));
    // …yet the link only ever went to the real booking.
    Mail::assertSent(VerifyEmailMail::class, fn (VerifyEmailMail $m): bool => $m->hasTo('ada@example.test'));
    Mail::assertNotSent(VerifyEmailMail::class, fn (VerifyEmailMail $m): bool => $m->hasTo('nobody@example.test'));
});

// ── An already-claimed email gets the neutral response but NO new link (no enumeration) ───────────
it('sends no link for an email that already has an account', function () {
    Mail::fake();
    $s = seedScenario(1);
    $guest = bookedGuest($s);
    $guest->password = 'already set';
    $guest->email_verified_at = now();
    $guest->save();

    $this->post('/guest/claim', ['email' => 'ada@example.test'])->assertRedirect(route('guest.claim.sent'));

    Mail::assertNotSent(VerifyEmailMail::class);   // already claimed → no link, but the response is still neutral
});

// ── Signed-link integrity: a tampered signature is rejected; a valid signature with a wrong hash is generic ─
it('rejects a tampered link and shows a generic page for a wrong hash', function () {
    $s = seedScenario(1);
    $guest = bookedGuest($s);

    // Tampered signature → 403 (the `signed` middleware).
    $this->get('/guest/claim/verify/'.$guest->getKey().'/'.sha1('ada@example.test').'?signature=deadbeef')
        ->assertForbidden();

    // Valid signature but the hash doesn't match the email → the generic invalid page (no enumeration).
    $badHash = URL::temporarySignedRoute('guest.claim.verify', now()->addHour(), ['guest' => $guest->getKey(), 'hash' => 'not-the-email-hash']);
    $this->get($badHash)->assertOk()->assertSee('isn’t valid');
});

// ── ⚠️ G11 — THE CLAIM LINK REALLY EXPIRES, AND THE PAGE'S PROMISE IS BOUND TO IT ─────────────────
// A signed claim link is a CREDENTIAL IN A URL: anyone holding it can set the password on that account. Its
// only limit is time. `sendClaimLink()` mints it with `temporarySignedRoute(..., now()->addHour())`, and the
// "check your inbox" page tells the guest in words: "It expires in an hour."
//
// ⚠️ NOTHING PROVED EITHER HALF. The suite covered a TAMPERED signature and a wrong hash, but never expiry —
// so swapping `temporarySignedRoute` for a permanent `signedRoute` would have left every test green while
// turning every claim link in every old inbox into a permanent account-takeover key, and turning the page's
// sentence into a lie. This drives the REAL emailed URL across the boundary in both directions.
it('expires the emailed claim link after the hour the page promises, and refuses it thereafter', function () {
    Mail::fake();
    $s = seedScenario(1);
    bookedGuest($s);

    $this->post('/guest/claim', ['email' => 'ada@example.test']);

    $sentUrl = null;
    Mail::assertSent(VerifyEmailMail::class, function (VerifyEmailMail $mail) use (&$sentUrl): bool {
        $sentUrl = $mail->verifyUrl;

        return true;
    });

    // The page's words, and the link they describe — read from the real "sent" screen, not restated here.
    $this->get(route('guest.claim.sent'))->assertOk()->assertSee('expires in an hour');

    // INSIDE the window (59 minutes later) — still the set-password form.
    $this->travelTo(now()->addMinutes(59));
    $this->get($sentUrl)->assertOk()->assertSee('Set a password');

    // PAST it (61 minutes) — refused by the signature itself, before any controller code runs.
    $this->travelTo(now()->addMinutes(2));
    $this->get($sentUrl)->assertForbidden();
    $this->post($sentUrl, ['password' => 'super secret pw', 'password_confirmation' => 'super secret pw'])
        ->assertForbidden();

    // …and the account was never claimed by the expired link.
    expect(Guest::query()->where('email_hash', Guest::emailHash('ada@example.test'))->first()->hasPortalAccount())->toBeFalse();

    $this->travelBack();
});

// ── ⚠️ G11 — A LINK WITH NO SIGNATURE AT ALL IS REFUSED (the GET-scope exception is not a bare GET) ───
// `claim.verify` is one of only two signed-URL GETs in the guest realm (#20's exception). Its authorization
// IS the signature — so the naked URL, which a log or a browser history would preserve, must be worthless.
it('refuses a claim URL that carries no signature at all', function () {
    $s = seedScenario(1);
    $guest = bookedGuest($s);
    $naked = '/guest/claim/verify/'.$guest->getKey().'/'.sha1('ada@example.test');

    $this->get($naked)->assertForbidden();
    $this->post($naked, ['password' => 'super secret pw', 'password_confirmation' => 'super secret pw'])->assertForbidden();

    expect($guest->fresh()->hasPortalAccount())->toBeFalse();
});
