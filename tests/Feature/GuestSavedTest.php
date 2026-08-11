<?php

use App\Models\Guest;
use App\Models\GuestWaitlistEntry;
use App\Models\GuestWishlistItem;
use App\Services\GuestWishlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// GUEST SAVED (Phase 4c · Sub-gate 4) — wishlist + waitlist. Proven: a guest saves/removes rooms (idempotent) and
// joins/leaves/re-confirms the waitlist, all self-only; a guest can never see or modify another guest's saved
// items (mutation-proven). The notify trigger + auto-hold are deferred honestly.

if (! function_exists('savGuest')) {
    function savGuest(Guest $g, string $email): Guest
    {
        $g->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $g->password = 'guest-password-1234';
        $g->email_verified_at = now();
        $g->save();

        return $g->fresh();
    }
}

// ── Wishlist: save (idempotent) + remove, self-only ───────────────────────────────────────────────
it('saves a room to the wishlist idempotently and removes it', function () {
    $s = seedScenario(1);
    $a = savGuest($s['guest'], 'ada@example.test');

    $this->actingAs($a, 'guest')->post(route('guest.saved.wishlist.add'), ['room_type_id' => $s['roomType']->id])->assertRedirect();
    $this->actingAs($a, 'guest')->post(route('guest.saved.wishlist.add'), ['room_type_id' => $s['roomType']->id])->assertRedirect();   // again
    expect(GuestWishlistItem::withoutGlobalScopes()->where('guest_id', $a->getKey())->count())->toBe(1);   // idempotent

    $item = GuestWishlistItem::withoutGlobalScopes()->where('guest_id', $a->getKey())->first();
    $this->actingAs($a, 'guest')->post(route('guest.saved.wishlist.remove', $item->id))->assertRedirect();
    expect(GuestWishlistItem::withoutGlobalScopes()->where('guest_id', $a->getKey())->count())->toBe(0);
});

// ── Waitlist: join (exact dates) → re-confirm extends expiry → leave ──────────────────────────────
it('joins, re-confirms and leaves the waitlist', function () {
    $s = seedScenario(1);
    $a = savGuest($s['guest'], 'ada@example.test');

    $this->actingAs($a, 'guest')->post(route('guest.saved.waitlist.join'), ['room_type_id' => $s['roomType']->id, 'mode' => 'dates', 'check_in' => '2026-09-20', 'check_out' => '2026-09-23', 'notify_similar' => '1'])->assertRedirect();
    $entry = GuestWaitlistEntry::withoutGlobalScopes()->where('guest_id', $a->getKey())->first();
    expect($entry)->not->toBeNull();
    expect((bool) $entry->notify_similar)->toBeTrue();

    // Simulate a lapsed entry, then re-confirm → the expiry is pushed back into the future.
    $entry->forceFill(['expires_at' => now()->subDay()])->save();
    $this->actingAs($a, 'guest')->post(route('guest.saved.waitlist.reconfirm', $entry->id))->assertRedirect();
    expect($entry->fresh()->expires_at->isFuture())->toBeTrue();

    $this->actingAs($a, 'guest')->post(route('guest.saved.waitlist.leave', $entry->id))->assertRedirect();
    expect(GuestWaitlistEntry::withoutGlobalScopes()->find($entry->id))->toBeNull();
});

// ── Waitlist: a flexible-month entry ──────────────────────────────────────────────────────────────
it('joins the waitlist for a flexible month', function () {
    $s = seedScenario(1);
    $a = savGuest($s['guest'], 'ada@example.test');

    $this->actingAs($a, 'guest')->post(route('guest.saved.waitlist.join'), ['room_type_id' => $s['roomType']->id, 'mode' => 'month', 'flexible_month' => '2026-10'])->assertRedirect();
    $entry = GuestWaitlistEntry::withoutGlobalScopes()->where('guest_id', $a->getKey())->first();
    expect((string) $entry->flexible_month)->toBe('2026-10');
    expect($entry->check_in)->toBeNull();
});

// ── ISOLATION (mutation-proven): a guest can't see or modify another guest's saved items ──────────
it('never lets a guest see or modify another guest’s saved items', function () {
    $s = seedScenario(1);
    $a = savGuest($s['guest'], 'ada@example.test');
    $b = Guest::create(['email' => 'bob@example.test', 'first_name' => 'Bob', 'last_name' => 'Vale']);
    $bItem = app(GuestWishlistService::class)->saveRoom($b, $s['roomType']);
    $bEntry = app(GuestWishlistService::class)->joinWaitlist($b, $s['roomType'], ['flexible_month' => '2026-10']);

    $this->actingAs($a, 'guest')->post(route('guest.saved.wishlist.remove', $bItem->id))->assertNotFound();
    $this->actingAs($a, 'guest')->post(route('guest.saved.waitlist.leave', $bEntry->id))->assertNotFound();
    $this->actingAs($a, 'guest')->post(route('guest.saved.waitlist.reconfirm', $bEntry->id))->assertNotFound();

    expect(GuestWishlistItem::withoutGlobalScopes()->find($bItem->id))->not->toBeNull();   // B's untouched
    $this->actingAs($a, 'guest')->get(route('guest.saved'))->assertOk()->assertSee('Nothing saved yet');   // A sees only their own (empty)
});

// ── ⌨️ G10 — EVERY CONTROL ON THE JOIN-WAITLIST FORM CARRIES AN ACCESSIBLE NAME ────────────────────
// A placeholder is not a name (it vanishes on input and is not exposed as one by every AT), and neither is
// an <option>. Five controls — two selects and three date fields — had NO label, no aria-label and no title:
// a screen-reader user heard "combo box" and "date" four times over, unable to tell check-in from check-out.
// ⚠️ My first probe reported them as NAMED because it fell back to option text / the `name` attribute — the
// §N.8e vacuous-probe failure, caught by re-running with a strict accessible-name computation.
it('gives every join-waitlist control an accessible name, not just a placeholder', function () {
    $s = seedScenario(1);
    $a = savGuest($s['guest'], 'ada@example.test');

    $html = $this->actingAs($a, 'guest')->get(route('guest.saved'))->assertOk()->getContent();

    foreach (['Room to wait for', 'Date flexibility', 'Check-in date', 'Check-out date', 'Flexible month'] as $name) {
        expect($html)->toContain('aria-label="'.$name.'"');
    }

    // The form's three writes opt into the shell's shared double-submit guard — `joinWaitlist()` is a bare
    // create() with no uniqueness rule, so a double-submit would otherwise post two identical entries.
    expect(substr_count($html, 'data-guard-submit'))->toBeGreaterThanOrEqual(1);
});
