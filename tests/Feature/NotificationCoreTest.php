<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// NOTIFICATION CORE (Phase 3 · Sub-gate 1) — REAL domain events → per-user database notifications → the in-app
// feed. Laravel-native (Notifiable + notifications table). Proves: a real event dispatches; it lands in the
// RIGHT recipient's feed; a user sees ONLY their own (isolation, mutation-proven); mark-read / mark-all-read.

/** An owner (factory → tax-complete) with a linked portal user + a REAL finalized statement + a pending payout. */
if (! function_exists('notifPayoutOwner')) {
    function notifPayoutOwner(array $s, int $unitIndex, string $name): array
    {
        $owner = Owner::factory()->create(['name' => $name, 'payout_currency' => 'EUR', 'status' => 'active']);
        $unit = $s['units'][$unitIndex];
        $unit->update(['owner_id' => $owner->id]);
        ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01', 'effective_to' => null]);

        $reservation = book($s, ['unit_id' => $unit->id]);
        postRoom($reservation, 20000);
        app(OwnerAttributionService::class)->attributeReservation($reservation);
        collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)
        $svc = app(OwnerStatementService::class);
        $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));
        $payout = app(OwnerPayoutService::class)->createFromStatement($statement);

        $user = User::factory()->create(['name' => $name]);
        Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
        $owner->update(['user_id' => $user->id]);

        return compact('owner', 'user', 'payout');
    }
}

// ── 1. A real event (reservation.confirmed) → the operator's feed ─────────────────────────────────
it('dispatches an operator notification on a new booking and shows it in the feed', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, [], 'Hotel Manager');

    book($s, ['unit_id' => $s['units'][0]->id]);   // fires reservation.confirmed

    expect($operator->fresh()->notifications()->count())->toBe(1);

    $this->actingAs($operator)->get('/app/notifications')
        ->assertOk()
        ->assertSee('New booking')
        ->assertSee('1 unread');
});

// ── 2. A real event (payout.paid) → the OWNER's feed, to the right owner ──────────────────────────
it('dispatches an owner notification on a paid payout, to that owner', function () {
    $s = seedScenario(1);
    $a = notifPayoutOwner($s, 0, 'Amara Okafor');

    app(OwnerPayoutService::class)->pay($a['payout']);   // fires payout.paid

    expect($a['user']->fresh()->notifications()->count())->toBe(1);

    $this->actingAs($a['user'])->get('/owner/notifications')
        ->assertOk()
        ->assertSee('Payout sent')
        ->assertSee('€170.00');   // integer money — the payout amount
});

// ── 3. ISOLATION — a user sees ONLY their own; can't mark another's read (mutation-proven) ────────
it('never lets a user read or mark another user’s notifications', function () {
    $s = seedScenario(1);
    $a = notifPayoutOwner($s, 0, 'Amara Okafor');
    app(OwnerPayoutService::class)->pay($a['payout']);
    $aNotification = $a['user']->fresh()->notifications()->firstOrFail();

    // A stranger member with no notifications of their own.
    $b = User::factory()->create(['name' => 'Bruno Vale']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $b->id, 'status' => 'active']);

    // ⚠️ R-GATE (founder, 2026-09-19): B is a member with no owner link, so the OWNER feed is now refused
    //    at the realm door rather than rendered empty. The isolation being tested is unchanged and is
    //    asserted below on the route that is NOT owner-gated.
    $this->actingAs($b)->get('/owner/notifications')->assertForbidden();

    // …and B cannot mark A's notification read (it isn't in B's relation → 404); A's stays unread.
    $this->actingAs($b)->post('/notifications/'.$aNotification->id.'/read')->assertNotFound();
    expect($a['user']->fresh()->unreadNotifications()->count())->toBe(1);
});

// ── 4. Mark-read + mark-all-read ──────────────────────────────────────────────────────────────────
it('marks one and all notifications read', function () {
    $s = seedScenario(1);
    $a = notifPayoutOwner($s, 0, 'Amara Okafor');
    app(OwnerPayoutService::class)->pay($a['payout']);
    $n = $a['user']->fresh()->notifications()->firstOrFail();

    $this->actingAs($a['user'])->post('/notifications/'.$n->id.'/read')->assertRedirect();
    expect($a['user']->fresh()->unreadNotifications()->count())->toBe(0);

    // mark-all-read is a no-op when nothing is unread, and clears any unread otherwise.
    $this->actingAs($a['user'])->post('/notifications/read-all')->assertRedirect();
    expect($a['user']->fresh()->unreadNotifications()->count())->toBe(0);
});

// ═══════════════════════════════════════════════════════════════════════════════════════════════════
// P29 (design-parity) — the unread STATE must not ride on colour alone (WCAG 1.4.1). The design marks
// unread with a coloured dot; the dot previously carried only a `title` attribute, which is not
// reliably announced on a non-interactive span. The dot stays; the state now also has real text.
// ═══════════════════════════════════════════════════════════════════════════════════════════════════

it('conveys the unread state with TEXT, not colour alone (1.4.1)', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, [], 'Hotel Manager');
    book($s, ['unit_id' => $s['units'][0]->id]);

    $unreadPage = $this->actingAs($operator)->get('/app/notifications')->assertOk();
    $unreadPage->assertSee('New booking');
    // The accessible text for the state, and the dot itself hidden from the a11y tree (it is decoration).
    $unreadPage->assertSee('vy-sr-only">Unread', false);
    $unreadPage->assertSee('aria-hidden="true"', false);

    // Once read, the row carries neither the dot nor the "Unread" text — the state really is derived.
    $operator->fresh()->unreadNotifications->markAsRead();
    $this->actingAs($operator)->get('/app/notifications')
        ->assertOk()
        ->assertSee('New booking')
        ->assertDontSee('vy-sr-only">Unread', false);
});

it('drives the feed filter through a GET link, never a wire action (#20)', function () {
    $s = seedScenario(1);
    $operator = operatorUser($s, [], 'Hotel Manager');
    book($s, ['unit_id' => $s['units'][0]->id]);

    $this->actingAs($operator)->get('/app/notifications')
        ->assertOk()
        ->assertSee(route('app.notifications', ['filter' => 'unread']), false)
        ->assertDontSee('wire:click', false);
});
