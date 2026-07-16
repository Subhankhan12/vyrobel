<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\PropertyPolicy;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Wave 2 · sub-gate 2 — the CANCELLED and NO-SHOW terminal states of App\Livewire\ReservationDetail
// (/app/reservations/{reservation}). They are STATE VARIANTS of that one screen, not screens of their own.
//
// Locks: the real cancel/no-show writes persist through AssignmentService (the single inventory write
// path); the provenance panel renders only columns terminate() genuinely writes (a no-show has NO reason —
// markNoShow() stores none — so none is ever invented); the status timeline is the real
// reservation_status_history trail; the cancellation policy is the real property_policies.cancellation
// JSON rendered as CONTEXT, not a fee; the fee engine is DEFERRED, never faked (locked decision 5); the
// design's fictions (Booking.com / Expedia / Visa / "Completed") never appear; and tenant isolation +
// the back-office gate hold on BOTH write routes.

/** A real OPERATOR: an active member of the scenario's org holding a real back-office role. */
function lifecycleMember(array $scenario, string $name = 'Marisol Vandenberg'): User
{
    $user = User::factory()->create(['name' => $name, 'status' => 'active']);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return grantBackOfficeRole($user, $scenario['org']);
}

/** The property's REAL cancellation policy row — exactly the shape DemoSeeder writes. */
function seedCancellationPolicy(array $scenario): PropertyPolicy
{
    return PropertyPolicy::create([
        'property_id' => $scenario['property']->id,
        'cancellation' => ['type' => 'flexible', 'free_until_days' => 2],
    ]);
}

// ⚠️ book() defaults EVERY stay to the same dates, so each reservation needs its OWN unit or the
// no-double-booking guard (correctly) rejects it. seedScenario(N) provides N units.

// ── 1. The real CANCEL write — through the route, the controller and the service ─────────────────
it('cancels for real through AssignmentService and renders the stored reason', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $operator = lifecycleMember($s);

    $this->actingAs($operator)
        ->post(route('app.reservations.cancel', $res->id), ['reason' => 'Guest called to cancel'])
        ->assertRedirect(route('app.reservations.show', $res->id))
        ->assertSessionHas('folio_notice');

    // The REAL columns terminate() writes.
    $fresh = Reservation::query()->findOrFail($res->id);
    expect((string) $fresh->status)->toBe('cancelled')
        ->and($fresh->cancelled_at)->not->toBeNull()
        ->and((string) $fresh->cancellation_reason)->toBe('Guest called to cancel');

    // …and the page renders that real reason back, labelled as a cancellation.
    $response = $this->actingAs($operator)->get("/app/reservations/{$res->id}");
    $response->assertOk()
        ->assertSee('Cancelled at')
        ->assertSee('Guest called to cancel')
        ->assertDontSee('Marked no-show at');   // the shared cancelled_at column, labelled per state

    assertNoRawBlade($response);
});

// ── 2. The real NO-SHOW write — and NO fabricated reason (markNoShow stores none) ────────────────
it('marks a no-show for real and invents no reason, because the service stores none', function () {
    $s = seedScenario(1);
    seedCancellationPolicy($s);   // a real policy IS on file — see the no-show assertion below
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $operator = lifecycleMember($s);

    $this->actingAs($operator)
        ->post(route('app.reservations.no-show', $res->id))
        ->assertRedirect(route('app.reservations.show', $res->id))
        ->assertSessionHas('folio_notice');

    $fresh = Reservation::query()->findOrFail($res->id);
    expect((string) $fresh->status)->toBe('no_show')
        ->and($fresh->cancelled_at)->not->toBeNull()
        ->and($fresh->cancellation_reason)->toBeNull();   // markNoShow() passes null — genuinely no reason

    $response = $this->actingAs($operator)->get("/app/reservations/{$res->id}");
    $response->assertOk()
        ->assertSee('No-show')
        // The SHARED cancelled_at column must be labelled honestly for THIS state.
        ->assertSee('Marked no-show at')
        ->assertDontSee('Cancelled at')
        // No reason exists → the row is omitted entirely. Never a dash pretending to be an empty field,
        // and never the design's invented "guest never arrived".
        ->assertDontSee('guest never arrived')
        ->assertDontSee('Reason')
        // The CANCELLATION policy is not captioned at a no-show even though one is on file: that JSON is
        // the cancellation policy specifically and carries no no-show key, so showing it here would imply
        // a no-show policy the property never configured.
        ->assertDontSee('Cancellation policy')
        ->assertDontSee('Flexible');

    assertNoRawBlade($response);
});

// ── 3. The status timeline is the REAL reservation_status_history trail ──────────────────────────
it('renders the real status timeline from reservation_status_history', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $operator = lifecycleMember($s);

    $this->actingAs($operator)->post(route('app.reservations.cancel', $res->id), ['reason' => 'Double booked']);

    // The row genuinely exists in the DB — written by AssignmentService::recordStatusHistory().
    $this->assertDatabaseHas('reservation_status_history', [
        'reservation_id' => $res->id,
        'from_status' => 'confirmed',
        'to_status' => 'cancelled',
        'reason' => 'Double booked',
    ]);
    // …with the REAL actor: the operator who drove the route.
    $transition = ReservationStatusHistory::query()
        ->where('reservation_id', $res->id)->where('to_status', 'cancelled')->firstOrFail();
    expect((string) $transition->changed_by)->toBe((string) $operator->id);

    // …and the page shows the transition in the real enum's vocabulary, with the real actor.
    $this->actingAs($operator)->get("/app/reservations/{$res->id}")
        ->assertOk()
        ->assertSee('Status history')
        ->assertSee('Confirmed → Cancelled')
        ->assertSee('Marisol Vandenberg');
});

// ── 4. GUARDED: re-cancelling an already-cancelled reservation is refused CLEANLY and changes nothing ──
// This is the guard that must live in the controller: AssignmentService::terminate() has NO status check,
// so calling it again would silently OVERWRITE cancelled_at/cancellation_reason (destroying the real
// provenance) and append a bogus `cancelled → cancelled` row to an append-only audit trail.
it('refuses to re-cancel an already-cancelled reservation, cleanly, leaving the row untouched', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $operator = lifecycleMember($s);

    $this->actingAs($operator)->post(route('app.reservations.cancel', $res->id), ['reason' => 'The original reason']);

    $afterFirst = Reservation::query()->findOrFail($res->id);
    $originalCancelledAt = $afterFirst->cancelled_at;
    $historyCount = ReservationStatusHistory::query()->where('reservation_id', $res->id)->count();

    // A second attempt — a clean flash, a redirect, NO 500.
    $this->actingAs($operator)
        ->post(route('app.reservations.cancel', $res->id), ['reason' => 'A DIFFERENT reason'])
        ->assertRedirect(route('app.reservations.show', $res->id))
        ->assertSessionHas('folio_notice');

    // The row is UNCHANGED — the original provenance survives intact.
    $afterSecond = Reservation::query()->findOrFail($res->id);
    expect((string) $afterSecond->status)->toBe('cancelled')
        ->and((string) $afterSecond->cancellation_reason)->toBe('The original reason')
        ->and($afterSecond->cancelled_at->equalTo($originalCancelledAt))->toBeTrue()
        // …and no junk transition was appended to the audit trail.
        ->and(ReservationStatusHistory::query()->where('reservation_id', $res->id)->count())->toBe($historyCount);

    // The same guard covers no-showing a cancelled booking.
    $this->actingAs($operator)->post(route('app.reservations.no-show', $res->id))
        ->assertRedirect()->assertSessionHas('folio_notice');
    expect((string) Reservation::query()->findOrFail($res->id)->status)->toBe('cancelled');
});

// ── 5. ACTION GATING — a terminal reservation is offered no transition; a confirmed one is ───────
it('offers the lifecycle actions only when the real status allows them', function () {
    $s = seedScenario(2);
    $operator = lifecycleMember($s);

    $live = book($s, ['unit_id' => $s['units'][0]->id]);
    $dead = book($s, ['unit_id' => $s['units'][1]->id]);   // its OWN unit — same dates otherwise collide

    // A confirmed booking: both transitions are genuinely offered (so this test cannot pass vacuously).
    $this->actingAs($operator)->get("/app/reservations/{$live->id}")
        ->assertOk()
        ->assertSee(route('app.reservations.cancel', $live->id))
        ->assertSee(route('app.reservations.no-show', $live->id))
        ->assertSee('Mark no-show');

    $this->actingAs($operator)->post(route('app.reservations.cancel', $dead->id), ['reason' => 'Guest request']);

    // A terminal booking: neither is offered.
    $this->actingAs($operator)->get("/app/reservations/{$dead->id}")
        ->assertOk()
        ->assertDontSee(route('app.reservations.cancel', $dead->id))
        ->assertDontSee(route('app.reservations.no-show', $dead->id))
        ->assertDontSee('Mark no-show');
});

// ── 6. The FEE is DEFERRED, not faked — no figure, no waive flow (locked decision 5) ─────────────
it('defers the cancellation and no-show fees instead of fabricating them', function () {
    $s = seedScenario(2);
    $operator = lifecycleMember($s);

    $cancelled = book($s, ['unit_id' => $s['units'][0]->id]);
    $noShow = book($s, ['unit_id' => $s['units'][1]->id]);

    $this->actingAs($operator)->post(route('app.reservations.cancel', $cancelled->id), ['reason' => 'Guest request']);
    $this->actingAs($operator)->post(route('app.reservations.no-show', $noShow->id));

    $this->actingAs($operator)->get("/app/reservations/{$cancelled->id}")
        ->assertOk()
        ->assertSee('Cancellation fee')
        ->assertSee('Soon')                  // the honest deferral idiom
        ->assertDontSee('€175.00')
        ->assertDontSee('€87.50')
        ->assertDontSee('Fee applied')
        ->assertDontSee('Waive fee')
        ->assertDontSee('Full fee');

    $this->actingAs($operator)->get("/app/reservations/{$noShow->id}")
        ->assertOk()
        ->assertSee('No-show fee')
        ->assertSee('Soon')
        ->assertDontSee('€175.00')
        ->assertDontSee('€87.50')
        ->assertDontSee('Waive fee')
        ->assertDontSee('Full fee')
        ->assertDontSee('Goodwill');
});

// ── 7. NO DESIGN FICTION — the OTA brand, the card brand and the invented status never appear ────
it('renders no fabricated brands, cards or statuses', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $operator = lifecycleMember($s);
    seedCancellationPolicy($s);

    $this->actingAs($operator)->post(route('app.reservations.cancel', $res->id), ['reason' => 'Guest request']);

    $this->actingAs($operator)->get("/app/reservations/{$res->id}")
        ->assertOk()
        ->assertDontSee('Booking.com')     // `source` has only 'ota' — the OTA brand is not modelled
        ->assertDontSee('Expedia')
        ->assertDontSee('Visa')            // payments has card_token only — no brand, no last4
        ->assertDontSee('····3307')
        ->assertDontSee('Completed')       // not in the status enum ('checked_out' is the real value)
        ->assertDontSee('Download receipt')
        ->assertDontSee('Email the guest')
        ->assertDontSee('Rebook')
        ->assertDontSee('Workspace paused');
});

// ── 8. TENANT ISOLATION — another org's reservation 404s on BOTH write routes, and nothing mutates ──
it('isolates tenants — org B cannot cancel or no-show org A\'s reservation', function () {
    $sA = seedScenario(1);
    $resA = book($sA, ['unit_id' => $sA['units'][0]->id]);
    $userA = lifecycleMember($sA, 'Amara Okafor');

    $sB = seedScenario(1);
    $userB = lifecycleMember($sB, 'Bao Nguyen');

    // A cross-org id is resolved through the tenant-scoped global scope → 404 on BOTH routes.
    $this->actingAs($userB)->post(route('app.reservations.cancel', $resA->id), ['reason' => 'malicious'])->assertNotFound();
    $this->actingAs($userB)->post(route('app.reservations.no-show', $resA->id))->assertNotFound();

    // A's reservation is completely untouched by B's attempts.
    // NB assert against the RAW table: seedScenario($sB) left the TEST PROCESS's tenant context on org B,
    // so an Eloquent read of A's row here would 404 on the global scope and prove nothing about the row.
    // (Each request above set its own context via SetTenantForWebRequest — that is the real code path.)
    $this->assertDatabaseHas('reservations', [
        'id' => $resA->id, 'status' => 'confirmed', 'cancelled_at' => null, 'cancellation_reason' => null,
    ]);

    // A can still act on their own — the isolation is a boundary, not a breakage.
    $this->actingAs($userA)->post(route('app.reservations.cancel', $resA->id), ['reason' => 'Guest request'])->assertRedirect();
    $this->assertDatabaseHas('reservations', ['id' => $resA->id, 'status' => 'cancelled', 'cancellation_reason' => 'Guest request']);
});

// ── 9. THE ROLE GATE — an owner-role user is refused both write routes ───────────────────────────
it('refuses an owner-role user both lifecycle routes', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);

    // A linked owner: a Membership (the only way their org resolves) + an Owner row, but NO back-office
    // role — exactly what OwnerInviteService produces. This is the BackOfficeAccessTest fixture idiom.
    $user = User::factory()->create(['name' => 'Ottilie Vance', 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);

    // An owner has a home — they are redirected there, never handed an operator write path.
    $this->actingAs($user)->post(route('app.reservations.cancel', $res->id), ['reason' => 'nope'])
        ->assertRedirect(route('owner.dashboard'));
    $this->actingAs($user)->post(route('app.reservations.no-show', $res->id))
        ->assertRedirect(route('owner.dashboard'));

    // Nothing moved.
    expect((string) Reservation::query()->findOrFail($res->id)->status)->toBe('confirmed');
});

// ── 10. The real cancellation POLICY renders from the seeded JSON — as CONTEXT, not a fee ────────
it('renders the real property cancellation policy without implying a fee', function () {
    $s = seedScenario(1);
    seedCancellationPolicy($s);   // {"type": "flexible", "free_until_days": 2} — the real seeded shape
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $operator = lifecycleMember($s);

    $this->actingAs($operator)->post(route('app.reservations.cancel', $res->id), ['reason' => 'Guest request']);

    $response = $this->actingAs($operator)->get("/app/reservations/{$res->id}");
    $response->assertOk()
        ->assertSee('Cancellation policy')
        // Both real keys, rendered from the real JSON — this screen is the column's first reader.
        ->assertSee('Flexible — free until 2 days before arrival')
        // …and the copy must NOT imply anything was computed against this booking.
        ->assertSee('Nothing has been calculated against this booking.');

    assertNoRawBlade($response);
});

// ── 11. No policy row → the panel disappears rather than rendering an empty shell ────────────────
it('omits the policy panel entirely when the property has no policy on file', function () {
    $s = seedScenario(1);   // deliberately NO PropertyPolicy
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $operator = lifecycleMember($s);

    $this->actingAs($operator)->post(route('app.reservations.cancel', $res->id), ['reason' => 'Guest request']);

    $this->actingAs($operator)->get("/app/reservations/{$res->id}")
        ->assertOk()
        ->assertDontSee('Cancellation policy')
        ->assertDontSee('Flexible');
});
