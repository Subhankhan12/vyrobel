<?php

use App\Models\Guest;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// GUEST ACCOUNT + GUEST ISOLATION (Phase 4a · Sub-gate 4) — the authed guest's OWN profile, change-password, and
// safe account removal. SELF-ONLY: everything resolves from the GUEST guard, never a request id — the isolation
// the whole 4b/4c portal relies on. Proven: a guest edits only their own; a guest can't reach another's (even
// with another's id in the payload); change-password is current-verified; deletion revokes access but keeps the
// booking record; the guest realm never falls back to the operator login.

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

// ── Safe deletion: revoke portal access, KEEP the guest row + PII + booking ───────────────────────
it('removes portal access but keeps the guest record and bookings', function () {
    $s = seedScenario(1);
    $a = $s['guest'];
    $a->update(['email' => 'ada@example.test', 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $a->password = 'account-password-1234';
    $a->email_verified_at = now();
    $a->save();
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);   // $s['guest'] leads a real booking

    $this->actingAs($a->fresh(), 'guest')->post('/guest/account/delete')->assertRedirect(route('guest.login'));

    $fresh = $a->fresh();
    expect($fresh->password)->toBeNull();               // portal access revoked…
    expect($fresh->email_verified_at)->toBeNull();
    expect($fresh->hasPortalAccount())->toBeFalse();
    expect($fresh->first_name)->toBe('Ada');            // …but the PII + record are KEPT (the operator needs them)
    expect((string) $fresh->email)->toBe('ada@example.test');
    expect(Reservation::where('lead_guest_id', $a->getKey())->count())->toBe(1);   // the booking survives
    $this->assertGuest('guest');
});

// ── The guest realm never falls back to the operator login ────────────────────────────────────────
it('bounces an unauthenticated visitor to the GUEST login, not the operator login', function () {
    $this->get('/guest/account')->assertRedirect(route('guest.login'));
});
