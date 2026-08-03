<?php

use App\Mail\AccountDeletionScheduledMail;
use App\Models\Guest;
use App\Models\Reservation;
use App\Services\GuestDeletionService;
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
