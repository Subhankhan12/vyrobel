<?php

use App\Mail\AccountDeletionScheduledMail;
use App\Models\Guest;
use App\Models\Message;
use App\Models\Reservation;
use App\Models\Review;
use App\Services\AssignmentService;
use App\Services\GuestDeletionService;
use App\Services\InboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// GUEST ACCOUNT + GUEST ISOLATION (Phase 4a · Guest Portal GATE 3) — the authed guest's OWN profile,
// preferences, and account deletion. SELF-ONLY: everything resolves from the GUEST guard, never a request id.
// Proven: a guest edits only their own; a guest can't reach another's (even with another's id in the payload);
// change-password is current-verified; DELETE is a REVERSIBLE 14-day SCHEDULED erase (D2 — type DELETE, undo,
// the daily sweep runs anonymize + per-policy cancellation, never an immediate destroy); real KPIs + honest
// cards/SMS (D3); the trip-offers toggle flips marketing_consent (#87); the guest realm never falls back to operator.

/** A standalone claimed guest account. */
function acct(string $email, string $pw = 'account-password-1234'): Guest
{
    $g = Guest::create(['email' => $email, 'first_name' => 'First', 'last_name' => 'Last', 'phone' => '+100', 'nationality' => 'GB', 'language' => 'en']);
    $g->password = $pw;
    $g->email_verified_at = now();
    $g->save();

    return $g->fresh();
}

// ── Self-only: a guest sees + edits their OWN record (resolved from the guard) ─────────────────────
it('shows and self-edits the authed guest’s own profile', function () {
    seedScenario(1);
    $a = acct('ada@example.test');

    $this->actingAs($a, 'guest')->get('/guest/account')->assertOk()->assertSee('ada@example.test');

    $this->actingAs($a, 'guest')->post('/guest/account', [
        'first_name' => 'Ada', 'last_name' => 'Lovelace', 'phone' => '+44123', 'nationality' => 'GB', 'language' => 'en',
    ])->assertRedirect(route('guest.account'));

    expect($a->fresh()->first_name)->toBe('Ada');
});

// ── GUEST ISOLATION (mutation-proven): a guest can't touch another's record, even with their id ───
it('never lets a guest edit another guest, even with another’s id in the payload', function () {
    seedScenario(1);
    $a = acct('ada@example.test');
    $b = acct('bob@example.test');

    // A edits, sneaking B's id into the request — the controller resolves SELF from the guard and ignores it.
    $this->actingAs($a, 'guest')->post('/guest/account', [
        'first_name' => 'Hacked', 'last_name' => 'X', 'id' => $b->getKey(), 'guest_id' => $b->getKey(),
    ])->assertRedirect();

    expect($a->fresh()->first_name)->toBe('Hacked');   // A's own record changed…
    expect($b->fresh()->first_name)->toBe('First');    // …B's is UNTOUCHED — isolation holds
});

// ── Change password — the CURRENT password is verified ─────────────────────────────────────────────
it('changes the password only when the current one is correct', function () {
    seedScenario(1);
    $a = acct('ada@example.test', 'old-password-123');

    // Wrong current → rejected, unchanged.
    $this->actingAs($a, 'guest')->post('/guest/account/password', [
        'current_password' => 'WRONG', 'password' => 'new-password-456', 'password_confirmation' => 'new-password-456',
    ])->assertSessionHasErrors('current_password');
    expect(Hash::check('old-password-123', (string) $a->fresh()->password))->toBeTrue();

    // Correct current → changed.
    $this->actingAs($a, 'guest')->post('/guest/account/password', [
        'current_password' => 'old-password-123', 'password' => 'new-password-456', 'password_confirmation' => 'new-password-456',
    ])->assertRedirect(route('guest.account'));
    expect(Hash::check('new-password-456', (string) $a->fresh()->password))->toBeTrue();
});

// ── D2: delete requires typing DELETE, and only SCHEDULES a reversible 14-day erase (nothing removed now) ──
it('schedules a reversible 14-day deletion (never an immediate destroy) and emails the guest', function () {
    Mail::fake();
    $s = seedScenario(1);
    $a = acct('ada@example.test');

    // Type-DELETE guard: a bare submit is rejected.
    $this->actingAs($a, 'guest')->post('/guest/account/delete')->assertSessionHasErrors('confirm');
    expect($a->fresh()->scheduled_deletion_at)->toBeNull();

    // Confirmed → scheduled ~14 days out; PII + login KEPT; the guest stays signed in; a confirmation email sent.
    $this->actingAs($a, 'guest')->post('/guest/account/delete', ['confirm' => 'DELETE'])->assertRedirect(route('guest.account'));
    $fresh = $a->fresh();
    expect($fresh->scheduled_deletion_at)->not->toBeNull();
    expect($fresh->scheduled_deletion_at->toDateString())->toBe(now()->addDays(14)->toDateString());
    expect($fresh->password)->not->toBeNull();               // NOT anonymized — the erase is deferred
    expect((string) $fresh->email)->toBe('ada@example.test');
    expect($fresh->anonymized_at)->toBeNull();
    $this->assertAuthenticated('guest');                     // still signed in (can undo)
    Mail::assertSent(AccountDeletionScheduledMail::class);
});

// ── D2: undo clears the schedule (the guest keeps their account) ───────────────────────────────────
it('undoes a scheduled deletion', function () {
    $s = seedScenario(1);
    $a = acct('ada@example.test');
    $this->actingAs($a, 'guest')->post('/guest/account/delete', ['confirm' => 'DELETE'])->assertRedirect();
    expect($a->fresh()->scheduled_deletion_at)->not->toBeNull();

    $this->actingAs($a->fresh(), 'guest')->post('/guest/account/delete/undo')->assertRedirect(route('guest.account'));
    expect($a->fresh()->scheduled_deletion_at)->toBeNull();
});

// ── D2: the daily sweep runs the real erase after the grace window — anonymize + cancel upcoming ────
it('erases the guest and cancels upcoming reservations when the scheduled date passes', function () {
    $s = seedScenario(1);
    $a = $s['guest'];   // book()'s lead guest is the scenario guest
    $a->update(['email' => 'ada@example.test', 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $a->password = 'account-password-1234';
    $a->email_verified_at = now();
    $a->save();
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    expect((string) $reservation->status)->toBe('confirmed');

    $a->forceFill(['scheduled_deletion_at' => now()->subDay()])->save();   // grace window elapsed

    $erased = app(GuestDeletionService::class)->processDue();
    expect($erased)->toBe(1);

    $fresh = $a->fresh();
    expect($fresh->anonymized_at)->not->toBeNull();          // the certified GDPR erase ran
    expect($fresh->first_name)->toBeNull();                  // PII gone
    expect((string) $reservation->fresh()->status)->toBe('cancelled');   // upcoming reservation cancelled per policy
});

// ── D2: not-yet-due schedules are left alone (never erased early) ──────────────────────────────────
it('never erases a guest before the scheduled date', function () {
    $s = seedScenario(1);
    $a = acct('ada@example.test');
    $a->forceFill(['scheduled_deletion_at' => now()->addDays(10)])->save();

    expect(app(GuestDeletionService::class)->processDue())->toBe(0);
    expect($a->fresh()->anonymized_at)->toBeNull();          // untouched
});

// ── Account page shows REAL KPIs + honest cards/SMS (D3) ───────────────────────────────────────────
it('renders real KPIs with honest cards and SMS states', function () {
    seedScenario(1);
    $a = acct('ada@example.test');

    $this->actingAs($a, 'guest')->get('/guest/account')
        ->assertOk()
        ->assertSee('Saved cards')
        ->assertSee('No saved cards')            // D3 — honest empty state, no fake card
        ->assertSee('Not available yet')         // SMS honest-gated
        ->assertSee('Trip offers');
});

// ── Preferences: the trip-offers toggle genuinely flips marketing_consent (#87) ────────────────────
it('toggles marketing consent from the preferences form', function () {
    seedScenario(1);
    $a = acct('ada@example.test');
    expect((bool) $a->fresh()->marketing_consent)->toBeFalse();

    $this->actingAs($a, 'guest')->post('/guest/account/preferences', ['marketing_consent' => '1'])->assertRedirect(route('guest.account'));
    expect((bool) $a->fresh()->marketing_consent)->toBeTrue();
});

// ── The guest realm never falls back to the operator login ────────────────────────────────────────
it('bounces an unauthenticated visitor to the GUEST login, not the operator login', function () {
    $this->get('/guest/account')->assertRedirect(route('guest.login'));
});

// ── 🔒 THE anonymize() CONVERGENCE: the account-delete really reaches G4/G7/G8's stores ────────────
// COVERAGE ≠ TRIGGERING. G4 (check-in note + arrival window + phone), G7 (message bodies + sender labels)
// and G8 (a review's identifying fields) each proved Guest::anonymize() COVERS its store — but each proved it
// by calling anonymize() DIRECTLY. Nothing tied the guest-facing delete to those stores end-to-end: the
// account-delete does not call anonymize() at all, it stamps `scheduled_deletion_at`, and a daily command
// (`guests:process-deletions`, registered in routes/console.php) runs the sweep 14 days later. A covered
// anonymize() erases nothing if that chain is broken anywhere along it.
//
// This drives the REAL chain — schedule → grace elapses → processDue() — and asserts each gate's store.
it('clears the check-in, message and review PII that G4/G7/G8 proved covered, through the delete path', function () {
    $s = seedScenario(1);
    $a = $s['guest'];
    $a->update(['email' => 'ada@example.test', 'first_name' => 'Ada', 'last_name' => 'Lovelace', 'phone' => '+353860000123']);
    $a->password = 'account-password-1234';
    $a->email_verified_at = now();
    $a->save();
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    // G4 — the check-in stores (the guest's own free text + the coarse arrival window).
    $reservation->forceFill([
        'notes_guest' => 'Travelling with my daughter Mia — please leave a cot',
        'arrival_window' => '18:00-20:00',
    ])->save();

    // G7 — a guest-authored message body, written through the real inbox path.
    $thread = app(InboxService::class)->startThread(
        ['channel' => 'manual', 'guest_id' => $a->getKey(), 'subject' => 'Early check-in?']);
    app(InboxService::class)->postGuestMessage($thread, 'We land at 2pm — can we come early?');

    // G8 — the operator's PUBLIC reply, which is the identifying half of a review (the body is retained
    // by design; the reply had kept the guest's first name on an indexable page).
    Review::create([
        'organization_id' => $s['org']->id, 'reservation_id' => $reservation->getKey(),
        'guest_id' => $a->getKey(), 'rating' => 5, 'body' => 'Lovely stay.',
        'operator_response' => 'Thank you Ada — a pleasure hosting you!', 'responded_at' => now(),
    ]);

    // The REAL chain: the guest asked to be deleted, the grace window elapsed, the daily sweep runs.
    $a->forceFill(['scheduled_deletion_at' => now()->subDay()])->save();
    expect(app(GuestDeletionService::class)->processDue())->toBe(1);

    $res = $reservation->fresh();
    expect($res->notes_guest)->toBeNull()                       // G4
        ->and($res->arrival_window)->toBeNull()                 // G4
        ->and($a->fresh()->phone)->toBeNull();                  // G4
    expect(Message::query()->where('thread_id', $thread->getKey())->first()->body)->toBe('');   // G7
    expect(Review::query()->where('guest_id', $a->getKey())->first()->operator_response)->toBeNull();   // G8
});

// ─────────────────────────── FIX 01 · F-26 — THE DELETION SCREEN MUST STATE ITS CONSEQUENCE ───────────────────────────
// The erase cancels EVERY still-live reservation through cancelWithPolicyRefund (a real, policy-dependent refund),
// so the screen a guest decides on must say so BEFORE the button. ⚠️ These assert the RENDERED page with <script>,
// <style> and HTML comments STRIPPED — a warning that lives in a comment is F-21's exact defect and must fail here.

/** One live booking for this guest, through the certified single write path. */
function vyDelBook(array $s, Guest $guest, string $in = '2026-09-05', string $out = '2026-09-08'): Reservation
{
    return app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $in, 'check_out' => $out, 'lead_guest_id' => $guest->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => $in, 'check_out' => $out, 'price_minor' => 10000, 'currency' => 'EUR']],
    );
}

/** Visible text only: no <script>, no <style>, no HTML comments — what a reader actually sees. */
function vyVisibleText(string $html): string
{
    $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html) ?? $html;
    $html = preg_replace('#<!--.*?-->#s', ' ', $html) ?? $html;

    return (string) preg_replace('/\s+/', ' ', strip_tags($html));
}

it('warns on the account screen that deleting cancels bookings, quantified from the same set the erase uses', function () {
    $s = seedScenario(1);
    $a = acct('ada@example.test');
    vyDelBook($s, $a);   // one live booking

    $expected = app(GuestDeletionService::class)->reservationsToCancel($a->fresh())->count();
    expect($expected)->toBe(1);       // the fixture is what we think it is

    $text = vyVisibleText($this->actingAs($a->fresh(), 'guest')->get('/guest/account')->assertOk()->getContent());

    // 1 — the consequence itself, and it is QUANTIFIED from reservationsToCancel()
    expect($text)->toMatch('/cancels?\s+'.$expected.'\s+booking/i');
    // 2 — the refund is stated as POLICY-DEPENDENT, never promised (a wrong reassurance is worse than silence)
    expect($text)->toMatch('/cancellation policy/i');
    expect($text)->toMatch('/may not be refunded/i');
    // 3 — the LIMIT renders: a stay already under way goes too (F-21 — a limit in a comment reaches nobody)
    expect($text)->toMatch('/already under way/i');
    // 4 — the undo is offered, because it exists
    expect($text)->toMatch('/undo/i');
});

it('tells a guest with no bookings that none will be cancelled, and never invents a count', function () {
    seedScenario(1);
    $a = acct('nobookings@example.test');

    expect(app(GuestDeletionService::class)->reservationsToCancel($a->fresh())->count())->toBe(0);

    $text = vyVisibleText($this->actingAs($a->fresh(), 'guest')->get('/guest/account')->assertOk()->getContent());

    expect($text)->toMatch('/no bookings with us right now/i');
    expect($text)->not->toMatch('/cancels?\s+0\s+booking/i');   // never "cancels 0 bookings"
});

it('repeats the consequence on the SCHEDULED state, where the guest decides whether to undo', function () {
    $s = seedScenario(1);
    $a = acct('scheduled@example.test');
    vyDelBook($s, $a);

    $this->actingAs($a->fresh(), 'guest')->post('/guest/account/delete', ['confirm' => 'DELETE'])->assertRedirect();

    $text = vyVisibleText($this->actingAs($a->fresh(), 'guest')->get('/guest/account')->assertOk()->getContent());

    expect($text)->toMatch('/scheduled for deletion/i');
    expect($text)->toMatch('/cancel\s+1\s+booking/i');
    expect($text)->toMatch('/cancellation policy/i');
});

it('carries the same consequence into the scheduled-deletion email, from the same count', function () {
    Mail::fake();
    $s = seedScenario(1);
    $a = acct('mailed@example.test');
    vyDelBook($s, $a);

    $this->actingAs($a->fresh(), 'guest')->post('/guest/account/delete', ['confirm' => 'DELETE'])->assertRedirect();

    Mail::assertSent(AccountDeletionScheduledMail::class, function (AccountDeletionScheduledMail $mail): bool {
        $intro = (string) ($mail->content()->with['intro'] ?? '');

        return $mail->staysCancelled === 1
            && preg_match('/cancel the 1 booking/i', $intro) === 1
            && preg_match('/cancellation policy/i', $intro) === 1;
    });
});
